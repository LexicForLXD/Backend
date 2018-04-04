<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Backup;
use AppBundle\Entity\BackupDestination;
use AppBundle\Entity\BackupSchedule;
use AppBundle\Entity\Container;
use AppBundle\Event\ManualBackupEvent;
use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Exception\ForbiddenException;
use AppBundle\Exception\WrongInputException;
use AppBundle\Exception\WrongInputExceptionArray;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Swagger\Annotations as OAS;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BackupController extends Controller
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
    public function getBackupById($id){
        $backup = $this->getDoctrine()->getRepository(Backup::class)->find($id);

        if (!$backup) {
            throw new ElementNotFoundException(
                'No Backup for id '.$id .' found'
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
    public function backupCreationWebhook(Request $request, EntityManagerInterface $em){
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
        foreach ($backupSchedule->getContainers() as $container){
            $backup->addContainer($container);
        }

        $backup->setDestination($backupSchedule->getDestination());
        $backup->setTimestamp();

        if ($errorArray = $this->validation($backup)) {
            throw new WrongInputExceptionArray($errorArray);
        }

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
    public function deleteBackupEntry($id, EntityManagerInterface $em){
        $backup = $this->getDoctrine()->getRepository(Backup::class)->find($id);

        if (!$backup) {
            throw new ElementNotFoundException(
                'No Backup for id '.$id .' found'
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
     * @return Response
     * @throws ElementNotFoundException
     * @throws WrongInputException
     */
    public function storeBackupAction(Request $request, EntityManagerInterface $em)
    {
        $destination = $this->getDoctrine()->getRepository(BackupDestination::class)->find($request->get("destination"));

        if (!$destination) {
            throw new ElementNotFoundException(
                'No backup destination for id '.$request->get("destination") .' found'
            );
        }

        $containers = $this->getDoctrine()->getRepository(Container::class)->findBy(['id' => $request->get("containerIds")]);

        if (!$containers) {
            throw new ElementNotFoundException(
                'No containers for id '.$request->get("containerIds") .' found'
            );
        }

        $host = $containers[0].getHost();

        foreach ($containers as $container)
        {
            if($container->getHost() != $host)
            {
                throw new WrongInputException("The selected containers are not on the same host.");
            }
        }

        $backup = new Backup();
        $backup->setDestination($destination);
        foreach ($containers as $container)
        {
            $backup->addContainer($container);
        }
        $backup->setManualBackupName($request->get("backupName"));
        $backup->setTimestamp();

        $this->validation($backup);

        $em->persist($backup);
        $em->flush();

        $dispatcher = $this->get('sb_event_queue');
        $dispatcher->on(ManualBackupEvent::class, date('Y-m-d H:i:s'), $host, $backup);


        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($backup, 'json');
        return new Response($response, Response::HTTP_CREATED);
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
