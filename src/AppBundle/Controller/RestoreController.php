<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Backup;
use AppBundle\Entity\Container;
use AppBundle\Entity\Host;
use AppBundle\Entity\Image;
use AppBundle\Entity\ImageAlias;
use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Exception\WrongInputException;
use AppBundle\Service\LxdApi\ContainerApi;
use AppBundle\Service\LxdApi\ImageApi;
use AppBundle\Service\Restore\RestoreService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as OAS;

class RestoreController extends Controller
{
    /**
     * Get all files in a duplicity backup
     *
     * @OAS\Get(path="/restores/backups/{id}",
     *     tags={"backup-restore"},
     *     @OAS\Parameter(
     *      description="ID of the Backup",
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
     *          description="Array of all Container backup tarballs included in the specified duplicity Backup",
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No Backup for the id found",
     *      ),
     *      @OAS\Response(
     *          response=400,
     *          description="Error getting the list of files from the duplicity backup",
     *      ),
     * )
     *
     * @Route("/restores/backups/{backupID}", name="restore_all_files_in_backup", methods={"GET"})
     * @param $backupID
     * @param RestoreService $restoreService
     * @return Response
     * @throws ElementNotFoundException
     * @throws WrongInputException
     */
    public function listAllFilesInBackup($backupID, RestoreService $restoreService){
        $backup = $this->getDoctrine()->getRepository(Backup::class)->find($backupID);

        if (!$backup) {
            throw new ElementNotFoundException(
                'No Backup for id '.$backupID .' found'
            );
        }

        $result = $restoreService->getFilesInBackupForTimestamp($backup);

        if(is_array($result) == false){
            throw new WrongInputException($result);
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($result, 'json');
        return new Response($response, Response::HTTP_OK);
    }

    /**
     * Restore Backups created via BackupSchedule
     *
     * @OAS\Post(path="/restores/backups/{id}",
     * tags={"backup-restore"},
     * @OAS\Parameter(
     *      description="ID of the Backup",
     *      name="id",
     *      in="path",
     *      required=true,
     *      @OAS\Schema(
     *          @OAS\Property(
     *              property="id",
     *              type="int",
     *          ),
     *      ),
     * ),
     * @OAS\Parameter(
     *      description="Body for the Backup restore",
     *      in="body",
     *      name="bodyRestoreBackup",
     *      required=true,
     *      @OAS\Schema(
     *          @OAS\Property(
     *              property="tarball",
     *              example="TestContainer.tar.gz",
     *              description="You can receive a list of all available tarballs in the Backup via the get endpoint",
     *              type="string",
     *          ),
     *          @OAS\Property(
     *              property="containerName",
     *              example="My-Restored-Container",
     *              description="Name of the new Container, which gets created based on the restored Image",
     *              type="string",
     *          ),
     *          @OAS\Property(
     *              property="hostID",
     *              example="2",
     *              description="ID of the Host, where the new Container should be created",
     *              type="int",
     *          ),
     *      ),
     *  ),
     * @OAS\Response(
     *  description="One of the body parameters is missing or an error occurred in the image restore process or in the Container creation",
     *  response=400
     * ),
     * @OAS\Response(
     *  description="No Backup for the ID found or no Host for the ID found",
     *  response=404
     * ),
     * @OAS\Response(
     *  description="The new Container was successfully created",
     *  response=201,
     *  @OAS\JsonContent(ref="#/components/schemas/container"),
     * ),
     * )
     *
     * @Route("/restores/backups/{backupID}", name="create_container_from_backup", methods={"POST"})
     * @param $backupID
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param RestoreService $restoreService
     * @param ImageApi $imageApi
     * @param ContainerApi $containerApi
     * @return Response
     * @throws ElementNotFoundException
     * @throws WrongInputException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function createContainerFromBackup($backupID, Request $request, EntityManagerInterface $entityManager, RestoreService $restoreService, ImageApi $imageApi, ContainerApi $containerApi){
        if(!$request->request->has('tarball') || !$request->request->has('containerName') || !$request->request->has('hostID')) {
            throw new WrongInputException('At least one of the required parameters in the body is missing');
        }

        $backup = $this->getDoctrine()->getRepository(Backup::class)->find($backupID);
        if (!$backup) {
            throw new ElementNotFoundException(
                'No Backup for id '.$backupID .' found'
            );
        }

        $host = $this->getDoctrine()->getRepository(Host::class)->find($request->request->get('hostID'));
        if (!$host) {
            throw new ElementNotFoundException(
                'No Host for id '.$request->request->get('hostID') .' found'
            );
        }

        $tarball = $request->request->get('tarball');
        $containerName  = $request->request->get('containerName');

        $restoreService->restoreBackupForTimestampInTmp($host, $containerName, $tarball, $backup);

        //Restoring image from tarball
        $result = $restoreService->createLXCImageFromTarball($host ,$containerName, $backup);

        if (strpos($result, 'error') !== false) {
            throw new WrongInputException("Couldn't import LXC Image from tarball - LXC Error : " . $result);
        }

        $image = new Image();
        //Get fingerprint
        $fingerprint = str_replace('Image imported with fingerprint: ', '', $result);
        //Remove line break \n at the end of the string
        $fingerprint = preg_replace( "/\r|\n/", "", $fingerprint );

        $image->setFingerprint($fingerprint);

        $result = $imageApi->getImageByFingerprint($host, $fingerprint);

        $image->setFilename($result->body->metadata->architecture);
        $image->setProperties($result->body->metadata->properties);
        $image->setPublic($result->body->metadata->public);
        $image->setHost($host);
        $image->setArchitecture($result->body->metadata->architecture);
        $image->setSize($result->body->metadata->size);
        $image->setFinished(true);

        $entityManager->persist($image);

        $imageAlias = new ImageAlias();
        $imageAlias->setName($containerName);
        $imageAlias->setDescription('Restored Image for Container ' . $containerName . ' from Backup - ' . date_format($backup->getTimestamp(), DATE_ISO8601));

        $imageAlias->setImage($image);
        $image->addAlias($imageAlias);

        $entityManager->persist($image);
        $entityManager->persist($imageAlias);

        $entityManager->flush();

        //Create Container for the restored Image
        $result = $restoreService->restoreContainerFromImage($host, $containerName);
        if (strpos($result, 'error') !== false) {
            $error = substr(strpos($result, 'error'), $result);
            throw new WrongInputException("Couldn't create Container from the imported Image - LXC Error : " . $error);
        }

        $result = $containerApi->show($host, $containerName);

        $container = new Container();
        $container->setName($containerName);
        $container->setHost($host);
        $container->setSettings($result->body->metadata);
        $container->setState(strtolower($result->body->metadata->status));

        $container->setImage($image);
        $image->addContainer($container);

        $entityManager->persist($container);
        $entityManager->merge($image);

        $entityManager->flush();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($container, 'json');
        return new Response($response, Response::HTTP_CREATED);
    }
}