<?php


namespace App\EofficeApp\Elastic\Foundation;


class Params
{
    /**
     * Params.
     *
     * @var array
     */
    protected $_params = [];

    /**
     * Sets (overwrites) the value at the given key.
     *
     * @param string $key   Key to set
     * @param mixed  $value Key Value
     *
     * @return $this
     */
    public function setParam($key, $value)
    {
        $this->_params[$key] = $value;

        return $this;
    }

    /**
     * Sets (overwrites) all params of this object.
     *
     * @param array $params Parameter list
     *
     * @return $this
     */
    public function setParams(array $params)
    {
        $this->_params = $params;

        return $this;
    }

    /**
     * Adds a param to the list.
     *
     * This function can be used to add an array of params
     *
     * @param string $key   Param key
     * @param mixed  $value Value to set
     *
     * @return $this
     */
    public function addParam($key, $value)
    {
        if (null != $key) {
            $this->_params[$key][] = $value;
        } else {
            $this->_params = $value;
        }

        return $this;
    }

    /**
     * Returns a specific param.
     *
     * @param string $key Key to return
     *
     * @return mixed Key value
     */
    public function getParam($key)
    {
        if (!$this->hasParam($key)) {
            return '';
        }

        return $this->_params[$key];
    }

    /**
     * Test if a param is set.
     *
     * @param string $key Key to test
     *
     * @return bool True if the param is set, false otherwise
     */
    public function hasParam($key)
    {
        return isset($this->_params[$key]);
    }

    /**
     * Returns the params array.
     *
     * @return array Params
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Convert self to array
     */
    public function convertArray($type = false)
    {
        if (empty($this->_params)) {
            $this->_params = new \stdClass();
        }

        $arr = [];

        foreach ($this->_params as $key => $value) {
            $arr[$key] = $value;
        }

        return $type ? [$type => $arr] : $arr;
    }
}