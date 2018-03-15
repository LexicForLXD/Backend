<?php

namespace AppBundle\Controller;


use AppBundle\Entity\Backup;
use AppBundle\Entity\Host;
use AppBundle\Entity\Image;
use AppBundle\Entity\ImageAlias;
use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Exception\WrongInputException;
use AppBundle\Service\LxdApi\ImageApi;
use AppBundle\Service\Restore\RestoreService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class RestoreController extends Controller
{
    /**
     * Get all files in a duplicity backup
     *
     * @Route("/restores/backups/{backupID}", name="restore_all_files_in_backup", methods={"GET"})
     * @param $backupID
     * @param RestoreService $restoreAPI
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

        if(strpos($result, 'Error') !== false){
            throw new WrongInputException($result);
        }
    }

    /**
     * Restore Backups created via BackupSchedule
     *
     * @Route("/restores/backups/{backupID}", name="create_container_from_backup", methods={"POST"})
     * @param Request $request
     * @throws WrongInputException
     * @throws ElementNotFoundException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function createContainerFromBackup($backupID, Request $request, EntityManagerInterface $entityManager, RestoreService $restoreService, ImageApi $imageApi){
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
        $result = $restoreService->createLXCImageFromTarball($host ,$backup, $containerName);
        if(strpos($result, 'error') !== false){
            $image = new Image();
            //Get fingerprint
            $fingerprint = str_replace('Image imported with fingerprint: ', '', $result);
            $image->setFingerprint($fingerprint);

            $result = $imageApi->getImageByFingerprint($host ,$fingerprint);

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
            $imageAlias->setDescription('Restored Image for Container '.$containerName.' from Backup - '.date_format($backup->getTimestamp(), DATE_ISO8601));

            $imageAlias->setImage($image);
            $image->addAlias($imageAlias);

            $entityManager->persist($image);
            $entityManager->persist($imageAlias);

            $entityManager->flush();
        }else{
            throw new WrongInputException("Couldn't import LXC Image from tarball - LXC Error : ".$result);
        }
    }
}