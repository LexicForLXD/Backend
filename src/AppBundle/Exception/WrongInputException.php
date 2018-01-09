<?php


namespace AppBundle\Exception;

use AppBundle\Exception\Utils\MessageException;
use AppBundle\Exception\Utils\UserInputException;

class WrongInputException extends \Exception implements MessageException, UserInputException
{

}