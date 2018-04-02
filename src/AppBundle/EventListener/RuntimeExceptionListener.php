<?php

namespace AppBundle\EventListener;


use Httpful\Exception\ConnectionErrorException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class RuntimeExceptionListener
{
    public function onKernelException(GetResponseForExceptionEvent $event){
        $exception = $event->getException();

        if(!$exception instanceof \RuntimeException){
            return;
        }

        $responseData = [
            'error' => [
                'code' => 503,
                'message' => $exception->getMessage()
            ]
        ];

        $event->setResponse(new JsonResponse($responseData, Response::HTTP_SERVICE_UNAVAILABLE));
    }
}