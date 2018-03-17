<?php
namespace AppBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as OAS;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use AppBundle\Entity\BackupSchedule;
use AppBundle\Entity\Container;
use AppBundle\Entity\BackupDestination;

use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Exception\WrongInputExceptionArray;

use AppBundle\Service\SSH\ScheduleSSH;


class BackupScheduleController extends Controller
{

    /**
     * Create a new backup schedule.
     *
     * @Route("/schedules", methods={"POST"})
     *
     * @OAS\Post(path="/schedules", tags={"backups"},
     *
     *  @OAS\Parameter(
     *      description="body for backupschedule",
     *      in="body",
     *      name="bodyCreateSchedule",
     *      @OAS\Schema(
     *          @OAS\Property(
     *              property="name",
     *              type="string",
     *          ),
     *          @OAS\Property(
     *              property="description",
     *              type="string",
     *          ),
     *          @OAS\Property(
     *              property="executionTime",
     *              type="string",
     *          ),
     *          @OAS\Property(
     *              property="type",
     *              type="string",
     *          ),
     *          @OAS\Property(
     *              property="destination",
     *              type="string",
     *          ),
     *          @OAS\Property(
     *              property="containers",
     *              type="array",
     *          ),
     *      ),
     *  ),
     *
     *  @OAS\Response(
     *      description="Created BackupSchedule",
     *      response=201
     *  ),
     *  @OAS\Response(
     *      description="Nonvalid input data",
     *      response=400
     *  ),
     *  @OAS\Response(
     *      description="Container not found",
     *      response=404
     *  ),
     * )
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param ScheduleSSH $sshApi
     * @return Response
     * @throws ElementNotFoundException
     * @throws WrongInputException
     */
    public function createBackupScheduleAction(Request $request, EntityManagerInterface $em, ScheduleSSH $sshApi)
    {

        $containers = $this->getDoctrine()->getRepository(Container::class)->findBy(["id" => $request->get('containers')]);

        if (!$containers) {
            throw new ElementNotFoundException(
                'No container found. You must specify at least one container to use a BackupSchedule.'
            );
        }

        $destination = $this->getDoctrine()->getRepository(BackupDestination::class)->find($request->get('destination'));

        if (!$destination) {
            throw new ElementNotFoundException(
                'No backup destination found for ID .' . $request->get('destination') . '. You can create a backup destination with another endpoint.'
            );
        }


        $schedule = new BackupSchedule();
        $schedule->setName($request->get('name'));
        $schedule->setDescription($request->get('description'));
        $schedule->setExecutionTime($request->get('executionTime'));
        $schedule->setDestination($destination);
        $schedule->setType($request->get('type'));
        $schedule->setContainers($containers);
        $schedule->setWebhookUrl($this->generateUrl('create_backup_with_schedule_webhook', array('token' => $schedule->getToken()), UrlGerneratorInterface::ABSOLUTE_URL));

        $this->validation($schedule);

        $em->persist($schedule);
        $em->flush();



        $sshApi->sendAnacronFile($schedule);
        $sshApi->makeFileExecuteable($schedule);

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($schedule, 'json');
        return new Response($response);
    }

    /**
     * Delete an existing BackupSchedule
     *
     * @Route("/schedules/{scheduleId}", methods={"DELETE"})
     *
     * @OAS\Delete(path="/schedules/{scheduleId}", tags={"backups"},
     *  @OAS\Parameter(
     *      description="Which schedule should be deleted",
     *      in="path",
     *      name="scheduleId",
     *      @OAS\Schema(
     *          type="integer"
     *      ),
     *  ),
     *  @OAS\Response(
     *      description="Success message",
     *      response=204
     *  ),
     *  @OAS\Response(
     *      description="Schedule not found",
     *      response=404
     *  ),
     * )
     *
     * @param integer $scheduleId
     * @param EntityManagerInterface $em
     * @param ScheduleSSH $sshApi
     * @return JsonResponse
     * @throws ElementNotFoundException
     */
    public function deleteBackupScheduleAction(int $scheduleId, EntityManagerInterface $em, ScheduleSSH $sshApi)
    {
        $schedule = $this->getDoctrine()->getRepository(BackupSchedule::class)->find($scheduleId);

        if (!$schedule) {
            throw new ElementNotFoundException(
                'No backupschedule found for id ' . $scheduleId
            );
        }

        $sshApi->deleteAnacronFile($schedule);

        $em->remove($schedule);
        $em->flush();

        return JsonResponse(["message" => "successful deleted", 204]);
    }

