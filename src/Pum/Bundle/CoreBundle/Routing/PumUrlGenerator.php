<?php

namespace Pum\Bundle\CoreBundle\Routing;

use Pum\Core\Extension\Routing\RoutableInterface;
use Pum\Core\Extension\Routing\RoutingTable;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

class PumUrlGenerator implements UrlGeneratorInterface
{
    protected $urlGenerator;
    protected $routeName;
    protected $routingTable;

    public function __construct(UrlGeneratorInterface $urlGenerator, RoutingTable $routingTable, $routeName = 'pum_object')
    {
        $this->urlGenerator = $urlGenerator;
        $this->routingTable = $routingTable;
        $this->routeName = $routeName;
    }

    /**
     * @return RoutingTable
     */
    public function getRoutingTable()
    {
        return $this->routingTable;
    }

    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        if (!$name instanceof RoutableInterface && !is_array($name)) {
            return $this->urlGenerator->generate($name, $parameters, $referenceType);
        }

        $objects = $name instanceof RoutableInterface ? array($name) : $name;
        $seoKeys = array();
        foreach ($objects as $object) {
            if (!$object instanceof RoutableInterface) {
                continue;
            }

            $seoKeys[] = $object->getSeoKey();
        }

        error_log(var_export($seoKeys, true));
        $key = implode('/', $seoKeys);

        $parameters = array_merge($parameters, array('key' => $key));

        return $this->urlGenerator->generate($this->routeName, $parameters, $referenceType);
    }

    public function getContext()
    {
        return $this->urlGenerator->getContext();
    }

    public function setContext(RequestContext $context)
    {
        return $this->urlGenerator->setContext($context);
    }
}