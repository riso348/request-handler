<?php namespace TyrionCMS\RequestHandler;

use ArrayIterator;
use TyrionCMS\RequestHandler\Exceptions\RequestFindException;

class RequestItem extends ArrayIterator
{

    private string $method;
    private string $key;
    private mixed $value = null;
    private array $values;
    private array $hiddenParams = array();
    private bool $preventXSS = true;

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method
     */
    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey(string $key): void
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
    public function setHiddenParams(array $hiddenParams): void
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

    public function getValues(array $preventXssExceptions = array()): array
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


    /**
     * @param string $key
     * @param $nullable
     * @return RequestItem|null
     * @throws RequestFindException
     */
    public function find(string $key, bool $nullable = false): ?RequestItem
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
        } elseif ($nullable === true) {
            return new RequestItem();
        }
        throw new RequestFindException("Unable to find request key: " . $key);
    }

    /**
     * @param mixed $value
     */
    public function setValue(mixed $value): void
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

    public function delete(): void
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
            if (is_string($value)) {
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
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('Core.Encoding', 'UTF-8');
            $config->set('Cache.SerializerPath', $_SERVER['DOCUMENT_ROOT'] . "/cache");
            $antiXss = new \HTMLPurifier($config);
            return $antiXss->purify($value);
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

    public function setPreventXss(bool $value): RequestItem
    {
        $this->preventXSS = $value;
        return $this;
    }

}