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

        if($this->value == null && $value !== null){
            return $value;
        }
        return $this->value;
    }

    public function getValues()
    {
        if(is_array($this->values)){
            return $this->values;
        }

        $values = array();
        while( $this->valid() )
        {
            $values[$this->current()->getKey()] = $this->current()->getValue();
            $this->next();
        }
        $this->values = $values;
        return $values;
    }

    public function getClearValues(array $ignoredParams = array())
    {
        $params = $this->getValues();
        foreach ($params as $key => $param){
            if(in_array($key, $this->getHiddenParams()) || in_array($key , $ignoredParams)){
                unset($params[$key]);
            }
        }
        return $params;
    }


    public function find(String $key, $nullable = false): ? RequestItem
    {
        $found = false;
        while( $this->valid() )
        {
            if($this->current()->getKey() == $key){
                $found = $this->current();
                break;
            }
            $this->next();
        }
        $this->rewind();
        if($found){
            return $found;
        }elseif($nullable){
            return new RequestItem();
        }
        throw new RequestFindException("Unable to find request key: ".$key);
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

    public function generateURL():string
    {
        $url = "?";
        foreach($this->getValues() as $key => $value){
            if (in_array($key , $this->getHiddenParams())){
                continue;
            }
            $url .= "$key=";
            $url .= urlencode($value);
            $url .= "&";
        }
        $url = rtrim($url , '&');
        return $url;
    }

}