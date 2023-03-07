<?php namespace TyrionCMS\RequestHandler;

use ArrayIterator;
use TyrionCMS\RequestHandler\Exceptions\RequestFindException;

class RequestItem extends ArrayIterator
{

    private $method;
    private $key;
    private $value = null;
    private $values;
    private $hiddenParams = array();
    private $preventXSS = true;

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param mixed $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param mixed $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return array
     */
    public function getHiddenParams(): array
    {
        return $this->hiddenParams;
    }

    /**
     * @param array $hiddenParams
     */
    public function setHiddenParams(array $hiddenParams)
    {
        $this->hiddenParams = $hiddenParams;
    }


    /**
     * @param null $value
     * @return mixed
     */
    public function getValue($value = null)
    {
        if ($this->value == null && $value !== null) {
            return $this->preventXSS ? $this->preventXSSValue($value) : $value;
        }
        return $this->preventXSS ? $this->preventXSSValue($this->value) : $this->value;
    }

    public function getValues(array $preventXssExceptions = array())
    {
        if (is_array($this->values)) {
            return $this->preventXSS ? $this->preventXSSValue($this->values, $preventXssExceptions) : $this->values;
        }

        $values = array();
        while ($this->valid()) {
            $values[$this->current()->getKey()] = $this->current()->setPreventXss($this->preventXSS && !in_array($this->current()->getKey(), $preventXssExceptions))->getValue();
            $this->next();
        }
        $this->values = $values;

        $this->rewind();
        return $this->preventXSS ? $this->preventXSSValue($values, $preventXssExceptions) : $values;
    }

    public function getClearValues(array $ignoredParams = array(), array $preventXssExceptions = array())
    {
        $params = $this->getValues($preventXssExceptions);
        foreach ($params as $key => $param) {
            if (in_array($key, $this->getHiddenParams()) || in_array($key, $ignoredParams)) {
                unset($params[$key]);
            }
        }
        return $this->preventXSS ? $this->preventXSSValue($params, $preventXssExceptions) : $params;
    }


    public function find(string $key, $nullable = false): ?RequestItem
    {
        $found = false;
        while ($this->valid()) {
            if ($this->current()->getKey() == $key) {
                $found = $this->current();
                break;
            }
            $this->next();
        }
        $this->rewind();
        if ($found instanceof RequestItem) {
            $found->setPreventXss($this->preventXSS);
            return $found;
        } elseif ($nullable) {
            return new RequestItem();
        }
        throw new RequestFindException("Unable to find request key: " . $key);
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        switch ($this->getMethod()) {
            case "request":
                $_REQUEST[$this->getKey()] = $value;
                break;
            case "get":
                $_GET[$this->getKey()] = $value;
                break;
            case "post":
                $_POST[$this->getKey()] = $value;
                break;
        }
        $this->value = $value;
    }

    public function delete()
    {
        switch ($this->getMethod()) {
            case "request":
                unset($_REQUEST[$this->getKey()]);
                break;
            case "get":
                unset($_GET[$this->getKey()]);
                break;
            case "post":
                unset($_POST[$this->getKey()]);
                break;
        }
    }

    public function generateURL(): string
    {
        $url = "?";
        foreach ($this->getValues() as $key => $value) {
            if(is_string($value)) {
                if (in_array($key, $this->getHiddenParams())) {
                    continue;
                }
                $url .= "$key=";
                $url .= urlencode($value);
                $url .= "&";
            }
        }
        $url = rtrim($url, '&');
        return $url;
    }

    private function preventXSSValue($value, array $preventXssExceptions = array())
    {
        $preventString = function (string $value) {
            $antiXss = new \voku\helper\AntiXSS();
            return $antiXss->xss_clean($value);
        };
        if (is_string($value) && !is_numeric($value) && !in_array($value, [true, false], true)) {
            $value = strip_tags($value);
        } else if (is_array($value)) {
            array_walk_recursive(
                $value,
                function (&$value, $key) use ($preventString, $preventXssExceptions) {
                    if (is_scalar($value) && !is_numeric($value) && !in_array($value, [true, false], true)) {
                        $value = !in_array($key, $preventXssExceptions) ? $preventString($value) : $value;
                    }
                }
            );
        }
        return $value;
    }

    public function setPreventXss(bool $value)
    {
        $this->preventXSS = $value;
        return $this;
    }

}