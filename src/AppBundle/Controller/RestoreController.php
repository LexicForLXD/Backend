<?php

namespace AppBundle\Controller;


use AppBundle\Entity\Backup;
use AppBundle\Entity\Host;
use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Exception\WrongInputException;
use AppBundle\Service\Restore\RestoreService;
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
     * @Route("/restores/backups/{backupID}", name="create_container_from_backup", methods={"POST"})
     * @param Request $request
     * @throws WrongInputException
     * @throws ElementNotFoundException
     */
    public function createContainerFromBackup($backupID, Request $request, RestoreService $restoreService){
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
    }
}