<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Backup;
use AppBundle\Entity\BackupDestination;
use AppBundle\Entity\BackupSchedule;
use AppBundle\Entity\Container;
use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Exception\ForbiddenException;
use AppBundle\Exception\WrongInputExceptionArray;
use AppBundle\Worker\BackupWorker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as OAS;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BackupController extends BaseController
{
    /**
     * Get all successful Backups
     *
     * @Route("/backups", name="backups_all", methods={"GET"})
     *
     * @return Response
     * @throws ElementNotFoundException
     *
     * @OAS\Get(path="/backups",
     *     tags={"backups"},
     *      @OAS\Response(
     *          response=200,
     *          description="List of all successful Backups",
     *          @OAS\JsonContent(ref="#/components/schemas/backup"),
     *          @OAS\Schema(
     *              type="array"
     *          ),
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No Backups found",
     *      ),
     * )
     */
    public function getAllBackups()
    {
        $backups = $this->getDoctrine()->getRepository(Backup::class)->findAll();

        if (!$backups) {
            throw new ElementNotFoundException(
                'No Backups found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($backups, 'json');
        return new Response($response);
    }

    /**
     * Get single Backup by it's id
     *
     * @Route("/backups/{id}", name="backup_by_id", methods={"GET"})
     *
     * @return Response
     * @throws ElementNotFoundException
     *
     * @OAS\Get(path="/backups/{id}",
     *     tags={"backups"},
     *     @OAS\Parameter(
     *      description="Id of the Backup",
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OAS\Schema(
     *          @OAS\Property(
     *              property="id",
     *              type="int",
     *          ),
     *      ),
     *      ),
     *      @OAS\Response(
     *          response=200,
     *          description="Single Backup with the provided id",
     *          @OAS\JsonContent(ref="#/components/schemas/backup"),
     *          @OAS\Schema(
     *              type="object"
     *          ),
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No Backup for the id found",
     *      ),
     * )
     */
    public function getBackupById($id)
    {
        $backup = $this->getDoctrine()->getRepository(Backup::class)->find($id);

        if (!$backup) {
            throw new ElementNotFoundException(
                'No Backup for id ' . $id . ' found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($backup, 'json');
        return new Response($response);
    }

    /**
     * Webhook to create a new Backup object based on a Backup Schedule
     *
     * @Route("/webhooks/backups", name="create_backup_with_schedule_webhook", methods={"POST"})
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response
     * @throws ForbiddenException
     * @throws WrongInputExceptionArray
     * @OAS\Post(path="webhooks/backups?token={token}",
     * tags={"backups"},
     * @OAS\Parameter(
     *      description="The authorization token set in the Backup Schedule",
     *      name="token",
     *      in="query",
     *      required=true,
     *      @OAS\Schema(
     *          @OAS\Property(
     *              property="token",
     *              type="string",
     *          ),
     *      ),
     * ),
     * @OAS\Response(
     *  description="The provided parameters are invalid",
     *  response=400
     * ),
     * @OAS\Response(
     *  description="The backup token is invalid",
     *  response=403
     * ),
     * @OAS\Response(
     *  description="The new Backup object was created",
     *  response=201,
     *  @OAS\JsonContent(ref="#/components/schemas/backup"),
     * ),
     * )
     */
    public function backupCreationWebhook(Request $request, EntityManagerInterface $em)
    {
        $token = $request->query->get('token');

        $backupSchedule = $this->getDoctrine()->getRepository(BackupSchedule::class)->findOneBy(["token" => $token]);

        if (!$backupSchedule) {
            throw new ForbiddenException(
                'Invalid backup token'
            );
        }

        $backup = new Backup();
        $backup->setBackupSchedule($backupSchedule);

        //Add containers to Backup
        foreach ($backupSchedule->getContainers() as $container) {
            $backup->addContainer($container);
        }

        $backup->setDestination($backupSchedule->getDestination());
        $backup->setTimestamp();

        $this->validation($backup);

        $em->persist($backup);
        $em->flush();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($backup, 'json');
        return new Response($response, Response::HTTP_CREATED);
    }

    /**
     * Delete an existing Backup
     *
     * @Route("/backups/{id}", name="delete_backup", methods={"DELETE"})
     * @param $id
     * @param EntityManagerInterface $em
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws ElementNotFoundException
     *
     * @OAS\Delete(path="/backups/{id}",
     *     tags={"backups"},
     *     @OAS\Parameter(
     *      description="Id of the Backup",
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OAS\Schema(
     *          @OAS\Property(
     *              property="id",
     *              type="int",
     *          ),
     *      ),
     *      ),
     *      @OAS\Response(
     *          response=204,
     *          description="Backup for the provided id deleted",
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No Backup for the id found",
     *      ),
     * )
     */
    public function deleteBackupEntry($id, EntityManagerInterface $em)
    {
        $backup = $this->getDoctrine()->getRepository(Backup::class)->find($id);

        if (!$backup) {
            throw new ElementNotFoundException(
                'No Backup for id ' . $id . ' found'
            );
        }

        $em->remove($backup);
        $em->flush();

        return $this->json([], 204);
    }


    /**
     * @Route("/backups", name="create_backup", methods={"POST"})
     *
     * @OAS\Post(path="/backups",
     *  tags={"backups"},
     *  @OAS\Parameter(
     *      description="Create a manual backup of containers from the same host",
     *      name="body",
     *      in="body",
     *      required=true,
     *      @OAS\Schema(
     *          @OAS\Property(
     *              property="destination",
     *              type="int",
     *          ),
     *          @OAS\Property(
     *              property="containerIds",
     *              type="array",
     *          ),
     *          @OAS\Property(
     *              property="backupName",
     *              type="string",
     *          ),
     *      ),
     *  ),
     *  @OAS\Response(
     *      description="The provided parameters are invalid",
     *      response=400
     *  ),
     *  @OAS\Response(
     *      description="The backup token is invalid",
     *      response=403
     *  ),
     *  @OAS\Response(
     *      description="The new Backup object was created",
     *      response=201,
     *      @OAS\JsonContent(ref="#/components/schemas/backup"),
     *  ),
     * )
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param BackupWorker $backupWorker
     * @return Response
     * @throws ElementNotFoundException
     * @throws WrongInputExceptionArray
     */
    public function storeBackupAction(Request $request, EntityManagerInterface $em, BackupWorker $backupWorker)
    {
        $destination = $this->getDoctrine()->getRepository(BackupDestination::class)->find($request->get("destination"));

        if (!$destination) {
            throw new ElementNotFoundException(
                'No backup destination for id ' . $request->get("destination") . ' found'
            );
        }

        $containers = $this->getDoctrine()->getRepository(Container::class)->findBy(['id' => $request->get("containerIds")]);

        if (!$containers) {
            throw new ElementNotFoundException(
                'No containers for id ' . $request->get("containerIds") . ' found'
            );
        }

        $host = $containers[0]->getHost();

        foreach ($containers as $container) {
            if ($container->getHost() !== $host) {
                throw new WrongInputExceptionArray(["containers" => "The selected containers are not on the same host."]);
            }
        }

        $backup = new Backup();
        $backup->setDestination($destination);
        foreach ($containers as $container) {
            $backup->addContainer($container);
        }
        $backup->setManualBackupName($request->get("name"));
        $backup->setTimestamp();

        $this->validation($backup);

        $em->persist($backup);
        $em->flush();

        $backupWorker->later()->createManualBackup($backup->getId());


        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($backup, 'json');
        return new Response($response, 202);
    }


    /**
     * Return all backups from on schedule
     * 
     * @Route("/schedules/{scheduleId}/backups", name="get_schedule_backups", methods={"GET"})
     *
     * @param int $scheduleId
     * @param EntityManagerInterface $em
     * @throws ElementNotFoundException
     * @return Response
     */
    public function getBackupsFromSchedule($scheduleId, EntityManagerInterface $em)
    {
        $backupSchedule = $this->getDoctrine()->getRepository(BackupSchedule::class)->find($scheduleId);

        if (!$backupSchedule) {
            throw new ElementNotFoundException(
                'No BackupSchedule for id ' . $scheduleId . ' found'
            );
        }

        $backups = $backupSchedule->getBackups();

        if (count($backups) == 0) {
            throw new ElementNotFoundException(
                'No Backups for schedule ' . $scheduleId . ' found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($backups, 'json');
        return new Response($response, 200);
    }
}
