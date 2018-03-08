<?php

namespace AppBundle\EventListener;

use AppBundle\Exception\ForbiddenException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class ForbiddenExceptionListener
{
    public function onKernelException(GetResponseForExceptionEvent $event){
        $exception = $event->getException();

        if(!$exception instanceof ForbiddenException){
            return;
        }

        $code = $exception instanceof ForbiddenException ? 403 : 500;

        $responseData = [
            'error' => [
                'code' => $code,
                'message' => $exception->getMessage()
            ]
        ];

        $event->setResponse(new JsonResponse($responseData, $code));
    }
}