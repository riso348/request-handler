<?php namespace TyrionCMS\RequestHandler;

final class Request
{
    private $requestItemHiddenParams;

    public function __construct(array $requestItemHiddenParams = null)
    {
        $this->requestItemHiddenParams = $requestItemHiddenParams;
    }

    /**
     * @param null $key
     * @return \TyrionCMS\RequestHandler\RequestItem|\TyrionCMS\RequestHandler\RequestItem[]
     */
    public function get($key = null)
    {
        if ($key == null) {
            $gets = array();
            if (is_array($_GET)) {
                foreach ($_GET as $key => $value) {
                    $value = $this->parseDataType($value);
                    $gets[] = $this->createRequestItem('get', $key, $value);;
                }
            }
            return $this->createFinalRequestItem($gets);
        } else {
            $value = (isset($_GET[$key])) ? $_GET[$key] : null;
            $value = $this->parseDataType($value);
            return $this->createRequestItem('get', $key, $value);
        }
    }

    private function parseDataType($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->checkValue($value);
            }
            return $data;
        } else {
            return $this->checkValue($data);
        }
    }

    private function checkValue($value)
    {
        if (is_numeric($value) && (int)$value[0] === 0) {
            return $value;
        } else if (is_numeric($value) && strpos($value, '.') === false && strpos($value, ',') === false && strlen((int)$value) === strlen($value)) {
            return (int)$value;
        } elseif (is_numeric($value) && (strpos($value, ',') !== false || strpos($value, '.') !== false) && strlen((float)$value) === strlen($value)) {
            return (float)str_replace(',', '.', $value);
        } elseif (is_numeric(str_replace(',', '.', $value)) && strlen((float)str_replace(',', '.', $value)) === strlen(str_replace(',', '.', $value))) {
            return (float)str_replace(',', '.', $value);
        } elseif ($value === 'true') {
            return true;
        } elseif ($value === 'false') {
            return false;
        } elseif ($value === 'null') {
            return null;
        } else {
            return $value;
        }
    }

    private function createRequestItem(string $method, string $key, $value): RequestItem
    {
        $requestItem = new RequestItem();
        $requestItem->setMethod($method);
        $requestItem->setKey($key);
        $requestItem->setValue($value);
        return $requestItem;
    }

    /**
     * @param null $key
     * @return \TyrionCMS\RequestHandler\RequestItem|\TyrionCMS\RequestHandler\RequestItem[]
     */
    public function post($key = null)
    {
        if ($key == null) {
            $posts = array();
            if (is_array($_POST)) {
                foreach ($_POST as $key => $value) {
                    $value = $this->parseDataType($value);
                    $posts[] = $this->createRequestItem('post', $key, $value);
                }
            }
            return $this->createFinalRequestItem($posts);
        } else {
            $value = (isset($_POST[$key])) ? $_POST[$key] : null;
            return $this->createRequestItem('post', $key, $value);
        }
    }

    /**
     * @param null $key
     * @return \TyrionCMS\RequestHandler\RequestItem|\TyrionCMS\RequestHandler\RequestItem[]
     */
    public function request($key = null)
    {
        if ($key == null) {
            $requests = array();
            if (is_array($_REQUEST)) {
                foreach ($_REQUEST as $key => $value) {
                    $value = $this->parseDataType($value);
                    $requests[] = $this->createRequestItem('request', $key, $value);
                }
            }
            return $this->createFinalRequestItem($requests);
        } else {
            $value = (isset($_REQUEST[$key])) ? $_REQUEST[$key] : null;
            return $this->createRequestItem('request', $key, $value);
        }
    }

    private function createFinalRequestItem($data): RequestItem
    {
        $requestItem = new RequestItem($data);
        if ($this->requestItemHiddenParams) {
            $requestItem->setHiddenParams($data);
        }
        return $requestItem;
    }


}
