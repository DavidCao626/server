<?php


namespace App\EofficeApp\Elastic\Foundation;


class SearchParams extends Params
{
    const FROM = 0;
    const SIZE = 10;

    const SORT_DEFAULT = 'default';
    const SORT_BY_CREATE_TIME = 'createTime';

    /**
     * @return string
     */
    public function getKeyword()
    {
        return $this->getParam('keyword');
    }

    /**
     * @return bool
     */
    public function hasKeyword()
    {
        return $this->hasParam('keyword') && '' !== $this->getParam('keyword');
    }

    /**
     * @param string $keyword
     *
     * @return $this
     */
    public function setKeyword($keyword)
    {
        return $this->setParam('keyword', trim((string) $keyword));
    }

    /**
     * @return int
     */
    public function getFrom()
    {
        if (!$this->hasParam('from')) {
            return self::FROM;
        }

        return $this->getParam('from');
    }

    /**
     * @param int $from
     *
     * @return $this
     */
    public function setFrom($from)
    {
        return $this->setParam('from', (int) $from);
    }

    /**
     * @return string
     */
    public function getSort()
    {
        if (!$this->hasParam('sort')) {
            return self::SORT_DEFAULT;
        }

        return $this->getParam('sort');
    }


    /**
     * @return string[]
     */
    public function getOrders()
    {
        return $this->getParam('orders');
    }

    /**
     * @param $field
     * @param $order
     *
     * @return $this
     */
    public function addOrder($field, $order = 'ASC')
    {
        if (!$this->hasParam('orders')) {
            $this->setParam('orders', []);
        }

        $this->_params['orders'][$field] = ['order' => $order];

        return $this;
    }

    /**
     * @return bool
     */
    public function hasOrders()
    {
        return $this->hasParam('orders') && !empty($this->getParam('orders'));
    }

    /**
     * @param string[] $orders
     *
     * @return $this
     */
    public function setOrders(array $orders = [])
    {
        return $this->setParam('orders', $orders);
    }

    /**
     * Sets highlight arguments for the query.
     *
     * @param array $highlightArgs Set all highlight arguments
     *
     * @return $this
     *
     */
    public function setHighlight(array $highlightArgs)
    {
        return $this->setParam('highlight', $highlightArgs);
    }

    /**
     * Adds a highlight argument.
     *
     * @param mixed $highlight Add highlight argument
     *
     * @return $this
     *
     */
    public function addHighlight($highlight)
    {
        return $this->addParam('highlight', $highlight);
    }


    /**
     * @return int
     */
    public function getSize()
    {
        if (!$this->hasParam('size')) {
            return self::SIZE;
        }

        return $this->getParam('size');
    }

    /**
     * @param int $size
     *
     * @return $this
     */
    public function setSize($size)
    {
        return $this->setParam('size', (int) $size);
    }

    /**
     * @return bool
     */
    public function getExplain()
    {
        if (!$this->hasParam('explain')) {
            return false;
        }

        return $this->getParam('explain');
    }

    /**
     * @param bool $explain
     *
     * @return $this
     */
    public function setExplain($explain)
    {
        return $this->setParam('explain', (bool) $explain);
    }

    /**
     * Sets the _source field to be returned with every hit.
     *
     * @param array|bool $params Fields to be returned or false to disable source
     *
     * @return $this
     */
    public function setSource($params)
    {
        return $this->setParam('_source', $params);
    }

    /**
     * get the _source field.
     *
     * @param array|bool $params Fields to be returned or false to disable source
     *
     * @return array
     */
    public function getSource()
    {
        if (isset($this->_params['_source'])) {
            return $this->_params['_source'];
        }

        return [];
    }

    /**
     * @param string[] $filters
     *
     * @return $this
     */
    public function setFilters(array $filters = [])
    {
        return $this->setParam('filters', $filters);
    }

    /**
     * @return string[]
     */
    public function getFilters()
    {
        return $this->getParam('filters');
    }

    /**
     * @param string $source
     *
     * @return $this
     */
    public function addFilters($filter)
    {
        if (!$this->hasParam('filters')) {
            $this->setParam('filters', []);
        }

        $this->_params['filters'][] = $filter;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMinShouldMatch()
    {
        if (!$this->hasParam('minShouldMatch')) {
            return false;
        }

        return $this->getParam('minShouldMatch');
    }

    /**
     * @param bool|int|string $minShouldMatch
     *
     * @return $this
     */
    public function setMinShouldMatch($minShouldMatch)
    {
        return $this->setParam('minShouldMatch', $minShouldMatch);
    }
}