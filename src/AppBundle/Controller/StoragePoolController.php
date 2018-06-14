<?php
/**
 * Created by IntelliJ IDEA.
 * User: leon
 * Date: 12.06.18
 * Time: 13:35
 */

namespace AppBundle\Controller;


use AppBundle\Entity\Host;
use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Exception\WrongInputExceptionArray;
use AppBundle\Service\LxdApi\StorageApi;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as OAS;
use AppBundle\Entity\StoragePool;

class StoragePoolController extends BaseController
{
    /**
     * Get all storage pools
     *
     * @Route("/hosts/{hostId}/storage-pools", name="storage_pool_all", methods={"GET"})
     *
     * @OAS\Get(path="/hosts/{hostId}/storage-pools",
     *     tags={"storage-pools"},
     *      @OAS\Response(
     *          response=200,
     *          description="List of all storage pools",
     *          @OAS\JsonContent(ref="#/components/schemas/storage_pool"),
     *          @OAS\Schema(
     *              type="array"
     *          ),
     *      ),
     *      @OAS\Parameter(
     *          description="ID of the Host",
     *          in="path",
     *          name="hostId",
     *          required=true,
     *          @OAS\Schema(
     *              type="integer"
     *          ),
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No storage pools found",
     *      ),
     * )
     *
     * @param $hostId
     * @return Response
     * @throws ElementNotFoundException
     */
    public function getAllStoragePools($hostId)
    {
        $profiles = $this->getDoctrine()->getRepository(StoragePool::class)->findBy(['host' => $hostId]);

        if (!$profiles) {
            throw new ElementNotFoundException(
                'No storage pools found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($profiles, 'json');
        return new Response($response);
    }


    /**
     * Get a single storage pool by its id
     *
     * @Route("/storage-pools/{storagePoolId}", name="storage_pool_single", methods={"GET"})
     *
     * @OAS\Get(path="/storage-pools/{storagePoolId}",
     *  tags={"storage-pools"},
     *  @OAS\Response(
     *      response=200,
     *      description="Detailed information about a specific storage pool",
     *      @OAS\JsonContent(ref="#/components/schemas/storage_pool"),
     *  ),
     *  @OAS\Response(
     *      description="No storage pool for the provided id found",
     *      response=404
     * ),
     *
     *  @OAS\Parameter(
     *      description="ID of the storage pool",
     *      in="path",
     *      name="storagePoolId",
     *      required=true,
     *      @OAS\Schema(
     *          type="integer"
     *      ),
     *  ),
     *)
     *
     * @param $storagePoolId
     * @return Response
     * @throws ElementNotFoundException
     */
    public function getSingleStoragePool($storagePoolId)
    {
        $storagePools = $this->getDoctrine()->getRepository(StoragePool::class)->find($storagePoolId);

        if (!$storagePools) {
            throw new ElementNotFoundException(
                'No storage pool for ID ' . $storagePoolId . ' found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($storagePools, 'json');
        return new Response($response);
    }


    /**
     * Create a storage pool
     *
     * @Route("/hosts/{hostId}/storage-pools", name="create_storage_pool", methods={"POST"})
     *
     * @OAS\Post(path="/hosts/{hostId}/storage-pools",
     * tags={"storage-pools"},
     * @OAS\Parameter(
     *      description="Parameters for the new storage pool",
     *      name="body",
     *      in="body",
     *      required=true,
     *      @OAS\Schema(
     *      @OAS\Property(
     *          property="name",
     *          type="string",
     *      ),
     *      @OAS\Property(
     *          property="driver",
     *          type="string"
     *      ),
     *      @OAS\Property(
     *          property="config",
     *          type="string"
     *      ),
     *  ),
     * ),
     * @OAS\Parameter(
     *  description="ID of the Host the storage pool should be created on",
     *  in="path",
     *  name="hostId",
     *  required=true,
     *  @OAS\Schema(
     *     type="integer"
     *  ),
     * ),
     * @OAS\Response(
     *  description="The provided values for the storage pool are not valid",
     *  response=400
     * ),
     * @OAS\Response(
     *  description="The storage pool was successfully created",
     *  response=201,
     *  @OAS\JsonContent(ref="#/components/schemas/storage_pool"),
     * ),
     * )
     *
     * @param Request $request
     * @param int $hostId
     * @param EntityManagerInterface $em
     * @param StorageApi $storageApi
     * @return Response
     * @throws ElementNotFoundException
     * @throws WrongInputExceptionArray
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function createStoragePool(Request $request, int $hostId, EntityManagerInterface $em, StorageApi $storageApi)
    {
        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

        if (!$host) {
            throw new ElementNotFoundException(
                'No host found for id ' . $hostId
            );
        }


        $storagePool = new StoragePool();

        $storagePool->setHost($host);
        $storagePool->setName($request->request->get('name'));
        $storagePool->setDriver($request->request->get('driver'));
        $storagePool->setConfig($request->request->get('config'));

        $this->validation($storagePool);

        $result = $storageApi->create($host, $storagePool->getData());

        $em->persist($storagePool);
        $em->flush();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($storagePool, 'json');
        return new Response($response, Response::HTTP_CREATED);
    }

    /**
     * Edit a existing storage pool
     *
     * @Route("/storage-pools/{storagePoolId}", name="edit_storage_pool", methods={"PUT"})
     *
     * @OAS\Put(path="/storage-pools/{storagePoolId}",
     * tags={"storage-pools"},
     * @OAS\Parameter(
     *      description="Parameters which should be used to update the storage pool",
     *      name="body",
     *      in="body",
     *      required=true,
     *      @OAS\Schema(
     *      @OAS\Property(
     *          property="name",
     *          type="string",
     *      ),
     *      @OAS\Property(
     *          property="description",
     *          type="string"
     *      ),
     *      @OAS\Property(
     *          property="config",
     *          type="string"
     *      ),
     *      @OAS\Property(
     *          property="devices",
     *          type="string"
     *      ),
     *  ),
     * ),
     * @OAS\Parameter(
     *  description="ID of the storage pool",
     *  in="path",
     *  name="storagePoolId",
     *  required=true,
     *  @OAS\Schema(
     *     type="integer"
     *  ),
     * ),

     * @OAS\Response(
     *  description="No storage pool for the provided id found",
     *  response=404
     * ),
     * @OAS\Response(
     *  description="The provided values for the storage pool are not valid or the LXD Api call failed",
     *  response=400
     * ),
     * @OAS\Response(
     *  description="The storage pool was successfully updated",
     *  @OAS\JsonContent(ref="#/components/schemas/storage_pool"),
     *  response=201
     * ),
     * )
     *
     * @param $storagePoolId
     * @param Request $request
     * @return Response
     * @throws \Httpful\Exception\ConnectionErrorException
     * @throws ElementNotFoundException
     * @throws WrongInputExceptionArray
     */
    public function editProfile($storagePoolId, Request $request, EntityManagerInterface $em, StorageApi $storageApi)
    {
        $storagePool = $this->getDoctrine()->getRepository(StoragePool::class)->find($storagePoolId);

        if (!$storagePool) {
            throw new ElementNotFoundException(
                'No storage pool for ID ' . $storagePoolId . ' found'
            );
        }


        $storagePool->setName($request->request->get('name'));
        $storagePool->setConfig($request->request->get('config'));
        $storagePool->setDriver($request->request->get('driver'));

        $this->validation($storagePool);

        $result = $storageApi->create($storagePool->getHost(), $storagePool->getData());

        $em->persist($storagePool);
        $em->flush();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($storagePool, 'json');
        return new Response($response, Response::HTTP_OK);
    }

    /**
     * Delete a existing storage pool
     *
     * @Route("/storage-pools/{storagePoolId}", name="delete_storage_pool", methods={"DELETE"})
     *
     * @OAS\Delete(path="/storage-pools/{storagePoolId}",
     *  tags={"storage-pools"},
     *  @OAS\Parameter(
     *      description="ID of the storage pool",
     *      in="path",
     *      name="storagePoolId",
     *      required=true,
     *      @OAS\Schema(
     *          type="integer"
     *      ),
     *  ),
     *  @OAS\Response(
     *      response=204,
     *      description="The storage pool was successfully deleted",
     *  ),
     *  @OAS\Response(
     *      response=400,
     *      description="The storage pool couldn't be deleted, because it is used by at least one Container or the LXD Api call failed",
     *  ),
     *  @OAS\Response(
     *      description="No storage pool for the provided id found",
     *      response=404
     * ),
     *)
     * @param $storagePoolId
     * @param EntityManagerInterface $em
     * @param StorageApi $storageApi
     * @return Response
     * @throws ElementNotFoundException
     * @throws WrongInputExceptionArray
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function deleteProfile($storagePoolId, EntityManagerInterface $em, StorageApi $storageApi)
    {
        $storagePool = $this->getDoctrine()->getRepository(StoragePool::class)->find($storagePoolId);

        if (!$storagePool) {
            throw new ElementNotFoundException(
                'No storage pool found for id ' . $storagePoolId
            );
        }

        if ($storagePool->isUsedByContainer()) {
            throw new WrongInputExceptionArray(["general" => "The storage pool is used by at least one Container"]);
        }

        $result = $storageApi->remove($storagePool->getHost(), $storagePool->getName());

        $em->remove($storagePool);
        $em->flush();

        return new Response("", 204);
    }



}