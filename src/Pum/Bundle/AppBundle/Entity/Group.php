<?php

namespace Pum\Bundle\AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Pum\Core\Definition\Beam;
use Pum\Core\Definition\ObjectDefinition;
use Pum\Core\Definition\Project;
use Pum\Bundle\ProjectAdminBundle\Entity\CustomView;

/**
 * @ORM\Entity(repositoryClass="GroupRepository")
 * @ORM\Table(name="ww_group")
 */
class Group
{
    public static $appPermissions = array(
        'ROLE_APP_CONFIG',
    );

    public static $woodworkPermissions = array(
        'ROLE_WW_USERS',
        'ROLE_WW_BEAMS',
        'ROLE_WW_LOGS',
        'ROLE_WW_PROJECTS',
        'ROLE_WW_ROUTING',
    );

    public static $projectAdminPermissions = array(
        'ROLE_PA_LIST',
        'ROLE_PA_VARS',

        'ROLE_PA_VIEW_EDIT',
        'ROLE_PA_DEFAULT_VIEWS',
        'ROLE_PA_CUSTOM_VIEWS',
        'ROLE_PA_ROUTING',
    );

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=128)
     */
    protected $name;

    /**
     * @ORM\Column(type="array")
     */
    protected $permissions;

    /**
     * @var Permission[]
     *
     * @ORM\OneToMany(targetEntity="Permission", mappedBy="group")
     */
    protected $advancedPermissions;

    /**
     * @ORM\ManyToMany(targetEntity="User",mappedBy="groups")
     */
    protected $users;

    /**
     * @var customView[]
     *
     * @ORM\OneToMany(targetEntity="Pum\Bundle\ProjectAdminBundle\Entity\CustomView", mappedBy="group")
     */
    protected $customViews;

    public function __construct($name = null)
    {
        $this->name                = $name;
        $this->permissions         = array();
        $this->users               = new ArrayCollection();
        $this->advancedPermissions = new ArrayCollection();
        $this->customViews         = new ArrayCollection();
    }

    /**
     * @return string
     */
    public static function getKnownPermissions()
    {
        return array_merge(self::$appPermissions, self::$woodworkPermissions, self::$projectAdminPermissions);
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Group
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Group
     */
    public function setPermissions(array $permissions)
    {
        foreach ($permissions as $permission) {
            if (!is_string($permission)) {
                throw new \InvalidArgumentException(sprintf('Expected a string, got a "%s".', is_object($permission) ? get_class($permission) : gettype($permission)));
            }
            if (!in_array($permission, self::getKnownPermissions())) {
                throw new \InvalidArgumentException(sprintf('Permission "%s" unknown. Known are: %s', $permission, implode(', ', self::getKnownPermissions())));
            }
        }

        $this->permissions = $permissions;

        return $this;
    }

    /**
     * @return array an array of strings
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * READ-ONLY (inverse-side of a many-to-many relation)
     *
     * @return ArrayCollection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param Permission[] $advancedPermissions
     */
    public function setAdvancedPermissions(array $advancedPermissions)
    {
        $this->advancedPermissions->clear();
        foreach ($advancedPermissions as $permission) {
            $this->advancedPermissions->add($permission);
        }
    }

    /**
     * @return Permission[]
     */
    public function getAdvancedPermissions()
    {
        return $this->advancedPermissions;
    }

    /**
     * @param Permission $advancedPermission
     */
    public function addAdvancedPermission(Permission $advancedPermission)
    {
        $this->advancedPermissions->add($advancedPermission);
    }

    /**
     * @param Permission $advancedPermission
     * @return bool Whether or not the element was successfully removed
     */
    public function removeAdvancedPermission(Permission $advancedPermission)
    {
        return $this->advancedPermissions->removeElement($advancedPermission);
    }

    /**
     * @param CustomView[] $customViews
     */
    public function setCustomViews(array $customViews)
    {
        $this->customViews->clear();
        foreach ($customViews as $customView) {
            $this->customViews->add($customView);
        }
    }

    /**
     * @return CustomView[]
     */
    public function getCustomViews()
    {
        return $this->customViews;
    }

    /**
     * @param CustomView $customView
     */
    public function addCustomView(CustomView $customView)
    {
        $this->customViews->add($customView);
    }

    /**
     * @param CustomView $customView
     * @return bool Whether or not the element was successfully removed
     */
    public function removeCustomView(CustomView $customView)
    {
        return $this->customViews->removeElement($customView);
    }

    //Implements sleep so that it does not serialize $knownPermissions
    function __sleep()
    {
        return array('id', 'name', 'permissions', 'advancedPermissions');
    }
}