    /**
     * Update a BackupSchedule on the Host.
     *
     * @Route("/schedules/{scheduleId}", methods={"PUT"})
     *
     * @OAS\Put(path="/schedules/{scheduleId}", tags={"backups"},
     *  @OAS\Parameter(
     *      description="Which schedule should be updated",
     *      in="path",
     *      name="scheduleId",
     *      @OAS\Schema(
     *          type="integer"
     *      ),
     *  ),
     *  @OAS\Parameter(
     *      description="body for backupschedule",
     *      in="body",
     *      name="bodyCreateSchedule",
     *      @OAS\Schema(
     *          @OAS\Property(
     *              property="name",
     *              type="string",
     *          ),
     *          @OAS\Property(
     *              property="description",
     *              type="string",
     *          ),
     *          @OAS\Property(
     *              property="executionTime",
     *              type="string",
     *          ),
     *          @OAS\Property(
     *              property="type",
     *              type="string",
     *          ),
     *          @OAS\Property(
     *              property="destination",
     *              type="string",
     *          ),
     *          @OAS\Property(
     *              property="containers",
     *              type="array",
     *          ),
     *      ),
     *  ),
     *
     *  @OAS\Response(
     *      description="Updated BackupSchedule",
     *      response=200
     *  ),
     *  @OAS\Response(
     *      description="Nonvalid input data",
     *      response=400
     *  ),
     *  @OAS\Response(
     *      description="Container or Schedule not found",
     *      response=404
     *  ),
     * )
     *
     *
     * @param Request $request
     * @param integer $scheduleId
     * @param EntityManagerInterface $em
     * @param ScheduleSSH $sshApi
     * @return JsonResponse
     * @throws ElementNotFoundException
     * @throws WrongInputException
     */
    public function updateBackupScheduleAction(Request $request, int $scheduleId, EntityManagerInterface $em, ScheduleSSH $sshApi)
    {
        $schedule = $this->getDoctrine()->getRepository(BackupSchedule::class)->find($scheduleId);

        if (!$schedule) {
            throw new ElementNotFoundException(
                'No backupschedule found for id ' . $scheduleId
            );
        }

        $containers = $this->getDoctrine()->getRepository(Container::class)->findBy(["id" => $request->get('containers')]);

        if (!$containers) {
            throw new ElementNotFoundException(
                'No container found. You must specify at least one container to use a BackupSchedule.'
            );
        }

        $sshApi->deleteAnacronFile($schedule);

        $schedule->setName($request->get('name'));
        $schedule->setDescription($request->get('description'));
        $schedule->setExecutionTime($request->get('executionTime'));
        $schedule->setDestination($request->get('destination'));
        $schedule->setType($request->get('type'));
        $schedule->setContainers($containers);


        $this->validation($schedule);

        $em->flush($schedule);

        $sshApi->sendAnacronFile($schedule);
        $sshApi->makeFileExecuteable($schedule);

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($schedule, 'json');
        return new Response($response);
    }

    /**
     * Show a Single Backup Schedule
     *
     * @Route("/schedules/{scheduleId}", methods={"GET"})
     *
     * @OAS\Get(path="/schedules/{scheduleId}", tags={"backups"},
     *  @OAS\Parameter(
     *      description="Which schedule should be shown",
     *      in="path",
     *      name="scheduleId",
     *      @OAS\Schema(
     *          type="integer"
     *      ),
     *  ),
     *  @OAS\Response(
     *      description="one schedule",
     *      response=200
     *  ),
     *  @OAS\Response(
     *      description="Schedule not found",
     *      response=404
     *  ),
     * )
     *
     * @param integer $scheduleId
     * @return Response
     * @throws ElementNotFoundException
     */
    public function showBackupScheduleAction(int $scheduleId)
    {
        $schedule = $this->getDoctrine()->getRepository(BackupSchedule::class)->find($scheduleId);

        if (!$schedule) {
            throw new ElementNotFoundException(
                'No backupschedule found for id ' . $scheduleId
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($schedule, 'json');
        return new Response($response);
    }

    /**
     * List all BackupSchedules
     *
     * @Route("/schedules", methods={"GET"})
     *
     * @OAS\Get(path="/schedules", tags={"backups"},
     *  @OAS\Response(
     *      description="All schedules",
     *      response=200
     *  ),
     *  @OAS\Response(
     *      description="Schedules not found",
     *      response=404
     *  ),
     * )
     *
     * @return Response
     * @throws ElementNotFoundException
     */
    public function indexBackupScheduleAction()
    {
        $schedules = $this->getDoctrine()->getRepository(BackupSchedule::class)->findAll();

        if (!$schedules) {
            throw new ElementNotFoundException(
                'No backupschedules found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($schedules, 'json');
        return new Response($response);
    }


    /**
     * Validates a BackupSchedule Object and returns array with errors.
     *
     * @param BackupSchedule $object
     * @return array|bool
     */
    private function validation(BackupSchedule $object)
    {
        $validator = $this->get('validator');
        $errors = $validator->validate($object);

        if (count($errors) > 0) {
            $errorArray = array();
            foreach ($errors as $error) {
                $errorArray[$error->getPropertyPath()] = $error->getMessage();
            }
            throw new WrongInputExceptionArray($errorArray);
            return $errorArray;

        }
        return false;
    }
}