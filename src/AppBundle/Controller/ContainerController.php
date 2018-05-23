<?php
namespace AppBundle\Controller;


use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Exception\WrongInputException;
use AppBundle\Exception\WrongInputExceptionArray;

use AppBundle\Service\LxdApi\OperationApi;
use AppBundle\Service\Profile\ProfileManagerApi;
use AppBundle\Service\LxdApi\HostApi;
use AppBundle\Service\LxdApi\ContainerStateApi;
use AppBundle\Service\LxdApi\ContainerApi;

use AppBundle\Worker\ContainerWorker;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Doctrine\ORM\EntityManagerInterface;

use AppBundle\Entity\Container;
use AppBundle\Entity\Host;
use AppBundle\Entity\Profile;
use AppBundle\Entity\Image;
use AppBundle\Entity\ImageAlias;

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
     * @throws ElementNotFoundException
     */
    public function indexAction()
    {
        $containers = $this->getDoctrine()->getRepository(Container::class)->findAll();

        if (!$containers) {
            throw new ElementNotFoundException(
                'No Containers found'
            );
        }

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
     * @throws ElementNotFoundException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function listFromHostAction(Request $request, int $hostId, ContainerApi $api, EntityManagerInterface $em)
    {

        $fresh = $request->query->get('fresh');

        $containers = $this->getDoctrine()->getRepository(Container::class)->findBy(['host' => $hostId]);

        if (!$containers) {
            throw new ElementNotFoundException(
                'No Containers for Host ' . $hostId . ' found'
            );
        }

        if ($fresh == 'true') {
            $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

            if (!$host) {
                throw new ElementNotFoundException(
                    'No host found for id ' . $hostId
                );
            }


            foreach ($containers as $container) {
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
     * @param HostApi $hostApi
     * @param OperationApi $operationApi
     * @param ContainerWorker $containerWorker
     * @return Response
     * @throws ElementNotFoundException
     * @throws WrongInputExceptionArray
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function storeAction(Request $request, int $hostId, EntityManagerInterface $em, ProfileManagerApi $profileManagerApi, ContainerWorker $containerWorker)
    {
        $type = $request->query->get('type');

        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

        if (!$host) {
            throw new ElementNotFoundException(
                'No host found for id ' . $hostId
            );
        }


        if($request->request->has('profiles'))
        {
            $profiles = $this->getDoctrine()->getRepository(Profile::class)->findBy(['id' => $request->get("profiles")]);
            $this->checkProfiles($profiles, $request->get("profiles"));
        }


        $container = new Container();
        $container->setHost($host);
        $container->setConfig($request->get("config"));
        $container->setDevices($request->get("devices"));
        $container->setEphemeral($request->get("ephemeral", false));
        $container->setName($request->get("name"));
        $container->setArchitecture($request->get("architecture", 'x86_64'));
        $container->setState('creating');

        switch ($type) {
            case 'image':


                if ($request->request->has("fingerprint") && !$request->request->has("alias"))
                {
                    $image = $this->getDoctrine()->getRepository(Image::class)->findOneBy(["fingerprint" => $request->get("fingerprint")]);

                    if (!$image) {
                        throw new WrongInputExceptionArray([
                            'fingerprint' => 'No Image in our system found for fingerprint ' . $request->get("fingerprint")
                        ]);
                    }


                } else if ($request->request->has("alias") && !$request->request->has("fingerprint"))
                {
                    $imageAlias = $this->getDoctrine()->getRepository(ImageAlias::class)->findOneBy(["name" => $request->get("alias")]);

                    if (!$imageAlias) {
                        throw new WrongInputExceptionArray([
                            'alias' => 'No Image in our system found for alias ' . $request->get("alias")
                        ]);
                    }

                    $image = $imageAlias->getImage();

                } else
                {
                    throw new WrongInputExceptionArray([
                        'image' => 'You have to pass either a fingerprint or an alias for the image.'
                    ]);
                }


                if($host !== $image->getHost())
                {
                    throw new WrongInputExceptionArray([
                        'image' => 'The image you selected is not available on the selected host.'
                    ]);
                }

                $container->setImage($image);
                $container->setSource([
                    "type" => "image",
                    "fingerprint" => $image->getFingerPrint()
                ]);

                break;
            case 'migration':
                $oldContainer = $this->getDoctrine()->getRepository(Container::class)->find($request->get("oldContainerId"));

                if (!$oldContainer) {
                    throw new WrongInputExceptionArray([
                        'container' => 'No Container found for containerId ' . $request->get("oldContainerId")
                    ]);
                }


                $container->setImage($oldContainer->getImage());
                $this->validation($container);
                $em->persist($container);
                $em->flush();

                $containerWorker->later()->migrateContainer($container->getId(), $oldContainer->getId(), $request->get("containerOnly", true), $request->get("live", false), $profiles);

                $serializer = $this->get('jms_serializer');
                $response = $serializer->serialize($container, 'json');
                return new Response($response, Response::HTTP_CREATED);

                break;
            case 'copy':
                $oldContainer = $this->getDoctrine()->getRepository(Container::class)->find($request->get("oldContainerId"));

                if (!$oldContainer) {
                    throw new WrongInputExceptionArray([
                        'container' => 'No Container found for containerId ' . $request->get("oldContainerId")
                    ]);
                }

                $container->setSource([
                    "type" => "copy",
                    "container_only" => $request->get("containerOnly", true),
                    "source" => $oldContainer->getName()
                ]);
                $container->setImage($oldContainer->getImage());

                break;
            case 'none':

                $container->setSource([
                    "type" => "none"
                ]);

                break;
            default:
                throw new WrongInputExceptionArray(['type' => "The type was wrong. Either use image, migration, copy or none."]);
        }

        $this->validation($container);

        $em->persist($container);
        $em->flush();

        if($request->request->has('profiles'))
        {
            foreach ($profiles as $profile) {
                $profileManagerApi->enableProfileForContainer($profile, $container);
            }
        }

        $containerWorker->later()->createContainer($container->getId());

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($container, 'json');
        return new Response($response, Response::HTTP_ACCEPTED);

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
     * @throws ElementNotFoundException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function showSingleAction(Request $request, int $containerId, ContainerApi $api, EntityManagerInterface $em)
    {
        $fresh = $request->query->get('fresh');

        $container = $this->getDoctrine()->getRepository(Container::class)->find($containerId);

        if (!$container) {
            throw new ElementNotFoundException(
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
     * @param ContainerStateApi $stateApi
     * @param ContainerWorker $containerWorker
     * @return JsonResponse
     * @throws ElementNotFoundException
     * @throws WrongInputException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function deleteAction($containerId, EntityManagerInterface $em, ContainerStateApi $stateApi, ContainerWorker $containerWorker)
    {
        $container = $this->getDoctrine()->getRepository(Container::class)->find($containerId);

        if (!$container) {
            throw new ElementNotFoundException(
                'No container found for id ' . $containerId
            );
        }
        $stateResult = $stateApi->actual($container);

        if ($stateResult->code == 404) {
            $em->remove($container);
            $em->flush();
            return new JsonResponse(["message" => "deleted because was not found on lxd-host"]);
        }

        if ($stateResult->body->metadata->status_code != 102) {
            throw new WrongInputException("Container is currently not stopped. Please stop the container before you delete it.");
        }

        $containerWorker->later()->deleteContainer($container->getId());

        return $this->json(['message' => 'Deletion is ongoing'], Response::HTTP_ACCEPTED);
    }


    /**
     * @param Request $request
     * @param int $containerId
     * @param EntityManagerInterface $em
     * @param ProfileManagerApi $profileManagerApi
     * @param ContainerWorker $containerWorker
     * @return Response
     * @throws ElementNotFoundException
     * @throws WrongInputException
     * @throws WrongInputExceptionArray
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
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
    public function updateAction(Request $request, int $containerId, EntityManagerInterface $em, ProfileManagerApi $profileManagerApi, ContainerWorker $containerWorker)
    {
        $container = $this->getDoctrine()->getRepository(Container::class)->find($containerId);


        if (!$container) {
            throw new ElementNotFoundException(
                'No container found for id ' . $containerId
            );
        }

        if ($request->request->has("name")) {
            if ($container->getName() != $request->get("name")) {

                $data = ["name" => $request->get("name")];

                $container->setDataBody($data);
                $em->flush($container);
                $containerWorker->later()->renameContainer($container);
            } else {
                throw new WrongInputExceptionArray(["name" => "The new name is same as current name."]);
            }

        } else {
            $profileNames = array();

            if($request->request->has('profiles'))
            {
                $profiles = $this->getDoctrine()->getRepository(Profile::class)->findBy(['id' => $request->get("profiles")]);
                foreach ($profiles as $profile) {
                    $profileNames[] = $profile->getName();
                    $profileManagerApi->enableProfileForContainer($profile, $container);
                }
                $this->checkProfiles($profiles, $request->get("profiles"));
            }

            if (!$request->request->has("architecture") && !$request->request->has("config") && !$request->request->has("devices") && !$request->request->has("ephemeral") && !$request->request->has("profiles")) {
                throw new WrongInputException("The following fields are all required: architecture, config, devices, profiles and ephemeral");
            }

            $data = [
                "architecture" => $request->get("architecture"),
                "config" => $request->get("config"),
                "devices" => $request->get("devices"),
                "ephemeral" => $request->get("ephemeral"),
                "profiles" => $profileNames
            ];

            $container->setDataBody($data);
            $container->setArchitekture($request->get("architecture"));
            $container->setConfig($request->get("config"));
            $container->setDevices($request->get("devices"));
            $container->setEphemeral($request->get("ephemeral"));
            $this->validation($container);
            $em->flush($container);
            $containerWorker->later()->updateContainer($container->getId());
        }

//        $serializer = $this->get('jms_serializer');
//        $response = $serializer->serialize($container, 'json');
//        return new Response($response, Response::HTTP_OK);

        return $this->json(['message' => 'Update is ongoing'], Response::HTTP_ACCEPTED);



    }


    /**
     * Validates a Container Object and returns array with errors.
     * @param Container $object
     * @return array|bool
     * @throws WrongInputExceptionArray
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
            throw new WrongInputExceptionArray($errorArray);
        }
        return false;
    }


    /**
     * Checks whether the transmitted profiles are in the DB
     *
     * @param array $profiles
     * @param array $profilesRequest
     * @return array
     * @throws WrongInputExceptionArray
     */
    private function checkProfiles(Array $profiles, Array $profilesRequest)
    {
        $profilesDB = array();
        $profileNames = array();

        foreach ($profiles as $profile)
        {
            $profilesDB[] = $profile->getId();
            $profileNames[] = $profile->getName();
        }

        $errors = array_diff($profilesRequest, $profilesDB);

        $errorArray = array();
        foreach ($errors as $error) {
            $errorArray[] = 'The profile with the id ' . $error . ' is not present in our database.';
        }
        if(count($errorArray) > 0)
        {
            throw new WrongInputExceptionArray($errorArray);
        }
        return $profileNames;

    }

}