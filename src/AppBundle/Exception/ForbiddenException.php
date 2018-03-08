<?php


namespace AppBundle\Exception;

use AppBundle\Exception\Utils\MessageException;
use AppBundle\Exception\Utils\PermissionException;

class ForbiddenException extends \Exception implements MessageException, PermissionException
{

}