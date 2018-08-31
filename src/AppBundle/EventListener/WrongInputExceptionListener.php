<?php

namespace AppBundle\EventListener;


use AppBundle\Exception\WrongInputException;
use AppBundle\Exception\WrongInputExceptionArray;

use AppBundle\Exception\Utils\UserInputException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class WrongInputExceptionListener
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if (!$exception instanceof WrongInputException && !$exception instanceof WrongInputExceptionArray) {
            return;
        }

        $code = $exception instanceof UserInputException ? 400 : 500;

        $responseData = [
            'error' => [
                'code' => $code,
                'message' => $exception->getMessage()
            ]
        ];


        $event->setResponse(new JsonResponse($responseData, $code));
    }
}