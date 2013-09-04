<?php

namespace Pum\Bundle\CoreBundle\Form\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Pum\Core\Config\MysqlConfig as Config;

class ConfigTypeListener implements EventSubscriberInterface
{
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'onSetData',
            FormEvents::SUBMIT       => 'onSubmitData',
        );
    }

    public function onSetData(FormEvent $event)
    {
        $event->setData($this->config->all());

        $event->getForm()->add('save', 'submit', array(
            'label' => 'Save settings'
        ));
    }

    public function onSubmitData(FormEvent $event)
    {
        $data = $event->getData();

        unset($data['save']);
        foreach($data as $key => $value) {
            $value = (is_array($value)) ? array_values($value) : $value;
            $this->config->set($key, $value);
        }
        
        $this->config->flush();
    }
}
