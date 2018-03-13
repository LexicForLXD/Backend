<?php
namespace AppBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as OAS;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\BackupSchedule;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Exception\WrongInputExceptionArray;
use AppBundle\Service\SSH\HostSSH;


class BackupScheduleController extends Controller
{

    /**
     * @Route("/containers/{containerId}/backups/schedules", methods={"POST"})
     *
     * @OAS\Post(path="/containers/{containerId}/backups/schedules", tags={"backups"},
     *  @OAS\Parameter(
     *      description="Which container should be used",
     *      in="path",
     *      name="containerId",
     *      @OAS\Schema(
     *          type="integer"
     *      ),
     *  ),
     *  @OAS\Parameter(
     *      description="body for backupschedule for single container",
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
     *      ),
     *  ),
     *
     *  @OAS\Response(
     *      description="Success message",
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
     */
    public function createBackupScheduleSingleContainerAction(Request $request, int $containerId, EntityManagerInterface $em, HostSSH $sshApi)
    {
        $container = $this->getDoctrine()->getRepository(Container::class)->find($containerId);

        if (!$container) {
            throw new ElementNotFoundException(
                'No container found for id ' . $containerId
            );
        }


        $backupschedule = new BackupSchedule();
        $backupschedule->setName($request->get('name'));
        $backupschedule->setDescription($request->get('description'));
        $backupschedule->setExecutionTime($request->get('executionTime'));
        $backupschedule->setDestination($request->get('destination'));
        $backupschedule->setType($request->get('type'));
        $backupschedule->addContainer($container);

        $this->validation($container);

        $em->persist($backupschedule);
        $em->flush();

        $webhookUrl = $this->generateUrl('create_backup_with_schedule_webhook', array('token' => $backupschedule->getToken()), UrlGerneratorInterface::ABSOLUTE_URL);

        $commandText = $backupschedule->getShellCommands($webhookUrl);

        $sshApi->sendAnacronFile($container, $commandText, $backupschedule->getExecutionTime());
        $sshApi->makeFileExecuteable($container, $backupschedule->getExecutionTime());


    }


    /**
     * Validates a BackupSchedule Object and returns array with errors.
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