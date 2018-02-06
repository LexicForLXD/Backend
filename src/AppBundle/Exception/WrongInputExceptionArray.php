<?php


namespace AppBundle\Exception;

use AppBundle\Exception\Utils\MessageException;
use AppBundle\Exception\Utils\UserInputException;
use Throwable;

class WrongInputExceptionArray extends \Exception implements MessageException, UserInputException
{
    protected $message;

    public function __construct(array $message)
    {
        $this->message = $message;
    }
}