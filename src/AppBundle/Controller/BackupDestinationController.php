<?php

namespace AppBundle\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Exception\WrongInputExceptionArray;
use AppBundle\Exception\ElementNotFoundException;

use AppBundle\Entity\BackupDestination;


class BackupDestinationController extends BaseController
{
    /**
     * List all backup destinations
     *
     * @Route("/backupdestinations", name="backup_dest_index", methods={"GET"})
     *
     * @return Response
     * @throws ElementNotFoundException
     */
    public function indexBackupDestinationAction()
    {
        $dests = $this->getDoctrine()->getRepository(BackupDestination::class)->findAll();

        if (!$dests) {
            throw new ElementNotFoundException(
                'No backup destinations found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($dests, 'json');
        return new Response($response);
    }


    /**
     * Show one backup destination
     *
     * @Route("/backupdestinations/{destId}", name="backup_dest_show", methods={"GET"})
     *
     * @param integer $destId
     * @return Response
     * @throws ElementNotFoundException
     */
    public function showBackupDestinationAction(int $destId)
    {
        $dest = $this->getDoctrine()->getRepository(BackupDestination::class)->find($destId);

        if (!$dest) {
            throw new ElementNotFoundException(
                'No backup destination found for id ' . $destId
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($dest, 'json');
        return new Response($response);
    }


    /**
     * Creates a new backup destination
     *
     * @Route("/backupdestinations", name="backup_dest_create", methods={"POST"})
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response
     * @throws WrongInputExceptionArray
     */
    public function createBackupDestinationAction(Request $request, EntityManagerInterface $em)
    {
        $dest = new BackupDestination();
        $dest->setName($request->get('name'));
        $dest->setDescription($request->get('description'));
        $dest->setHostname($request->get('hostname'));
        $dest->setProtocol($request->get('protocol'));
        $dest->setPath($request->get('path'));

        if ($request->request->has("username")) {
            $dest->setUsername($request->get('username'));
        }
        if ($request->request->has("password")) {
            $dest->setPassword($request->get('password'));
        }

        $this->validation($dest);

        $em->persist($dest);
        $em->flush();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($dest, 'json');
        return new Response($response, Response::HTTP_CREATED);

    }


    /**
     * Update a single backup destination
     *
     * @Route("/backupdestinations/{destId}", name="backup_dest_update", methods={"PUT"})
     *
     * @param Request $request
     * @param integer $destId
     * @param EntityManagerInterface $em
     * @return Response
     * @throws ElementNotFoundException
     * @throws WrongInputExceptionArray
     */
    public function updateBackupDestinationAction(Request $request, int $destId, EntityManagerInterface $em)
    {
        $dest = $this->getDoctrine()->getRepository(BackupDestination::class)->find($destId);

        if (!$dest) {
            throw new ElementNotFoundException(
                'No backup destination found for id ' . $destId
            );
        }

        $dest->setName($request->get('name'));
        $dest->setDescription($request->get('description'));
        $dest->setHostname($request->get('hostname'));
        $dest->setProtocol($request->get('protocol'));
        $dest->setPath($request->get('path'));

        if ($request->request->has("username")) {
            $dest->setUsername($request->get('username'));
        }
        if ($request->request->has("password")) {
            $dest->setPassword($request->get('password'));
        }

        $this->validation($dest);

        $em->flush();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($dest, 'json');
        return new Response($response);
    }


    /**
     * Delete one backup destination
     *
     * @Route("/backupdestinations/{destId}", name="backup_dest_delete", methods="DELETE")
     *
     * @param integer $destId
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws ElementNotFoundException
     * @throws WrongInputExceptionArray
     */
    public function deleteBackupDestinationAction(int $destId, EntityManagerInterface $em)
    {
        $dest = $this->getDoctrine()->getRepository(BackupDestination::class)->find($destId);

        if (!$dest) {
            throw new ElementNotFoundException(
                'No backup destination found for id ' . $destId
            );
        }

        if (!$dest->getBackupSchedules()->isEmpty()) {
            throw new WrongInputExceptionArray(["general" => 'Please first remove this backup destination from the backup schedule.']);
        }

        $em->remove($dest);
        $em->flush();

        return new JsonResponse([], 204);
    }


}