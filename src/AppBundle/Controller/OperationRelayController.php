<?php

namespace AppBundle\Controller;


use AppBundle\Entity\Host;
use AppBundle\Service\LxdApi\OperationsRelayApi;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OperationRelayController extends Controller
{
    /**
     * @Route("/operations/{hostId}/{operationsId}", name="relay_operations", methods={"GET"})
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function getOperationsFromHost($hostId, $operationsId, OperationsRelayApi $api){
        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

        if (!$host) {
            throw $this->createNotFoundException(
                'No Host with id '.$hostId.' found'
            );
        }

        $response = $api->getOperationFromHost($host, $operationsId);

        return new Response($response);
    }
}