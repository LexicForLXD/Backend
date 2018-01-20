<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Container;
use AppBundle\Entity\ContainerStatus;
use AppBundle\Entity\Host;
use AppBundle\Entity\HostStatus;
use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Exception\WrongInputException;
use AppBundle\Service\LxdApi\MonitoringApi;
use AppBundle\Service\Nagios\Pnp4NagiosApi;
use AppBundle\Service\SSH\HostSSH;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as OAS;
use Symfony\Component\VarDumper\VarDumper;

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
     *     tags={"container-monitoring"},
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
     * Get the content of a single Logfile
     *
     * @Route("/monitoring/logs/containers/{containerId}/{logfile}", name="get_single_log_from_container", methods={"GET"})
     * @param $containerId
     * @param $logfile
     * @param MonitoringApi $api
     * @throws ElementNotFoundException
     * @throws WrongInputException
     * @return Response
     *
     * @OAS\Get(path="/monitoring/logs/containers/{containerId}/{logfile}",
     *     tags={"container-monitoring"},
     *     @OAS\Parameter(
     *      description="ID of the Container",
     *      in="path",
     *      name="containerId",
     *      required=true,
     *          @OAS\Schema(
     *              type="integer"
     *          ),
     *      ),
     *     @OAS\Parameter(
     *      description="Filename of the Logfile, including type",
     *      in="path",
     *      name="logfile",
     *      required=true,
     *          @OAS\Schema(
     *              type="string"
     *          ),
     *      ),
     *      @OAS\Response(
     *          response=200,
     *          description="Returns the File content as text/plain",
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
     * Get the content of a single Logfile
     *
     * @Route("/monitoring/logs/hosts/{hostId}/{logpath}", name="get_single_log_from_host", methods={"GET"})
     * @param $hostId
     * @param $logpath
     * @param HostSSH $ssh
     * @return Response
     * @throws ElementNotFoundException
     *
     *@OAS\Get(path="/monitoring/logs/hosts/{hostId}/{logpath}",
     *     tags={"host-monitoring"},
     *     @OAS\Parameter(
     *      description="ID of the Host",
     *      in="path",
     *      name="hostId",
     *      required=true,
     *          @OAS\Schema(
     *              type="integer"
     *          ),
     *      ),
     *     @OAS\Parameter(
     *      description="Path of the logfile on the Host",
     *      in="path",
     *      name="logpath",
     *      required=true,
     *          @OAS\Schema(
     *              type="string"
     *          ),
     *      ),
     *      @OAS\Response(
     *          response=200,
     *          description="Returns the File content as text/plain",
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No Host for the id found",
     *      ),
     *     @OAS\Response(
     *          response=400,
     *          description="Error getting the logfile",
     *      ),
     * )
     */
    public function getSingleLogfileFromHost($hostId, $logpath, HostSSH $ssh){
        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

        if (!$host) {
            throw new ElementNotFoundException(
                'No Host for ID '.$hostId.' found'
            );
        }
        $result = $ssh->getLogFileFromHost($host, $logpath);

        //TODO Add 400 Error

        $response = new Response();
        $response->setContent($result->body);
        $response->headers->set('Content-Type', 'text/plain');
        return $response;
    }

    /**
     * Get all ContainerStatus Nagios configurations for a Container
     * @Route("/monitoring/checks/containers/{containerId}", name="get_status_check_container", methods={"GET"})
     * @throws ElementNotFoundException
     *
     * @OAS\Get(path="/monitoring/checks/containers/{containerId}",
     *     tags={"container-monitoring"},
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
     *          description="Returns the ContainerStatus",
     *          @OAS\JsonContent(ref="#/components/schemas/containerStatus"),
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No Container for the id found or no StatusCheck for Container found",
     *      ),
     * )
     */
    public function getStatusChecksContainer($containerId){
        $container = $this->getDoctrine()->getRepository(Container::class)->find($containerId);

        if (!$container) {
            throw new ElementNotFoundException(
                'No Container for ID '.$containerId.' found'
            );
        }

        $containerStatuses = $container->getStatuses();

        if ($containerStatuses->count() == 0) {
            throw new ElementNotFoundException(
                'No ContainerStatuses for Container with ID '.$containerId.' found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($containerStatuses, 'json');
        return new Response($response);
    }

    /**
     * Create ContainerStatus Nagios configuration
     *
     * @Route("/monitoring/checks/containers/{containerId}", name="create_container_status", methods={"POST"})
     * @param $containerId
     * @param Request $request
     * @return JsonResponse|Response
     * @throws ElementNotFoundException
     *
     * @OAS\Post(path="/monitoring/checks/containers/{containerId}",
     *     tags={"container-monitoring"},
     *     @OAS\Parameter(
     *      description="ID of the Container",
     *      in="path",
     *      name="containerId",
     *      required=true,
     *          @OAS\Schema(
     *              type="integer"
     *          ),
     *      ),
     *     @OAS\Parameter(
     *      in="body",
     *      name="body",
     *      required=true,
     *      @OAS\Schema(
     *      @OAS\Property(
     *          property="nagiosEnabled",
     *          type="boolean",
     *          example=true,
     *      ),
     *      @OAS\Property(
     *          property="nagiosName",
     *          type="string",
     *          example="ContainerWebServer1",
     *      ),
     *      @OAS\Property(
     *          property="nagiosUrl",
     *          type="string",
     *          example="https://nagios.example.com/pnp4nagios/",
     *      ),
     *      @OAS\Property(
     *          property="checkName",
     *          type="string",
     *          example="check_http",
     *      ),
     *      @OAS\Property(
     *          property="sourceNumber",
     *          type="string",
     *          example=0,
     *      ),
     *      ),
     *      ),
     *      @OAS\Response(
     *          response=201,
     *          description="Returns the ContainerStatus",
     *          @OAS\JsonContent(ref="#/components/schemas/containerStatus"),
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No Container for the id found",
     *      ),
     * )
     */
    public function createStatusCheckForContainer($containerId, Request $request) {
        $container = $this->getDoctrine()->getRepository(Container::class)->find($containerId);

        if (!$container) {
            throw new ElementNotFoundException(
                'No Container for ID '.$containerId.' found'
            );
        }

        $em = $this->getDoctrine()->getManager();

        $containerStatus = new ContainerStatus();

        if($request->request->has('nagiosEnabled')) {
            $containerStatus->setNagiosEnabled($request->request->get('nagiosEnabled'));
        }

        if($request->request->has('nagiosName')) {
            $containerStatus->setNagiosName($request->request->get('nagiosName'));
        }

        if($request->request->has('checkName')) {
            $containerStatus->setCheckName($request->request->get('checkName'));
        }

        if($request->request->has('sourceNumber')) {
            $containerStatus->setSourceNumber($request->request->get('sourceNumber'));
        }

        if($request->request->has('nagiosUrl')) {
            $containerStatus->setNagiosUrl($request->request->get('nagiosUrl'));
        }

        //Validation
        if ($errorArray = $this->validation($containerStatus)) {
            return new JsonResponse(['errors' => $errorArray], 400);
        }

        $container->addStatus($containerStatus);
        $em->persist($containerStatus);
        $em->persist($container);
        $em->flush();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($containerStatus, 'json');
        return new Response($response, Response::HTTP_CREATED);
    }

    /**
     * Edit ContainerStatus Nagios configuration
     *
     * @Route("/monitoring/checks/{checkId}/containers", name="configure_container_status", methods={"PUT"})
     * @param $checkId
     * @param Request $request
     * @return JsonResponse|Response
     * @throws ElementNotFoundException
     * @OAS\Put(path="/monitoring/checks/{checkId}/containers",
     *     tags={"container-monitoring"},
     * @OAS\Parameter(
     *      description="ID of the ContainerStatus",
     *      in="path",
     *      name="checkId",
     *      required=true,
     *          @OAS\Schema(
     *              type="integer"
     *          ),
     *      ),
     * @OAS\Parameter(
     *      in="body",
     *      name="body",
     *      required=true,
     *      @OAS\Schema(
     *      @OAS\Property(
     *          property="nagiosEnabled",
     *          type="boolean",
     *          example=true,
     *      ),
     *      @OAS\Property(
     *          property="nagiosName",
     *          type="string",
     *          example="ContainerWebServer1",
     *      ),
     *      @OAS\Property(
     *          property="nagiosUrl",
     *          type="string",
     *          example="https://nagios.example.com/pnp4nagios/",
     *      ),
     *      @OAS\Property(
     *          property="checkName",
     *          type="string",
     *          example="check_http",
     *      ),
     *      @OAS\Property(
     *          property="sourceNumber",
     *          type="string",
     *          example=0,
     *      ),
     *      ),
     *      ),
     * @OAS\Response(
     *          response=200,
     *          description="Returns the ContainerStatus",
     *          @OAS\JsonContent(ref="#/components/schemas/containerStatus"),
     *      ),
     * @OAS\Response(
     *          response=404,
     *          description="No ContainerStatus for the id found",
     *      ),
     * )
     */
    public function configureStatusCheckForContainer($checkId, Request $request) {
        $containerStatus = $this->getDoctrine()->getRepository(ContainerStatus::class)->find($checkId);

        if (!$containerStatus) {
            throw new ElementNotFoundException(
                'No ContainerStatus for ID '.$checkId.' found'
            );
        }

        $em = $this->getDoctrine()->getManager();

        if($request->request->has('nagiosEnabled')) {
            $containerStatus->setNagiosEnabled($request->request->get('nagiosEnabled'));
        }

        if($request->request->has('nagiosName')) {
            $containerStatus->setNagiosName($request->request->get('nagiosName'));
        }

        if($request->request->has('checkName')) {
            $containerStatus->setCheckName($request->request->get('checkName'));
        }

        if($request->request->has('sourceNumber')) {
            $containerStatus->setSourceNumber($request->request->get('sourceNumber'));
        }

        if($request->request->has('nagiosUrl')) {
            $containerStatus->setNagiosUrl($request->request->get('nagiosUrl'));
        }

        //Validation
        if ($errorArray = $this->validation($containerStatus)) {
            return new JsonResponse(['errors' => $errorArray], 400);
        }

        $em->persist($containerStatus);
        $em->flush();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($containerStatus, 'json');
        return new Response($response);
    }

    /**
     * Receive a Nagios stats graph by ContainerStatus
     *
     * @Route("/monitoring/checks/{checkId}/containers/graph", name="get_pnp4nagios_container", methods={"GET"})
     * @param $checkId
     * @param Pnp4NagiosApi $api
     * @return Response
     * @throws ElementNotFoundException
     * @throws WrongInputException
     *
     * @OAS\Get(path="/monitoring/checks/{checkId}/containers/graph?timerange={timerange}",
     *     tags={"container-monitoring"},
     *     @OAS\Parameter(
     *      description="ID of the ContainerStatus",
     *      in="path",
     *      name="checkId",
     *      required=true,
     *          @OAS\Schema(
     *              type="integer"
     *          ),
     *      ),
     *      @OAS\Parameter(
     *      description="Define a custom timerange for the output graph, examples : -1day or -3weeks or -1year or yesterday",
     *      in="path",
     *      name="timerange",
     *      required=false,
     *          @OAS\Schema(
     *              type="string"
     *          ),
     *      ),
     *      @OAS\Response(
     *          response=200,
     *          description="Returns the Nagios stats graph as png with mime-type image/png",
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No ContainerStatus with ID found - returns json error with mime-type application/json",
     *      ),
     *
     *     @OAS\Response(
     *          response=400,
     *          description="Error getting the Nagios graph image - returns json error with mime-type application/json",
     *      ),
     * )
     */
    public function getPnp4NagiosImageForContainer($checkId, Request $request, Pnp4NagiosApi $api){
        $containerStatus = $this->getDoctrine()->getRepository(ContainerStatus::class)->find($checkId);

        if (!$containerStatus) {
            throw new ElementNotFoundException(
                'No ContainerStatus with ID '.$checkId.' found'
            );
        }

        $timerange = $request->query->get('timerange');

        if(!$timerange){
            $timerange = '-1day';
        }

        $result = $api->getNagiosImageForContainerTimerange($containerStatus, $timerange);

        if($result->code != 200){
            throw new WrongInputException("Error loading the Graph - HTTP-Code ".$result->code);
        }

        $response = new Response();
        $response->setContent($result->body);
        $response->headers->set('Content-Type', 'image/png');
        return $response;
    }

    /**
     * Get all HostStatus Nagios configurations for a Host
     * @Route("/monitoring/checks/hosts/{hostId}", name="get_status_checks_host", methods={"GET"})
     * @param $hostId
     * @return Response
     * @throws ElementNotFoundException
     *
     * @OAS\Get(path="/monitoring/checks/hosts/{hostId}",
     *     tags={"host-monitoring"},
     *     @OAS\Parameter(
     *      description="ID of the Host",
     *      in="path",
     *      name="hostId",
     *      required=true,
     *          @OAS\Schema(
     *              type="integer"
     *          ),
     *      ),
     *      @OAS\Response(
     *          response=200,
     *          description="Returns the HostStatuses",
     *          @OAS\JsonContent(ref="#/components/schemas/hostStatus"),
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No Container for the id found or no HostStatus configuration for Host found",
     *      ),
     * )
     */
    public function getStatusChecksHost($hostId){
        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

        if (!$host) {
            throw new ElementNotFoundException(
                'No Host for ID '.$hostId.' found'
            );
        }

        $hostStatuses = $host->getStatuses();

        if ($hostStatuses->count() == 0) {
            throw new ElementNotFoundException(
                'No HostStatuses for Host with ID '.$hostId.' found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($hostStatuses, 'json');
        return new Response($response);
    }

    /**
     * Receive a Nagios stats graph by HostStatus
     *
     * @Route("/monitoring/checks/{checkId}/hosts/graph", name="get_pnp4nagios_host", methods={"GET"})
     * @param $checkId
     * @param Pnp4NagiosApi $api
     * @return Response
     * @throws ElementNotFoundException
     * @throws WrongInputException
     *
     * @OAS\Get(path="/monitoring/checks/{checkId}/hosts/graph?timerange={timerange}",
     *     tags={"host-monitoring"},
     *     @OAS\Parameter(
     *      description="ID of the HostStatus",
     *      in="path",
     *      name="checkId",
     *      required=true,
     *          @OAS\Schema(
     *              type="integer"
     *          ),
     *      ),
     *      @OAS\Parameter(
     *      description="Define a custom timerange for the output graph, examples : -1day or -3weeks or -1year or yesterday",
     *      in="path",
     *      name="timerange",
     *      required=false,
     *          @OAS\Schema(
     *              type="string"
     *          ),
     *      ),
     *      @OAS\Response(
     *          response=200,
     *          description="Returns the Nagios stats graph as png with mime-type image/png",
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No HostStatus with ID found - returns json error with mime-type application/json",
     *      ),
     *
     *     @OAS\Response(
     *          response=400,
     *          description="Error getting the Nagios graph image - returns json error with mime-type application/json",
     *      ),
     * )
     */
    public function getPnp4NagiosImageForHost($checkId, Request $request, Pnp4NagiosApi $api){
        $hostStatus = $this->getDoctrine()->getRepository(HostStatus::class)->find($checkId);

        if (!$hostStatus) {
            throw new ElementNotFoundException(
                'No HostStatus with ID '.$checkId.' found'
            );
        }

        $timerange = $request->query->get('timerange');

        if(!$timerange){
            $timerange = '-1day';
        }

        $result = $api->getNagiosImageForHostTimerange($hostStatus, $timerange);

        if($result->code != 200){
            throw new WrongInputException("Error loading the Graph - HTTP-Code ".$result->code);
        }

        $response = new Response();
        $response->setContent($result->body);
        $response->headers->set('Content-Type', 'image/png');
        return $response;
    }

    /**
     * Create HostStatus Nagios configuration
     *
     * @Route("/monitoring/checks/hosts/{hostId}", name="create_host_status", methods={"POST"})
     * @param $hostId
     * @param Request $request
     * @return JsonResponse|Response
     * @throws ElementNotFoundException
     *
     * @OAS\Post(path="/monitoring/checks/hosts/{hostId}",
     *     tags={"host-monitoring"},
     *     @OAS\Parameter(
     *      description="ID of the Host",
     *      in="path",
     *      name="hostId",
     *      required=true,
     *          @OAS\Schema(
     *              type="integer"
     *          ),
     *      ),
     *     @OAS\Parameter(
     *      in="body",
     *      name="body",
     *      required=true,
     *      @OAS\Schema(
     *      @OAS\Property(
     *          property="nagiosEnabled",
     *          type="boolean",
     *          example=true,
     *      ),
     *      @OAS\Property(
     *          property="nagiosName",
     *          type="string",
     *          example="LXC-Host1",
     *      ),
     *      @OAS\Property(
     *          property="nagiosUrl",
     *          type="string",
     *          example="https://nagios.example.com/pnp4nagios/",
     *      ),
     *      @OAS\Property(
     *          property="checkName",
     *          type="string",
     *          example="check_http",
     *      ),
     *      @OAS\Property(
     *          property="sourceNumber",
     *          type="string",
     *          example=0,
     *      ),
     *      ),
     *      ),
     *      @OAS\Response(
     *          response=201,
     *          description="Returns the HostStatus",
     *          @OAS\JsonContent(ref="#/components/schemas/hostStatus"),
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No Host for the id found",
     *      ),
     * )
     */
    public function createStatusCheckForHost($hostId, Request $request) {
        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

        if (!$host) {
            throw new ElementNotFoundException(
                'No Host for ID '.$hostId.' found'
            );
        }

        $em = $this->getDoctrine()->getManager();

        $hostStatus = new HostStatus();

        if($request->request->has('nagiosEnabled')) {
            $hostStatus->setNagiosEnabled($request->request->get('nagiosEnabled'));
        }

        if($request->request->has('nagiosName')) {
            $hostStatus->setNagiosName($request->request->get('nagiosName'));
        }

        if($request->request->has('checkName')) {
            $hostStatus->setCheckName($request->request->get('checkName'));
        }

        if($request->request->has('sourceNumber')) {
            $hostStatus->setSourceNumber($request->request->get('sourceNumber'));
        }

        if($request->request->has('nagiosUrl')) {
            $hostStatus->setNagiosUrl($request->request->get('nagiosUrl'));
        }

        //Validation
        if ($errorArray = $this->validation($hostStatus)) {
            return new JsonResponse(['errors' => $errorArray], 400);
        }

        $host->addStatus($hostStatus);
        $em->persist($hostStatus);
        $em->persist($host);
        $em->flush();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($hostStatus, 'json');
        return new Response($response, Response::HTTP_CREATED);
    }

    /**
     * Edit HostStatus Nagios configuration
     *
     * @Route("/monitoring/checks/{checkId}/hosts", name="configure_host_check", methods={"PUT"})
     * @param $checkId
     * @param Request $request
     * @return Response
     * @throws ElementNotFoundException
     *
     * @OAS\Put(path="/monitoring/checks/{checkId}/hosts",
     *     tags={"host-monitoring"},
     *      @OAS\Parameter(
     *      description="ID of the HostStatus",
     *      in="path",
     *      name="checkId",
     *      required=true,
     *          @OAS\Schema(
     *              type="integer"
     *          ),
     *      ),
     *      @OAS\Parameter(
     *      in="body",
     *      name="body",
     *      required=true,
     *      @OAS\Schema(
     *      @OAS\Property(
     *          property="nagiosEnabled",
     *          type="boolean",
     *          example=true,
     *      ),
     *      @OAS\Property(
     *          property="nagiosName",
     *          type="string",
     *          example="LXC-Host1",
     *      ),
     *      @OAS\Property(
     *          property="nagiosUrl",
     *          type="string",
     *          example="https://nagios.example.com/pnp4nagios/",
     *      ),
     *      @OAS\Property(
     *          property="checkName",
     *          type="string",
     *          example="check_http",
     *      ),
     *      @OAS\Property(
     *          property="sourceNumber",
     *          type="string",
     *          example=0,
     *      ),
     *      ),
     *      ),
     * @OAS\Response(
     *          response=200,
     *          description="Returns the HostStatus",
     *          @OAS\JsonContent(ref="#/components/schemas/hostStatus"),
     *      ),
     * @OAS\Response(
     *          response=404,
     *          description="No HostStatus for the id found",
     *      ),
     * )
     */
    public function configureStatusCheckForHost($checkId, Request $request) {
        $hostStatus = $this->getDoctrine()->getRepository(HostStatus::class)->find($checkId);

        if (!$hostStatus) {
            throw new ElementNotFoundException(
                'No HostStatus for ID '.$checkId.' found'
            );
        }

        $em = $this->getDoctrine()->getManager();

        if($request->request->has('nagiosEnabled')) {
            $hostStatus->setNagiosEnabled($request->request->get('nagiosEnabled'));
        }

        if($request->request->has('nagiosName')) {
            $hostStatus->setNagiosName($request->request->get('nagiosName'));
        }

        if($request->request->has('checkName')) {
            $hostStatus->setCheckName($request->request->get('checkName'));
        }

        if($request->request->has('sourceNumber')) {
            $hostStatus->setSourceNumber($request->request->get('sourceNumber'));
        }

        if($request->request->has('nagiosUrl')) {
            $hostStatus->setNagiosUrl($request->request->get('nagiosUrl'));
        }

        //Validation
        if ($errorArray = $this->validation($hostStatus)) {
            return new JsonResponse(['errors' => $errorArray], 400);
        }

        $em->persist($hostStatus);
        $em->flush();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($hostStatus, 'json');
        return new Response($response);
    }

    /**
     * Delete HostStatus Nagios configuration
     *
     * @Route("/monitoring/checks/{checkId}/hosts", name="delete_host_check", methods={"DELETE"})
     * @param $checkId
     * @return Response
     * @throws ElementNotFoundException
     *
     * @OAS\Delete(path="/monitoring/checks/{checkId}/hosts",
     *     tags={"host-monitoring"},
     *     @OAS\Parameter(
     *      description="ID of the HostStatus",
     *      in="path",
     *      name="checkId",
     *      required=true,
     *          @OAS\Schema(
     *              type="integer"
     *          ),
     *      ),
     *      @OAS\Response(
     *          response=204,
     *          description="HostStatus deleted",
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No HostStatus for the provided id found",
     *      ),
     * )
     */
    public function deleteHostStatus($checkId){
        $hostStatus = $this->getDoctrine()->getRepository(HostStatus::class)->find($checkId);

        if (!$hostStatus) {
            throw new ElementNotFoundException(
                'No HostStatus for ID '.$checkId.' found'
            );
        }

        $host = $hostStatus->getHost();
        $host->removeStatus($hostStatus);

        $em = $this->getDoctrine()->getManager();

        $em->persist($host);
        $em->remove($hostStatus);

        $em->flush();

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * @param String $logfileUrl
     * @return null|string|string[]
     */
    private function parseLogfileUrlToLogfileName(String $logfileUrl){
        return preg_replace('"\/1.0\/containers\/.*\/logs\/"', '', $logfileUrl);
    }

    private function validation($object)
    {
        $validator = $this->get('validator');
        $errors = $validator->validate($object);

        if (count($errors) > 0) {
            $errorArray = array();
            foreach ($errors as $error) {
                $errorArray[$error->getPropertyPath()] = $error->getMessage();
            }
            return $errorArray;
        }
        return false;
    }
}
