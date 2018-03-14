<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Backup;
use AppBundle\Entity\BackupSchedule;
use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Exception\ForbiddenException;
use AppBundle\Exception\WrongInputException;
use AppBundle\Exception\WrongInputExceptionArray;
use AppBundle\Service\SSH\HostSSH;
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
     * @Route("/backups", name="create_backup_with_schedule_webhook", methods={"POST"})
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response
     * @throws ForbiddenException
     * @throws WrongInputExceptionArray
     * @OAS\Post(path="/backups?token={token}",
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

    /**
     * @Route("/backups/{backupId}/restores/containers/{containerId}", name="restore_backup_single_container", methods={"POST"})
     * @throws ElementNotFoundException
     * @throws WrongInputException
     */
    public function restoreBackupForSingleContainer($backupId, $containerId, EntityManagerInterface $entityManager, HostSSH $hostSSH)
    {
        $backup = $this->getDoctrine()->getRepository(Backup::class)->find($backupId);

        if (!$backup) {
            throw new ElementNotFoundException(
                'No Backup for id ' . $backupId . ' found'
            );
        }

        $containers = $backup->getContainers();
        $container = null;
        foreach ($containers as $containerCheck) {
            if ($containerCheck->getId() == $containerId) {
                $container = $containerCheck;
                break;
            }
        }

        if (!$container) {
            throw new WrongInputException(
                "The Backup doesn't contain the Container with the id " . $containerId
            );
        }

        //Check if a BackupSchedule is set, for no it's a manual backup
        if(!$backup->getBackupSchedule()){
            //TODO Manual Backup restore
        }else{
            //Backup Schedule Backup
            $destination = $backup->getDestination();
            $backupSchedule = $backup->getBackupSchedule();
            //Calling restore command with SSH
            $hostSSH->restoreBackupForTimestampInTmp($backup->getTimestamp(), $destination, $backupSchedule->getName(), $container->getName(), $container->getHost());
            //TODO Error response

            //Restoring image from tarball
            $hostSSH->createLXCImageFromTarball($backupSchedule->getName(), $container->getName(), $container->getHost());
            //TODO Error response

            //Create Container for the restored Image
            $container = $hostSSH->restoreContainerFromImage($container->getHost(), $container->getName());
            //TODO Error response

            $serializer = $this->get('jms_serializer');
            $response = $serializer->serialize($container, 'json');
            return new Response($response, Response::HTTP_CREATED);
        }
    }

}
