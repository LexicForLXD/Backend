<?php
namespace AppBundle\Controller;


use AppBundle\Entity\ImageAlias;
use AppBundle\Event\ContainerCreationEvent;
use AppBundle\Event\ContainerDeleteEvent;
use AppBundle\Exception\WrongInputException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Doctrine\ORM\EntityManagerInterface;

use AppBundle\Service\LxdApi\ContainerApi;
use AppBundle\Service\LxdApi\OperationsRelayApi;

use AppBundle\Entity\Container;
use AppBundle\Entity\ContainerStatus;
use AppBundle\Entity\Host;
use AppBundle\Entity\Profile;
use AppBundle\Entity\Image;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as OAS;



class ContainerController extends Controller
{
    /**
     * Get all saved Containers
     *
     * @Route("/containers", name="containers_all", methods={"GET"})
     *
     * @OAS\Get(path="/containers",
     *     tags={"containers"},
     *      @OAS\Response(
     *          response=200,
     *          description="List of all containers",
     *          @OAS\JsonContent(ref="#/components/schemas/container"),
     *          @OAS\Schema(
     *              type="array"
     *          ),
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No containers found",
     *      ),
     * )
     */
    public function indexAction()
    {
        $containers = $this->getDoctrine()->getRepository(Container::class)->findAllJoinedToHost();



        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($containers, 'json');
        return new Response($response);
    }

    /**
     * Get all Containers from one host
     *
     * @Route("/hosts/{hostId}/containers", name="containers_from_host", methods={"GET"})
     *
     * @OAS\Get(path="/hosts/{hostId}/containers?fresh={fresh}",
     *  tags={"containers"},
     *  @OAS\Response(
     *      response=200,
     *      description="List of containers from one host",
     *      @OAS\JsonContent(ref="#/components/schemas/container"),
     *  ),
     *
     *  @OAS\Parameter(
     *      description="ID of the Host",
     *      in="path",
     *      name="hostId",
     *      required=true,
     *      @OAS\Schema(
     *          type="integer"
     *      ),
     *  ),
     *
     *  @OAS\Parameter(
     *      description="true = force collect new data and return them, false = return cached data from the database",
     *      in="query",
     *      name="fresh",
     *      @OAS\Schema(
     *          type="boolean"
     *      ),
     *  ),
     *)
     * @param Request $request
     * @param int $hostId
     * @return Response
     */
    public function listFromHostAction(Request $request, int $hostId, ContainerApi $api)
    {

        $fresh = $request->query->get('fresh');


        if ($fresh == 'true') {
            $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

            if (!$host) {
                throw $this->createNotFoundException(
                    'No host found for id ' . $hostId
                );
            }






            $result = $api->list($host);

            //TODO in DB aktualisieren

            return new Response($result->body);
        } else {
            $containers = $this->getDoctrine()->getRepository(Container::class)->findAllByHostJoinedToHost($hostId);

            if (!$containers) {
                throw $this->createNotFoundException(
                    'No containers found'
                );
            }
            $serializer = $this->get('jms_serializer');
            $response = $serializer->serialize($containers, 'json');
            return new Response($response);
        }



    }


