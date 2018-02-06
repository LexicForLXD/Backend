<?php

namespace AppBundle\EventListener;


use AppBundle\Exception\WrongInputException;
use AppBundle\Exception\Utils\UserInputException;
use AppBundle\Exception\WrongInputExceptionArray;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class WrongInputExceptionArrayListener
{
    public function onKernelException(GetResponseForExceptionEvent $event){
        $exception = $event->getException();

        if(!$exception instanceof WrongInputExceptionArray){
            return;
        }

        $code = $exception instanceof UserInputException ? 400 : 500;

        $responseData = [
            'error' => [
                'code' => $code,
                'message' => $exception->getMessage()
            ]
        ];

        $event->setResponse(new Response(json_encode($responseData), $code));
    }
}