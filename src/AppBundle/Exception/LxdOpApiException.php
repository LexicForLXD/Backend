<?php


namespace AppBundle\Exception;

use AppBundle\Exception\Utils\MessageException;
use Throwable;
use AppBundle\Exception\Utils\CodeException;
use AppBundle\Exception\Utils\OpException;

class LxdOpApiException extends \Exception implements MessageException, CodeException, OpException
{
    protected $message;
    protected $code;
    protected $operation;

    public function __construct($message, int $code, string $operation)
    {
        $this->message = $message;
        $this->code = $code;
        $this->operation = $operation;
    }

    

    /**
     * Get the value of operation
     */ 
    public function getOperation()
    {
        return $this->operation;
    }
}