    /**
     * Create a new Container on a specific Host
     *
     * @Route("/hosts/{hostId}/containers", name="containers_store", methods={"POST"})
     *
     * @OAS\Post(path="/hosts/{hostId}/containers",
     *  tags={"containers"},
     *
     *  @OAS\Parameter(
     *      description="Gibt die Art an, wie der Container erstellt wird. (image, migration, copy, none) Default ist none",
     *      in="query",
     *      name="type",
     *      @OAS\Schema(
     *          type="string"
     *      ),
     *  ),
     *
     *
     *
     * @OAS\Parameter(
     *  description="Parameters for the new Container with fingerprint",
     *  in="body",
     *  name="bodyFingerprint",
     *  @OAS\Schema(
     *      @OAS\Property(
     *          property="name",
     *          type="string"
     *      ),
     *      @OAS\Property(
     *          property="architecture",
     *          type="string"
     *      ),
     *      @OAS\Property(
     *          property="profiles",
     *          type="array",
     *      ),
     *      @OAS\Property(
     *          property="ephemeral",
     *          type="bool"
     *      ),
     *      @OAS\Property(
     *          property="config",
     *          type="string",
     *      ),
     *      @OAS\Property(
     *          property="devices",
     *          type="string"
     *      ),
     *      @OAS\Property(
     *          property="fingerprint",
     *          type="string"
     *      )
     *  ),
     * ),
     *
     * @OAS\Parameter(
     *  description="Parameters for the new Container with alias",
     *  in="body",
     *  name="bodyAlias",
     *  @OAS\Schema(
     *      @OAS\Property(
     *          property="name",
     *          type="string"
     *      ),
     *      @OAS\Property(
     *          property="architecture",
     *          type="string"
     *      ),
     *      @OAS\Property(
     *          property="profiles",
     *          type="array",
     *      ),
     *      @OAS\Property(
     *          property="ephemeral",
     *          type="bool"
     *      ),
     *      @OAS\Property(
     *          property="config",
     *          type="string",
     *      ),
     *      @OAS\Property(
     *          property="devices",
     *          type="string"
     *      ),
     *      @OAS\Property(
     *          property="alias",
     *          type="string"
     *      )
     *  ),
     * ),
     *
     * @OAS\Parameter(
     *  description="Parameters for the new Container with migration",
     *  in="body",
     *  name="bodyMigration",
     *  @OAS\Schema(
     *      @OAS\Property(
     *          property="name",
     *          type="string"
     *      ),
     *      @OAS\Property(
     *          property="architecture",
     *          type="string"
     *      ),
     *      @OAS\Property(
     *          property="profiles",
     *          type="array",
     *      ),
     *      @OAS\Property(
     *          property="ephemeral",
     *          type="bool"
     *      ),
     *      @OAS\Property(
     *          property="config",
     *          type="string",
     *      ),
     *      @OAS\Property(
     *          property="devices",
     *          type="string"
     *      ),
     *      @OAS\Property(
     *          property="oldContainerId",
     *          type="int"
     *      ),
     *      @OAS\Property(
     *          property="containerOnly",
     *          type="bool"
     *      ),
     *      @OAS\Property(
     *          property="live",
     *          type="bool"
     *      )
     *  ),
     * ),
     *
     *  @OAS\Parameter(
     *  description="Parameters for copying a Container",
     *  in="body",
     *  name="bodyCopy",
     *  @OAS\Schema(
     *      @OAS\Property(
     *          property="name",
     *          type="string"
     *      ),
     *      @OAS\Property(
     *          property="architecture",
     *          type="string"
     *      ),
     *      @OAS\Property(
     *          property="profiles",
     *          type="array",
     *      ),
     *      @OAS\Property(
     *          property="ephemeral",
     *          type="bool"
     *      ),
     *      @OAS\Property(
     *          property="config",
     *          type="string",
     *      ),
     *      @OAS\Property(
     *          property="devices",
     *          type="string"
     *      ),
     *      @OAS\Property(
     *          property="oldContainerId",
     *          type="int"
     *      ),
     *      @OAS\Property(
     *          property="containerOnly",
     *          type="bool"
     *      )
     *  ),
     * ),
     *
     * @OAS\Parameter(
     *  description="ID of the Host the container should be created on",
     *  in="path",
     *  name="hostId",
     *  required=true,
     *  @OAS\Schema(
     *     type="integer"
     *  ),
     * ),
     *
     * @OAS\Response(
     *  description="The Container was successfully created",
     *  response=201
     * ),
     *
     * @OAS\Response(
     *     description="The Host was not found",
     *     response=404
     * ),
     *
     * @OAS\Response(
     *     description="The input was wrong",
     *     response=400
     * )
     * )
     *
     * @param Request $request
     * @param int $hostId
     * @param EntityManagerInterface $em
     * @param OperationsRelayApi $relayApi
     * @param ContainerApi $api
     * @return Response
     * @throws WrongInputException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function storeAction(Request $request, int $hostId, EntityManagerInterface $em, OperationsRelayApi $relayApi, ContainerApi $api)
    {
        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

        if (!$host) {
            throw $this->createNotFoundException(
                'No host found for id ' . $hostId
            );
        }

        $type = $request->query->get('type');

        $profiles = $this->getDoctrine()->getRepository(Profile::class)->findBy(['id' => $request->get("profiles")]);

        $profileNames = array();

        $profileController = new ProfileController();

        foreach ($profiles as $profile){
            $profileNames[] = $profile->getName();
        }

        switch ($type) {
            case 'image':

                $data = [
                    "name" => $request->request->get("name"),
                    "architecture" => $request->get("architecture", 'x86_64'),
                    "profiles" => $profileNames,
                    "ephemeral" => $request->get("ephemeral", false),
                    "config" => $request->get("config"),
                    "devices" => $request->get("devices"),
                    "source" => []
                ];

                if($request->request->has("fingerprint")){
                    $image = $this->getDoctrine()->getRepository(Image::class)->findBy(["fingerprint" => $request->get("fingerprint")]);
                    $data["source"] = [
                        "type" => "image",
                        "fingerprint" => $image->getFingerPrint()
                    ];
                }

                if($request->request->has("alias") && !$request->request->has("imageServer")){
                    $imageAlias = $this->getDoctrine()->getRepository(ImageAlias::class)->findOneBy(["name" => $request->get("alias")]);
                    $image = $imageAlias->getImage();
                    $data["source"] = [
                        "type" => "image",
                        "fingerprint" => $image->getFingerPrint()
                    ];
                }


//
//                        "type" => "image",
//                        "mode" => "pull",
//                        "server" => $request->get("imageServer") ? : 'https://images.linuxcontainers.org:8443',
//                        "protocol" => $request->get("protocol") ? : 'lxd',
//                        "alias" => $request->get("alias"),
//                        "fingerprint" => $request->get("fingerprint")
//                    ]
//                ];


                $container = new Container();
                $container->setHost($host);
                $container->setIpv4($request->get("ipv4"));

                $container->setName($request->get("name"));
                $container->setSettings($data);

                foreach ($profiles as $profile){
                    $profileController->enableProfile($profile, $container);
                }


                break;
            case 'migration':
                $oldContainer = $this->getDoctrine()->getRepository(Container::class)->find($request->get("oldContainerId"));

                $data = [
                    "name" => $request->get("name"),
                    "migration" => true,
                    "live" => $request->get("live", false)

                ];
                $oldHost = $oldContainer->getHost();
                $pushResult = $api->migrate($oldHost, $oldContainer, $data);


                $data = [
                    "name" => $request->get("name"),
                    "profiles" => $profileNames,
                    "ephemeral" => $request->get("ephemeral", false),
                    "config" => $request->get("config"),
                    "devices" => $request->get("devices"),
                    "source" => [
                        "type" => "migration",
                        "mode" => "pull",
                        "operation" => $oldHost->getUri().$pushResult->body->operation,
                        "base-image" => $oldContainer->getImage()->getFingerprint(),
                        "container_only" => $request->get("containerOnly", true),
                        "live" => $request->get("live", false),
                        "secrets" =>  $pushResult->body->metadata->metadata

                    ]
                ];

                $container = new Container();
                $container->setHost($host);
                $container->setIpv4($request->get("ipv4"));

                $container->setName($request->get("name"));
                $container->setSettings($data);


                foreach ($profiles as $profile){
                    $profileController->enableProfile($profile, $container);
                }

                break;
            case 'copy':
                $oldContainer = $this->getDoctrine()->getRepository(Container::class)->find($request->get("oldContainerId"));

                $data = [
                    "name" => $request->get("name"),
                    "profiles" => $profileNames,
                    "ephemeral" => $request->get("ephemeral", false),
                    "config" => $request->get("config"),
                    "devices" => $request->get("devices"),
                    "source" => [
                        "type" => "copy",
                        "container_only" => $request->get("containerOnly", true),
                        "source" => $oldContainer->getName()
                    ]
                ];

                $container = new Container();
                $container->setHost($host);
                $container->setName($request->get("name"));
                $container->setIpv4($request->get("ipv4"));
                $container->setSettings($data);

                foreach ($profiles as $profile){
                    $profileController->enableProfile($profile, $container);
                }


                break;
            default:
                return new JsonResponse(["message" => "none"]);
        }


        if($errorArray = $this->validation($container))
        {
            throw new WrongInputException(json_encode($errorArray));
        }

        $container->setState('creating');

        $em->persist($container);
        $em->flush();

        $result = $api->create($host, $data);

        $dispatcher = $this->get('sb_event_queue');

        $dispatcher->on(ContainerCreationEvent::class, date('Y-m-d H:i:s'), $result->body->metadata->id, $host, $container->getId());



        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($container, 'json');
        return new Response($response, Response::HTTP_CREATED);

    }


    /**
     * Get a Container by containerID
     *
     * @Route("/containers/{containerId}", name="containers_show", methods={"GET"})
     *
     * @OAS\Get(path="/containers/{containerId}",
     * tags={"containers"},
     * @OAS\Parameter(
     *  description="ID of the Container",
     *  in="path",
     *  name="containerId",
     *  required=true,
     *   @OAS\Schema(
     *         type="integer"
     *   ),
     * ),
     *
     * @OAS\Response(
     *      response=200,
     *      description="Returns the informationen of a single Container",
     *      @OAS\JsonContent(ref="#/components/schemas/container"),
     * ),
     *)
     * @param Request $request
     * @param int $containerId
     * @param ContainerApi $api
     * @return Object|Response
     */
    public function showSingleAction(Request $request, int $containerId, ContainerApi $api)
    {
        $fresh = $request->query->get('fresh');

        $container = $this->getDoctrine()->getRepository(Container::class)->findOneByIdJoinedToHost($containerId);

        if (!$container) {
            throw $this->createNotFoundException(
                'No container found for id ' . $containerId
            );
        }

        if ($fresh == 'true') {

            $result = $api->show($container->host, $container->name);

            //TODO in DB aktualisieren

            return new Response($result->body);
        }
        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($container, 'json');
        return new Response($response);

    }


