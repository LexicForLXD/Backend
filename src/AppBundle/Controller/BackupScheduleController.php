<?php

namespace AppBundle\Controller;



use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

use AppBundle\Entity\BackupSchedule;
use AppBundle\Entity\Container;
use AppBundle\Entity\BackupDestination;

use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Exception\WrongInputExceptionArray;

use AppBundle\Service\SSH\ScheduleSSH;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class BackupScheduleController extends BaseController
{

    /**
     * Create a new backup schedule.
     *
     * @Route("/schedules", methods={"POST"})
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param ScheduleSSH $sshApi
     * @return Response
     * @throws WrongInputExceptionArray
     */
    public function createBackupScheduleAction(Request $request, EntityManagerInterface $em, ScheduleSSH $sshApi)
    {

        $containers = $this->getDoctrine()->getRepository(Container::class)->findBy(["id" => $request->get('containers')]);

        $destination = $this->getDoctrine()->getRepository(BackupDestination::class)->find($request->get('destination'));

        $schedule = new BackupSchedule();
        $schedule->setName($request->get('name'));
        $schedule->setDescription($request->get('description'));
        $schedule->setExecutionTime($request->get('executionTime'));
        $schedule->setDestination($destination);
        $schedule->setType($request->get('type'));
        $schedule->setContainers($containers);
        $schedule->setWebhookUrl($this->generateUrl('create_backup_with_schedule_webhook', array('token' => $schedule->getToken()), UrlGeneratorInterface::ABSOLUTE_URL));

        $this->validation($schedule);

        $this->checkForSameHost($containers);

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
                'No backup schedule found for id ' . $scheduleId
            );
        }

        $sshApi->deleteAnacronFile($schedule);

        $em->remove($schedule);
        $em->flush();

        return new JsonResponse([], 204);
    }

    /**
     * Update a BackupSchedule on the Host.
     *
     * @Route("/schedules/{scheduleId}", methods={"PUT"})
     *
     * @param Request $request
     * @param integer $scheduleId
     * @param EntityManagerInterface $em
     * @param ScheduleSSH $sshApi
     * @return Response
     * @throws ElementNotFoundException
     * @throws WrongInputExceptionArray
     */
    public function updateBackupScheduleAction(Request $request, int $scheduleId, EntityManagerInterface $em, ScheduleSSH $sshApi)
    {
        $schedule = $this->getDoctrine()->getRepository(BackupSchedule::class)->find($scheduleId);

        if (!$schedule) {
            throw new ElementNotFoundException(
                'No backup schedule found for id ' . $scheduleId
            );
        }
        $oldSchedule = $schedule;

        $containers = $this->getDoctrine()->getRepository(Container::class)->findBy(["id" => $request->get('containers')]);


        if (!$containers) {
            throw new WrongInputExceptionArray([
                'containers' => 'No container found. You must specify at least one container to use a BackupSchedule.'
            ]);
        }


        $schedule->setName($request->get('name'));
        $schedule->setDescription($request->get('description'));
        $schedule->setExecutionTime($request->get('executionTime'));
        $schedule->setDestination($request->get('destination'));
        $schedule->setType($request->get('type'));
        $schedule->setContainers($containers);

        $this->validation($schedule);

        $this->checkForSameHost($containers);

        $em->flush($schedule);


        $sshApi->deleteAnacronFile($oldSchedule);
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
     * @param integer $scheduleId
     * @return Response
     * @throws ElementNotFoundException
     */
    public function showBackupScheduleAction(int $scheduleId)
    {
        $schedule = $this->getDoctrine()->getRepository(BackupSchedule::class)->find($scheduleId);

        if (!$schedule) {
            throw new ElementNotFoundException(
                'No backup schedule found for id ' . $scheduleId
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
     * @return Response
     * @throws ElementNotFoundException
     */
    public function indexBackupScheduleAction()
    {
        $schedules = $this->getDoctrine()->getRepository(BackupSchedule::class)->findAll();

        if (!$schedules) {
            throw new ElementNotFoundException(
                'No backup schedules found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($schedules, 'json');
        return new Response($response);
    }





    /**
     * Checks whether all containers are on the same host.
     *
     * @param $containers
     * @return bool
     * @throws WrongInputExceptionArray
     */
    private function checkForSameHost($containers)
    {
        $host = $containers[0]->getHost();

        foreach ($containers as $container) {
            if ($container->getHost() != $host) {
                throw new WrongInputExceptionArray(["containers" => "The selected containers are not on the same host."]);
            }
        }
        return true;
    }
}