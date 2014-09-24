<?php

namespace Pum\Core\Extension\Search\Behavior;

use Pum\Core\BehaviorInterface;
use Pum\Core\Context\ObjectBuildContext;
use Pum\Core\Context\ObjectContext;
use Pum\Core\Extension\Search\SearchEngine;
use Pum\Core\Extension\Util\Namer;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Doctrine\ORM\Mapping\ClassMetadata;

class SearchableBehavior implements BehaviorInterface
{
    public function mapDoctrineObject(ObjectContext $context, ClassMetadata $metadata)
    {
        return;
    }

    public function buildObject(ObjectBuildContext $context)
    {
        $cb = $context->getClassBuilder();
        $searchFields = $context->getObject()->getSearchFields();

        $getValuesBody = 'return array(';
        $getWeightsBody = 'return array(';
        $language = new ExpressionLanguage();
        foreach ($searchFields as $searchField) {
            $name = Namer::toLowercase($searchField->getName());
            $getValuesBody.= "'$name' => ".$language->compile($searchField->getExpression(), array('this')).',';
            $getWeightsBody.= "'$name' => ".$searchField->getWeight().',';
        }
        $getValuesBody .= ');';
        $getWeightsBody .= ');';

        $indexName = SearchEngine::getIndexName($context->getProject()->getName());
        $typeName = SearchEngine::getTypeName($context->getObject()->getName());

        $cb->addImplements('Pum\Core\Extension\Search\SearchableInterface');
        $cb->createMethod('getSearchValues', null, $getValuesBody);
        $cb->createMethod('getSearchWeights', null, $getWeightsBody);
        $cb->createMethod('getSearchIndexName', null, 'return "'.$indexName.'";');
        $cb->createMethod('getSearchTypeName', null, 'return "'.$typeName.'";');
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'seo';
    }
}
