<?php
namespace AppBundle\Controller;


use AppBundle\Entity\ImageAlias;
use AppBundle\Event\ContainerCreationEvent;
use AppBundle\Event\ContainerDeleteEvent;
use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Exception\WrongInputException;
use AppBundle\Service\LxdApi\OperationApi;
use AppBundle\Service\Profile\ProfileManagerApi;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Doctrine\ORM\EntityManagerInterface;

use AppBundle\Service\LxdApi\ContainerApi;

use AppBundle\Entity\Container;
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
     * @param ContainerApi $api
     * @param EntityManagerInterface $em
     * @return Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function listFromHostAction(Request $request, int $hostId, ContainerApi $api, EntityManagerInterface $em)
    {

        $fresh = $request->query->get('fresh');

        $containers = $this->getDoctrine()->getRepository(Container::class)->findAllByHostJoinedToHost($hostId);

        if ($fresh == 'true') {
            $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

            if (!$host) {
                throw $this->createNotFoundException(
                    'No host found for id ' . $hostId
                );
            }


            foreach ($containers as $container){
                $result = $api->show($container->getHost(), $container->getName());

                $container->setSettings($result->body->metadata);
                $container->setState(strtolower($result->body->metadata->status));


                $em->flush($container);
            }

        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($containers, 'json');
        return new Response($response);
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
     * @param ContainerApi $api
     * @param ProfileManagerApi $profileManagerApi
     * @return Response
     * @throws ElementNotFoundException
     * @throws WrongInputException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function storeAction(Request $request, int $hostId, EntityManagerInterface $em, ContainerApi $api)
    {
        $profileManagerApi = $this->container->get('profile.manager');

        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

        if (!$host) {
            throw new ElementNotFoundException(
                'No host found for id ' . $hostId
            );
        }

        $type = $request->query->get('type');

        $profiles = $this->getDoctrine()->getRepository(Profile::class)->findBy(['id' => $request->get("profiles")]);

        $profileNames = array();


        foreach ($profiles as $profile){
            $profileNames[] = $profile->getName();
        }

        $container = new Container();

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
                    $image = $this->getDoctrine()->getRepository(Image::class)->findOneBy(["fingerprint" => $request->get("fingerprint")]);

                    if (!$image) {
                        throw new ElementNotFoundException(
                            'No Image in our system found for fingerprint ' . $request->get("fingerprint")
                        );
                    }

                    $container->setImage($image);

                    $data["source"] = [
                        "type" => "image",
                        "fingerprint" => $image->getFingerPrint()
                    ];
                }

                if($request->request->has("alias")){
                    $imageAlias = $this->getDoctrine()->getRepository(ImageAlias::class)->findOneBy(["name" => $request->get("alias")]);

                    if (!$imageAlias) {
                        throw new ElementNotFoundException(
                            'No Image in our system found for alias ' . $request->get("alias")
                        );
                    }

                    $image = $imageAlias->getImage();
                    $container->setImage($image);
                    $data["source"] = [
                        "type" => "image",
                        "fingerprint" => $image->getFingerPrint()
                    ];
                }

                break;
            case 'migration':
                $oldContainer = $this->getDoctrine()->getRepository(Container::class)->find($request->get("oldContainerId"));

                if (!$oldContainer) {
                    throw new ElementNotFoundException(
                        'No Container found for containerId ' . $request->get("oldContainerId")
                    );
                }

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

                $container->setImage($oldContainer->getImage());


                break;
            case 'copy':
                $oldContainer = $this->getDoctrine()->getRepository(Container::class)->find($request->get("oldContainerId"));

                if (!$oldContainer) {
                    throw new ElementNotFoundException(
                        'No Container found for containerId ' . $request->get("oldContainerId")
                    );
                }

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

                $container->setImage($oldContainer->getImage());

                break;
            default:
                return new JsonResponse(["message" => "none"]);
        }

        $container->setHost($host);

        if($request->request->has("name")){
            $container->setName($request->get("name"));
        }

        $container->setSettings($data);




        $container->setState('creating');

        if($errorArray = $this->validation($container))
        {
            throw new WrongInputException(json_encode($errorArray));
        }


        $em->persist($container);
        $em->flush();

        foreach ($profiles as $profile){
            $profileManagerApi->enableProfileForContainer($profile, $container);
        }

        $result = $api->create($host, $data);

        $dispatcher = $this->get('sb_event_queue');

        if ($result->code != 202) {
            Return new Response(json_encode($result->body), Response::HTTP_BAD_REQUEST);
        }
        if ($result->body->metadata->status_code == 400) {
            Return new Response(json_encode($result->body), Response::HTTP_BAD_REQUEST);
        }

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
     * @param EntityManagerInterface $em
     * @return Object|Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function showSingleAction(Request $request, int $containerId, ContainerApi $api, EntityManagerInterface $em)
    {
        $fresh = $request->query->get('fresh');

        $container = $this->getDoctrine()->getRepository(Container::class)->findOneByIdJoinedToHost($containerId);

        if (!$container) {
            throw $this->createNotFoundException(
                'No container found for id ' . $containerId
            );
        }

        if ($fresh == 'true') {

            $result = $api->show($container->getHost(), $container->getName());

            $container->setSettings($result->body->metadata);
            $container->setState(strtolower($result->body->metadata->status));


            $em->flush($container);
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
        $profiles = $container->getProfiles();
        $profileManagerApi = $this->container->get('profile.manager');

        if (!$container) {
            throw $this->createNotFoundException(
                'No container found for id ' . $containerId
            );
        }

        $result = $api->remove($container->getHost(), $container->getName());

        foreach ($profiles as $profile){
            $profileManagerApi->disableProfileForContainer($profile, $container);
        }

        if($result->code == 404)
        {



            $em->remove($container);
            $em->flush();
            return new JsonResponse(["message" => "deleted because was not found on lxd-host"]);
        }


        $dispatcher = $this->get('sb_event_queue');
        $dispatcher->on(ContainerDeleteEvent::class, date('Y-m-d H:i:s'), $result->body->metadata->id, $container->getHost(), $container->getId());

        return $this->json(['message' => 'Deletion is ongoing'], 200);
    }


    /**
     * @param Request $request
     * @param int $containerId
     * @param EntityManagerInterface $em
     * @param ContainerApi $api
     * @param OperationApi $operationApi
     * @return WrongInputException|JsonResponse
     * @throws WrongInputException
     * @throws \Httpful\Exception\ConnectionErrorException
     * @Route("/containers/{containerId}", name="containers_update", methods={"PUT"})
     *
     * @OAS\Put(path="/containers/{containerId}",
     *  tags={"containers"},
     *  @OAS\Parameter(
     *      description="ID of the Container",
     *      in="path",
     *      name="containerId",
     *      required=true,
     *      @OAS\Schema(
     *         type="integer"
     *      ),
     *  ),
     *
     *  @OAS\Parameter(
     *      description="Body für die Namensaenderung eines Containers.",
     *      in="body",
     *      name="bodyName",
     *      @OAS\Schema(
     *          @OAS\Property(
     *              property="name",
     *              type="string"
     *          ),
     *      ),
     *  ),
     *
     *  @OAS\Parameter(
     *      description="Body für die Aenderung eines Containers.",
     *      in="body",
     *      name="bodyProps",
     *      @OAS\Schema(
     *          @OAS\Property(
     *              property="architecture",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="config",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="devices",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="ephemeral",
     *              type="bool"
     *          ),
     *          @OAS\Property(
     *              property="profiles",
     *              type="array"
     *          ),
     *      ),
     *  ),
     *
     *  @OAS\Response(
     *      response=200,
     *      description="Returns the informationen of a single Container",
     *      @OAS\JsonContent(ref="#/components/schemas/container"),
     *  ),
     *  @OAS\Response(
     *      response=400,
     *      description="Returns an 400 error if the new is already chosen."
     *  ),
     *  @OAS\Response(
     *      response=400,
     *      description="Returns an 400 error if something else was wrong."
     *  ),
     * )
     */
    public function updateAction(Request $request, int $containerId, EntityManagerInterface $em, ContainerApi $api, OperationApi $operationApi)
    {
        $container = $this->getDoctrine()->getRepository(Container::class)->findOneByIdJoinedToHost($containerId);


        if (!$container) {
            throw $this->createNotFoundException(
                'No container found for id ' . $containerId
            );
        }

        if($request->request->has("name"))
        {
            if($container->getName() != $request->get("name"))
            {
                $container->setName($request->get("name"));
                $data = ["name" => $request->get("name")];

                $result = $api->migrate($container->getHost(), $container, $data);

                if($result->code == 409)
                {
                    return new WrongInputException("The name is already taken.");
                }

                $operationResult = $operationApi->getOperationsLinkWithWait($container->getHost(), $result->body->metadata->id);
                if($operationResult->code != 200)
                {
                    return new WrongInputException($operationResult->body->error);
                }

                $serializer = $this->get('jms_serializer');
                $response = $serializer->serialize($container, 'json');
                return new Response($response, Response::HTTP_OK);
            } else {
                return new WrongInputException("The name is already taken.");
            }


        } else {
            $profiles = $this->getDoctrine()->getRepository(Profile::class)->findBy(['id' => $request->get("profiles")]);
            $profileNames = array();
            foreach ($profiles as $profile){
                $profileNames[] = $profile->getName();
            }

            if(!$request->request->has("architecture") && !$request->request->has("config") && !$request->request->has("devices") && !$request->request->has("ephemeral")){
                throw new WrongInputException("The following fields are all required: architecture, config, devices, profiles and ephemeral");
            }

            $data = [
                "architecture" => $request->get("architecture"),
                "config" => $request->get("config"),
                "devices" => $request->get("devices"),
                "ephemeral" => $request->get("ephemeral"),
                "profiles" => $profileNames
            ];

            $result = $api->update($container->getHost(), $container, $data);

            $operationResult = $operationApi->getOperationsLinkWithWait($container->getHost(), $result->body->metadata->id);

            if($operationResult->code != 200){
                return new WrongInputException($operationResult->body->error);
            }

            $container->setSettings($data);

            $em->flush();

            $serializer = $this->get('jms_serializer');
            $response = $serializer->serialize($container, 'json');
            return new Response($response, Response::HTTP_OK);

        }




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