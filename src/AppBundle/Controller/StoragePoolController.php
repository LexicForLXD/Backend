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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\StoragePool;

class StoragePoolController extends BaseController
{
    /**
     * Get all storage pools from one host
     *
     * @Route("/hosts/{hostId}/storage-pools", name="storage_pool_all_from_host", methods={"GET"})
     *
     * @param $hostId
     * @return Response
     * @throws ElementNotFoundException
     */
    public function getAllStoragePoolsFromHost($hostId)
    {
        $storagePools = $this->getDoctrine()->getRepository(StoragePool::class)->findBy(['host' => $hostId]);

        if (!$storagePools) {
            throw new ElementNotFoundException(
                'No storage pools found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($storagePools, 'json');
        return new Response($response);
    }


    /**
     * Get all storage pools
     *
     * @Route("/storage-pools", name="storage_pool_all", methods={"GET"})
     *
     * @return Response
     * @throws ElementNotFoundException
     */
    public function getAllStoragePools()
    {
        $storagePools = $this->getDoctrine()->getRepository(StoragePool::class)->findAll();

        if (!$storagePools) {
            throw new ElementNotFoundException(
                'No storage pools found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($storagePools, 'json');
        return new Response($response);
    }


    /**
     * Get a single storage pool by its id
     *
     * @Route("/storage-pools/{storagePoolId}", name="storage_pool_single", methods={"GET"})
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

        if ($result->code !== 201) {
            throw new WrongInputExceptionArray(["general" => $result->body->error]);
        }

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
     * @param $storagePoolId
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param StorageApi $storageApi
     * @return Response
     * @throws ElementNotFoundException
     * @throws WrongInputExceptionArray
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function editStoragePool($storagePoolId, Request $request, EntityManagerInterface $em, StorageApi $storageApi)
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

        if ($result->code !== 200) {
            throw new WrongInputExceptionArray(["general" => $result->body->error]);
        }

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
     * @param $storagePoolId
     * @param EntityManagerInterface $em
     * @param StorageApi $storageApi
     * @return Response
     * @throws ElementNotFoundException
     * @throws WrongInputExceptionArray
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function deleteStoragePool($storagePoolId, EntityManagerInterface $em, StorageApi $storageApi)
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

        if ($result->code !== 200) {
            throw new WrongInputExceptionArray(["general" => $result->body->error]);
        }

        $em->remove($storagePool);
        $em->flush();

        return new Response("", 204);
    }



}