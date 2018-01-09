<?php

namespace AppBundle\Exception;


use AppBundle\Exception\Utils\MessageException;
use AppBundle\Exception\Utils\NotFoundException;

class ElementNotFoundException extends \Exception implements MessageException, NotFoundException
{

}