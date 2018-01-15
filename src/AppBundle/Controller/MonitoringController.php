<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Container;
use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Exception\WrongInputException;
use AppBundle\Service\LxdApi\MonitoringApi;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class MonitoringController extends Controller
{
    /**
     * @Route("/monitoring/logs/containers/{containerId}", name="list_all_logfiles_from_container", methods={"GET"})
     * @throws ElementNotFoundException
     * @throws \Httpful\Exception\ConnectionErrorException
     * @throws WrongInputException
     */
    public function listAllLogfilesForContainer($containerId, MonitoringApi $api){
        $container = $this->getDoctrine()->getRepository(Container::class)->find($containerId);

        if (!$container) {
            throw new ElementNotFoundException(
                'No Container for ID '.$containerId.' found'
            );
        }

        $result = $api->getListOfLogfilesFromContainer($container);

        if($result->code != 200){
            throw new WrongInputException("LXD-Error - ".$result->body->error);
        }
        if($result->body->status_code != 200){
            throw new WrongInputException("LXD-Error - ".$result->body->error);
        }

        //Parse logfile names
        $logfileArray = array();
        for($i=0; $i<sizeof($result->body->metadata); $i++){
            $logfileArray[] = $this->parseLogfileUrlToLogfileName($result->body->metadata[$i]);
        }

        $response = ['logs' => $logfileArray];
        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($response, 'json');
        return new Response($response);
    }

    /**
     * @param String $logfileUrl
     * @return null|string|string[]
     */
    private function parseLogfileUrlToLogfileName(String $logfileUrl){
        return preg_replace('"\/1.0\/containers\/.*\/logs\/"', '', $logfileUrl);
    }
}
