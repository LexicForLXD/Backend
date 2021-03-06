<?php
namespace AppBundle\Controller;


use AppBundle\Entity\StoragePool;
use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Exception\WrongInputException;
use AppBundle\Exception\WrongInputExceptionArray;

use AppBundle\Service\LxdApi\OperationApi;
use AppBundle\Service\Profile\ProfileManagerApi;
use AppBundle\Service\LxdApi\HostApi;
use AppBundle\Service\LxdApi\ContainerStateApi;
use AppBundle\Service\LxdApi\ContainerApi;

use AppBundle\Worker\ContainerWorker;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Doctrine\ORM\EntityManagerInterface;

use AppBundle\Entity\Container;
use AppBundle\Entity\Host;
use AppBundle\Entity\Profile;
use AppBundle\Entity\Image;
use AppBundle\Entity\ImageAlias;

use Symfony\Component\Routing\Annotation\Route;


class ContainerController extends BaseController
{
    /**
     * Get all saved Containers
     *
     * @Route("/containers", name="containers_all", methods={"GET"})
     *
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

        if (!$request->request->has("storagePoolId")) {
            throw new WrongInputExceptionArray(
                ["storagePool" => "No storagePoolId included in request."]
            );
        }

        $storagePool = $this->getDoctrine()->getRepository(StoragePool::class)->find($request->get("storagePoolId"));
        if (!$storagePool) {
            throw new WrongInputExceptionArray(
                ["storagePool" => "No storagePool found for id " . $request->get("storagePoolId")]
            );
        }


        if ($request->request->has('profiles')) {
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
        $container->setStoragePool($storagePool);
        $container->setState('creating');

        switch ($type) {
            case 'image':


                if ($request->request->has("fingerprint") && !$request->request->has("alias")) {
                    $image = $this->getDoctrine()->getRepository(Image::class)->findOneBy(["fingerprint" => $request->get("fingerprint")]);

                    if (!$image) {
                        throw new WrongInputExceptionArray([
                            'fingerprint' => 'No Image in our system found for fingerprint ' . $request->get("fingerprint")
                        ]);
                    }


                } else if ($request->request->has("alias") && !$request->request->has("fingerprint")) {
                    $imageAlias = $this->getDoctrine()->getRepository(ImageAlias::class)->findOneBy(["name" => $request->get("alias")]);

                    if (!$imageAlias) {
                        throw new WrongInputExceptionArray([
                            'alias' => 'No Image in our system found for alias ' . $request->get("alias")
                        ]);
                    }

                    $image = $imageAlias->getImage();

                } else {
                    throw new WrongInputExceptionArray([
                        'image' => 'You have to pass either a fingerprint or an alias for the image.'
                    ]);
                }


                if ($host !== $image->getHost()) {
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

        if ($request->request->has('profiles')) {
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
     * @param Request $request
     * @param int $containerId
     * @param ContainerApi $api
     * @param EntityManagerInterface $em
     * @return Object|Response
     * @throws ElementNotFoundException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function showSingleAction(Request $request, int $containerId, ContainerApi $api, ContainerStateApi $stateApi, EntityManagerInterface $em)
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

            $this->checkForErrors($result);

            $container->setArchitecture($result->body->metadata->architecture);
            $container->setConfig($result->body->metadata->config);
            $container->setDevices($result->body->metadata->devices);
            $container->setEphemeral($result->body->metadata->ephemeral);
            $container->setCreatedAt(new \DateTime($result->body->metadata->created_at));
            $container->setExpandedConfig($result->body->metadata->expanded_config);
            $container->setExpandedDevices($result->body->metadata->expanded_devices);
            $container->setState(mb_strtolower($result->body->metadata->status));

            $result = $stateApi->actual($container);
            $container->setNetwork($result->body->metadata->network);

            $this->validation($container);
            $em->flush();
        }

        $em->refresh($container);
        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($container, 'json');
        return new Response($response);

    }


    /**
     * Deletes a Container by containerID
     *
     * @Route("/containers/{containerId}", name="containers_delete", methods={"DELETE"})
     *
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

                $containerWorker->later()->renameContainer($container->getId(), $request->get("name"));
            } else {
                throw new WrongInputExceptionArray(["name" => "The new name is same as current name."]);
            }

        } else {
            $requestProfiles = $request->get("profiles", []);

            $profiles = $this->getDoctrine()->getRepository(Profile::class)->findBy(['id' => $requestProfiles]);
            foreach ($profiles as $profile) {
                $profileManagerApi->enableProfileForContainer($profile, $container);
            }
            $profiles = $this->getDoctrine()->getRepository(Profile::class)->findAll();
            $profilesDB = array();
            foreach ($profiles as $profile) {
                $profilesDB[] = $profile->getId();
            }

            $unusedProfilesId = (array)array_diff($profilesDB, $requestProfiles);
            $unusedProfiles = $this->getDoctrine()->getRepository(Profile::class)->findBy(['id' => $unusedProfilesId]);

            foreach ($unusedProfiles as $profile) {
                $profileManagerApi->disableProfileForContainer($profile, $container);
            }

            $this->checkProfiles($profiles, $requestProfiles);




            $container->setConfig($request->get("config", []));
            $container->setDevices($request->get("devices", []));
            $this->validation($container);
            $em->flush($container);
            $containerWorker->later()->updateContainer($container->getId());
        }

        return $this->json(['message' => 'Update is ongoing'], Response::HTTP_ACCEPTED);



    }


}