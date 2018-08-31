<?php

namespace AppBundle\EventListener;


use AppBundle\Exception\LxdApiException;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class LxdExceptionListener
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if (!$exception instanceof LxdApiException) {
            return;
        }


        $responseData = [
            'error' => [
                'code' => $exception->getCode(),
                'message' => $exception->getMessage()
            ]
        ];


        $event->setResponse(new JsonResponse($responseData, 502));
    }
}