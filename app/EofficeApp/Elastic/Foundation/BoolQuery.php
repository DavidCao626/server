<?php


namespace App\EofficeApp\Elastic\Foundation;


class BoolQuery extends Params
{
    /**
     * Add should part to query.
     *
     * @param array $args
     *
     * @return $this
     */
    public function addShould($args)
    {
        return $this->_addQuery('should', $args);
    }

    /**
     * Set should part to query.
     *
     * @param $args
     *
     * @return $this
     */
    public function setShould($args)
    {
        return $this->setParam('should', $args);
    }

    /**
     * Add must part to query.
     *
     * @param array $args Must query
     *
     * @return $this
     */
    public function addMust($args)
    {
        return $this->_addQuery('must', $args);
    }

    /**
     * Set must part to query.
     *
     * @param $args
     *
     * @return $this
     */
    public function setMust($args)
    {
        return $this->setParam('must', $args);
    }

    /**
     * Add must not part to query.
     *
     * @param array $args Must not query
     *
     * @return $this
     */
    public function addMustNot($args)
    {
        return $this->_addQuery('must_not', $args);
    }

    /**
     * Set must not part to query.
     *
     * @param $args
     *
     * @return $this
     */
    public function setMustNot($args)
    {
        return $this->setParam('must_not', $args);
    }

    /**
     * Sets the filter.
     *
     * @param array
     *
     * @return $this
     */
    public function addFilter($filter)
    {
        return $this->addParam('filter', $filter);
    }

    /**
     * Set must not part to query.
     *
     * @param array $filter
     *
     * @return $this
     */
    public function setFilter($filter)
    {
        return $this->setParam('filter', $filter);
    }

    /**
     * Adds a query to the current object.
     *
     * @param string
     *
     * @return $this
     */
    protected function _addQuery($type, $args)
    {
        if (!is_array($args)) {
            $this->addParam($type, []);
        }

        return $this->addParam($type, $args);
    }

    /**
     * Sets boost value of this query.
     *
     * @param float $boost Boost value
     *
     * @return $this
     */
    public function setBoost($boost)
    {
        return $this->setParam('boost', $boost);
    }


    /**
     * Sets the minimum number of should clauses to match.
     *
     * @param int|string $minimum Minimum value
     *
     * @return $this
     */
    public function setMinimumShouldMatch($minimum)
    {
        return $this->setParam('minimum_should_match', $minimum);
    }
}