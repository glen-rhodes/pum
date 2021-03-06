<?php

namespace Pum\Core\Extension\Search\Query;

class Terms extends Query
{
    const QUERY_KEY = 'terms';

    private $field;
    private $terms              = array();
    private $minimumShouldMatch = null;
    private $matchAll           = false;

    public function __construct($field = null)
    {
        $this->field = $field;
    }

    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }

    public function setMinimumShouldMatch($minimumShouldMatch)
    {
        $this->minimumShouldMatch = $minimumShouldMatch;

        return $this;
    }

    public function addTerm($term)
    {
        $this->terms[] = $term;

        return $this;
    }

    public function addTerms($terms)
    {
        $this->terms = array_merge((array)$terms, $this->terms);

        return $this;
    }

    public function matchAll()
    {
        $this->matchAll = true;

        return $this;
    }

    public function getArray()
    {
        if (null === $this->field) {
            throw new \RuntimeException('You must set field to the query, null given');
        }

        if ($this->matchAll && count($this->terms) > 1) {
            $this->minimumShouldMatch = count($this->terms);
        }

        $result =  array(
            $this->field  => $this->terms,
        );

        if (null !== $this->minimumShouldMatch) {
            $result['minimum_should_match'] = $this->minimumShouldMatch;
        }

        return array(
            $this::QUERY_KEY => $result
        );
    }
}
