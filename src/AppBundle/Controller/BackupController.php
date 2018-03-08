<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Backup;
use AppBundle\Entity\BackupSchedule;
use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Exception\ForbiddenException;
use AppBundle\Exception\WrongInputException;
use AppBundle\Exception\WrongInputExceptionArray;
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
     * Webhook to create a new Backup object
     *
     * @Route("/backups", name="create_backup_webhook", methods={"POST"})
     *
     * @param Request $request
     * @return Response
     * @throws ForbiddenException
     *
     * @OAS\Post(path="/backups?path={path}&token={token}",
     * tags={"backups"},
     * @OAS\Parameter(
     *      description="The path to the backup file created by duplicity",
     *      name="path",
     *      in="query",
     *      required=true,
     *      @OAS\Schema(
     *          @OAS\Property(
     *              property="path",
     *              type="string",
     *          ),
     *      ),
     * ),
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
     *  description="The provided parameters are invalid or the path is missing",
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
     * @throws WrongInputException
     * @throws WrongInputExceptionArray
     */
    public function backupCreationWebhook(Request $request){
        $token = $request->query->get('token');

        $backupSchedules = $this->getDoctrine()->getRepository(BackupSchedule::class)->findBy(["token" => $token]);

        if (!$backupSchedules) {
            throw new ForbiddenException(
                'Invalid backup token'
            );
        }

        //Token is unique, there can be only one result
        $backupSchedule = $backupSchedules[0];

        //Validate path
        if (!$request->query->has('path')){
            throw new WrongInputException(
                'Backup file path is missing'
            );
        }

        $path = $request->query->get('path');

        $backup = new Backup();
        $backup->setBackupSchedule($backupSchedule);
        $backup->setFilePath($path);
        $backup->setTimestamp();

        if ($errorArray = $this->validation($backup)) {
            throw new WrongInputExceptionArray($errorArray);
        }

        $em = $this->getDoctrine()->getManager();

        $em->persist($backup);
        $em->flush();

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
