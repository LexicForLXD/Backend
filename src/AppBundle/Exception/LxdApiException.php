<?php


namespace AppBundle\Exception;

use AppBundle\Exception\Utils\MessageException;
use Throwable;
use AppBundle\Exception\Utils\CodeException;

class LxdApiException extends \Exception implements MessageException, CodeException
{
    protected $message;
    protected $code;

    public function __construct( $message, int $code)
    {
        $this->message = $message;
        $this->code = $code;
    }
}