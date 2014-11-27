// =========================================================================
// PumTrees - v0.1
// =========================================================================
// Needs jQuery JAAH and jsTree

;(function($, window, document) {
    'use strict';

    var PumTrees = function(options) {
        this.options = $.extend( this.defaults, options );
        this._init();
    };

    PumTrees.prototype = {

        defaults : {
            bindingClass: '.treeable', // The class to put on the element
        },

        _init : function()
        {
            this.bindingClass = this.defaults.bindingClass,
            this.allTrees     = $(this.defaults.bindingClass);

            this._initTrees(this.allTrees);
        },

        _initTrees : function(trees)
        { // Init Data needed from each tree
            var self = this;

            $(trees).each(function(key,item) {
                var namespace  = $(item).data('namespace'),
                    ajax_url   = $(item).data('ajax-url');

                self._initTree($(item), ajax_url, namespace);

                $(item).on("yaah-js_xhr_complete", "a.yaah-js", function() {
                    // TODO reload tatam
                });
            });

            return $(this);
        },

        _initTree : function(el, ajax_url, namespace)
        {
            var self      = this,
                container = $(el.data('container'));

            // Create the instance
            el.jstree({
                "core" : {
                    "animation" : 0,
                    'check_callback' : function (operation, node, node_parent, node_position, more) {
                        // operation can be 'create_node', 'rename_node', 'delete_node', 'move_node' or 'copy_node'
                        // in case of 'rename_node' node_position is filled with the new node name
                        return true;
                    },
                    "multiple": true,
                    "themes" : {
                        "stripes" : false,
                        "icons" : true
                    },
                    'data' : {
                        'url' : function (node) {
                            var params       = jQuery.param({
                                'action' : 'node'
                            });

                            return ajax_url+'?'+params;
                        },
                        'data' : function (node) {
                            return { 'id' : node.id };
                        }
                    }
                },
                "plugins" : [ "dnd", "types", "state", "contextmenu" ],
                "contextmenu": {
                    "items": self._customMenu(container, ajax_url)
                }
            });

            // events on object
            el.on('load_node.jstree', function (node, data) {
                var status = data.status,
                    node   = data.node;

                if (status && node.id != '#') {
                    self._addNode(node.id, namespace);
                }

                self._reloadYaah(250);
            });

            el.on('after_open.jstree', function (node, data) {
                var node = data.node;

                if (node.id != '#') {
                    self._addNode(node.id, namespace);
                }

                self._reloadYaah();
            });

            el.on('after_close.jstree', function (node, data) {
                var node = data.node;

                if (node.id != '#') {
                    self._removeNode(node.id, namespace);
                }
            });

            el.on("move_node.jstree", function (node, data) {
                var params       = jQuery.param({
                    'action'     : 'move_node',
                    'id'         : data.node.id,
                    'new_pos'    : data.position,
                    'new_parent' : data.parent,
                    'old_parent' : data.old_parent
                });

                self._callAjax(ajax_url+'?'+params);
            });

            el.on("create_node.jstree.jstree", function (node, data) {
                var params       = jQuery.param({
                    'action'     : 'create_node',
                    'id'         : data.node.id,
                    'label'      : data.node.text,
                    'parent'     : data.parent,
                    'position'   : data.position
                });

                //self._callAjax(ajax_url+'?'+params);
            });

            el.on("delete_node.jstree.jstree", function (node, data) {
                var params       = jQuery.param({
                    'action'     : 'delete_node',
                    'id'         : data.node.id
                });

                self._callAjax(ajax_url+'?'+params);
            });

            el.on("rename_node.jstree.jstree", function (node, data) {
                if (data.text != data.old) {
                    var params       = jQuery.param({
                        'action'     : 'rename_node',
                        'id'         : data.node.id,
                        'label'      : data.text
                    });

                    self._callAjax(ajax_url+'?'+params);
                }
            });

            return $(this);
        },

        _customMenu : function(container, ajax_url)
        {
            var self  = this,
                items = {
                "create" : {
                    "separator_before"  : false,
                    "separator_after"   : false,
                    "_disabled"         : false,
                    "label"             : "Create",
                    "action"            : function (data) {
                        var inst = $.jstree.reference(data.reference),
                            obj = inst.get_node(data.reference);

                        self._loadCreateNodeForm(inst, obj, container, ajax_url);
                    }
                },
                "rename" : {
                    "separator_before"  : false,
                    "separator_after"   : false,
                    "_disabled"         : false,
                    "label"             : "Rename",
                    "action"            : function (data) {
                        var inst = $.jstree.reference(data.reference),
                            obj = inst.get_node(data.reference);
                        inst.edit(obj);
                    }
                },
                "remove" : {
                    "separator_before"  : false,
                    "icon"              : false,
                    "separator_after"   : false,
                    "label"             : "Delete",
                    "_disabled"         : function (data) {
                        var inst = $.jstree.reference(data.reference),
                            obj = inst.get_node(data.reference);

                        return !inst.is_leaf(obj);
                    },
                    "action"            : function (data) {
                        var inst = $.jstree.reference(data.reference),
                            obj = inst.get_node(data.reference);
                        if(inst.is_selected(obj)) {
                            inst.delete_node(inst.get_selected());
                        }
                        else {
                            inst.delete_node(obj);
                        }
                    }
                }/*,
                "ccp" : {
                    "separator_before"  : true,
                    "icon"              : false,
                    "separator_after"   : false,
                    "label"             : "Edit",
                    "action"            : false,
                    "submenu" : {
                        "cut" : {
                            "separator_before"  : false,
                            "separator_after"   : false,
                            "label"             : "Cut",
                            "action"            : function (data) {
                                var inst = $.jstree.reference(data.reference),
                                    obj = inst.get_node(data.reference);
                                if(inst.is_selected(obj)) {
                                    inst.cut(inst.get_selected());
                                }
                                else {
                                    inst.cut(obj);
                                }
                            }
                        },
                        "copy" : {
                            "separator_before"  : false,
                            "icon"              : false,
                            "separator_after"   : false,
                            "label"             : "Copy",
                            "action"            : function (data) {
                                var inst = $.jstree.reference(data.reference),
                                    obj = inst.get_node(data.reference);
                                if(inst.is_selected(obj)) {
                                    inst.copy(inst.get_selected());
                                }
                                else {
                                    inst.copy(obj);
                                }
                            }
                        },
                        "paste" : {
                            "separator_before"  : false,
                            "icon"              : false,
                            "_disabled"         : function (data) {
                                return !$.jstree.reference(data.reference).can_paste();
                            },
                            "separator_after"   : false,
                            "label"             : "Paste",
                            "action"            : function (data) {
                                var inst = $.jstree.reference(data.reference),
                                    obj = inst.get_node(data.reference);
                                inst.paste(obj);
                            }
                        }
                    }
                }*/
            };

            return items;
        },

        _callAjax : function(url, data, type)
        {
            data = data ? data : {};
            type = type ? type : 'GET';

            $.ajax({
                url: url,
                type: type,
                data: data
            }).done(function(response) {
                if (response != 'OK') {
                    console.log('ERROR');
                }
            });
        },

        _loadCreateNodeForm : function(tree, node, container, ajax_url)
        {
            var self   = this,
                params = jQuery.param({
                    'action'    : 'create_node',
                    'parent_id' : node.id
                });

            $.ajax({
                type: 'GET',
                url: ajax_url+'?'+params,
                cache: false,
                success: function(data){
                    container.html(data);
                }
            });
        },

        _refreshNode : function(tree, node_id)
        {
            tree.jstree(true).refresh_node(node_id);

            return $(this);
        },

        _addNode : function(node_id, namespace)
        {
            var self = this,
                values = self._getCookie(namespace);

            if (values) {
                values = JSON.parse(values);
                values.push(node_id);
                values = values.filter(function(itm,i,a){
                    return i==a.indexOf(itm);
                });
            } else {
                values = [node_id];
            }

            self._setCookie(namespace, JSON.stringify(values));

            return $(this);
        },

        _removeNode : function(node_id, namespace)
        {
            var self = this,
                values = self._getCookie(namespace);

            if (values) {
                values = JSON.parse(values);
                values = jQuery.grep(values, function(value) {
                  return value != node_id;
                });
            } else {
                values = [node_id];
            }

            self._setCookie(namespace, JSON.stringify(values));

            return $(this);
        },

        _setCookie : function(cname, value, exdays)
        {
            var self = this;

            if (exdays) {
                var date = new Date();
                date.setTime(date.getTime()+(exdays*24*60*60*1000));
                var expires = "; expires="+date.toGMTString();
            } else {
                var expires = "";
            }

            document.cookie = cname+"="+value+expires+";";

            return $(this);
        },

        _getCookie : function(cname)
        {
            var nameEQ = cname + "=",
                ca = document.cookie.split(';');

            for(var i=0;i < ca.length;i++) {
                var c = ca[i];

                while (c.charAt(0)==' ') c = c.substring(1,c.length);
                if (c.indexOf(nameEQ) == 0) {
                    return c.substring(nameEQ.length,c.length);
                }
            }

            return null;
        },

        _deleteCookie : function(cname)
        {
            document.cookie = cname + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';

            return $(this);
        },

        _reloadYaah : function(time)
        {
            time = time ? time : 0;

            if (typeof window.Yaah != 'undefined') {
                setTimeout(function(){
                    window.Yaah._ya_reload()
                }, time);
            }

            return $(this);
        },

        _reinit : function(options)
        {
            if (typeof options != 'undefined' && typeof options.bindingClass != 'undefined') {
                this.defaults.bindingClass = options.bindingClass;
            } else {
                this.defaults.bindingClass = '.treeable';
            }

            if (typeof $.jstree != 'undefined') {
                $.jstree.destroy();
            }

            this._init();
        },
    };

    $(document).ready(function(){
        window.PumTrees = new PumTrees();
    });

})(window.jQuery, window, document);