<?php

namespace AppBundle\EventListener;


use AppBundle\Exception\LxdApiException;
use AppBundle\Exception\LxdOpApiException;
use AppBundle\Exception\Utils\OpException;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class LxdExceptionListener
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if (!$exception instanceof LxdApiException && !$exception instanceof LxdOpApiException) {
            return;
        }


        $responseData = [
            'error' => [
                'code' => $exception->getCode(),
                'message' => $exception->getMessage()
            ]
        ];

        if ($exception instanceof OpException) {
            $responseData["error"]["operation"] = $exception->getOperation();
        }


        $event->setResponse(new JsonResponse($responseData, 502));
    }
}