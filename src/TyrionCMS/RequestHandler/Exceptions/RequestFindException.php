<?php
/**
 * Created by PhpStorm.
 * User: richard
 * Date: 31.5.2017
 * Time: 11:02
 */

namespace TyrionCMS\RequestHandler;


class RequestFindException extends \Exception
{

    public function __construct($message, $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}