    /**
     * Deletes a Container by containerID
     *
     * @Route("/containers/{containerId}", name="containers_delete", methods={"DELETE"})
     *
     *SWG\Delete(path="/containers/{containerId}",
     * tags={"containers"},
     * SWG\Parameter(
     *  description="ID des Containers",
     *  format="int64",
     *  in="path",
     *  name="containerId",
     *  required=true,
     *  type="integer"
     * ),
     *
     * SWG\Response(
     *      response=200,
     *      description="show a single container"
     * ),
     *)
     * @param int $containerId
     * @param EntityManagerInterface $em
     * @param ContainerApi $api
     * @return JsonResponse
     */
    public function deleteAction(int $containerId, EntityManagerInterface $em, ContainerApi $api)
    {
        $container = $this->getDoctrine()->getRepository(Container::class)->findOneByIdJoinedToHost($containerId);

        if (!$container) {
            throw $this->createNotFoundException(
                'No container found for id ' . $containerId
            );
        }

        $result = $api->remove($container->getHost(), $container->getName());


        $dispatcher = $this->get('sb_event_queue');
        $dispatcher->on(ContainerDeleteEvent::class, date('Y-m-d H:i:s'), $result->body->metadata->id, $container->getHost(), $container->getId());

        return $this->json(['message' => 'Deletion is ongoing'], 200);
    }


    /**
     * Validates a Container Object and returns array with errors.
     * @param Container $object
     * @return array|bool
     */
    private function validation(Container $object)
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