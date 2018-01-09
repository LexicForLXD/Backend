<?php

namespace AppBundle\EventListener;


use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Exception\Utils\NotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class ElementNotFoundExceptionListener
{
    public function onKernelException(GetResponseForExceptionEvent $event){
        $exception = $event->getException();

        if(!$exception instanceof ElementNotFoundException){
            return;
        }

        $code = $exception instanceof NotFoundException ? 404 : 500;

        $responseData = [
            'error' => [
                'code' => $code,
                'message' => $exception->getMessage()
            ]
        ];

        $event->setResponse(new JsonResponse($responseData, Response::HTTP_SERVICE_UNAVAILABLE));
    }
}