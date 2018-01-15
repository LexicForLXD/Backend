<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Container;
use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Exception\WrongInputException;
use AppBundle\Service\LxdApi\MonitoringApi;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as OAS;

class MonitoringController extends Controller
{
    /**
     * List all available Logfiles for a Container
     *
     * @Route("/monitoring/logs/containers/{containerId}", name="list_all_logfiles_from_container", methods={"GET"})
     * @throws ElementNotFoundException
     * @throws \Httpful\Exception\ConnectionErrorException
     * @throws WrongInputException
     *
     * @OAS\Get(path="/monitoring/logs/containers/{containerId}",
     *     tags={"monitoring"},
     *     @OAS\Parameter(
     *      description="ID of the Container",
     *      in="path",
     *      name="containerId",
     *      required=true,
     *          @OAS\Schema(
     *              type="integer"
     *          ),
     *      ),
     *      @OAS\Response(
     *          response=200,
     *          description="List of all available logfiles for the Container as an array under the attribute logs",
     *          @OAS\Schema(
     *              type="array"
     *          ),
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No Container for the id found",
     *      ),
     *     @OAS\Response(
     *          response=400,
     *          description="Returns an LXD Error 'LXD-Error - {LXD-Response}' ",
     *      ),
     * )
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
     * @Route("/monitoring/logs/containers/{containerId}/{logfile}", name="get_single_log_from_container", methods={"GET"})
     * @param $containerId
     * @param $logfile
     * @param MonitoringApi $api
     * @throws ElementNotFoundException
     * @throws WrongInputException
     * @return Response
     */
    public function getSingleLogfileFromContainer($containerId, $logfile, MonitoringApi $api){
        $container = $this->getDoctrine()->getRepository(Container::class)->find($containerId);

        if (!$container) {
            throw new ElementNotFoundException(
                'No Container for ID '.$containerId.' found'
            );
        }
        $result = $api->getSingleLogfileFromContainer($container, $logfile);

        if($result->code != 200){
            $result = json_decode($result->body);
            throw new WrongInputException("LXD-Error - ".$result->error);
        }

        $response = new Response();
        $response->setContent($result->body);
        $response->headers->set('Content-Type', 'text/plain');
        return $response;
    }

    /**
     * @param String $logfileUrl
     * @return null|string|string[]
     */
    private function parseLogfileUrlToLogfileName(String $logfileUrl){
        return preg_replace('"\/1.0\/containers\/.*\/logs\/"', '', $logfileUrl);
    }
}
