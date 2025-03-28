<?php namespace TyrionCMS\RequestHandler;


final class Request
{
    private array|null $requestItemHiddenParams;

    public function __construct(?array $requestItemHiddenParams = null)
    {
        $this->requestItemHiddenParams = $requestItemHiddenParams;
    }

    /**
     * @param null $key
     * @return RequestItem
     */
    public function get($key = null): RequestItem
    {
        if ($key == null) {
            $gets = array();
            if (is_array($_GET)) {
                foreach ($_GET as $key => $value) {
                    $value = $this->parseDataType($value);
                    $gets[] = $this->createRequestItem('get', $key, $value);
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
                $value = $this->checkValue($value);
                $data[$key] = $value;
            }
            return $data;
        } else {
            return $this->checkValue($data);
        }
    }

    private function checkValue($value)
    {
        if (is_numeric($value) && ((string)$value)[0] === '0') {
            return $value;
        } else if (is_numeric($value) && !str_contains(strval($value), '.') && !str_contains(strval($value), ',') && strlen(strval(intval($value))) === strlen((string)$value)) {
            return (int)$value;
        } elseif (is_numeric($value) && (str_contains(strval($value), ',') || str_contains(strval($value), '.')) && strlen(strval(floatval($value))) === strlen((string)$value)) {
            return (float)str_replace(',', '.', strval($value));
        } elseif (is_scalar($value) && is_numeric(str_replace(',', '.', strval($value))) && strlen((string)floatval(str_replace(',', '.', $value))) === strlen(str_replace(',', '.', $value))) {
            return (float)str_replace(',', '.', strval($value));
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
     * @param string|null $key
     * @return RequestItem
     */
    public function post(?string $key = null): RequestItem
    {
        if ($key == null) {
            $posts = array();
            if (is_array($_POST) && count($_POST) > 0) {
                foreach ($_POST as $key => $value) {
                    $value = $this->parseDataType($value);
                    $posts[] = $this->createRequestItem('post', $key, $value);
                }
            } elseif ($_SERVER['CONTENT_TYPE'] ?? "" === 'application/json') {
                $jsonData = @json_decode(file_get_contents('php://input'), true);
                if (is_array($jsonData) && count($jsonData) > 0) {
                    foreach ($jsonData as $key => $value) {
                        $value = $this->parseDataType($value);
                        $posts[] = $this->createRequestItem('post', $key, $value);
                    }
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
     * @return RequestItem
     */
    public function request($key = null): RequestItem
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

    private function createFinalRequestItem(array $data): RequestItem
    {
        $requestItem = new RequestItem($data);
        if ($this->requestItemHiddenParams) {
            $requestItem->setHiddenParams($this->requestItemHiddenParams);
        }
        return $requestItem;
    }

    /**
     * @param string $key
     * @return string|null
     */
    public static function getHeaderValue(string $key): ?string
    {
        $header = null;
        if (isset($_SERVER[$key])) {
            $header = trim($_SERVER[$key]);
        } else if (isset($_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $key))])) {
            $header = trim($_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $key))]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders[$key])) {
                $header = trim($requestHeaders[$key]);
            }
        }
        return $header;
    }

}
