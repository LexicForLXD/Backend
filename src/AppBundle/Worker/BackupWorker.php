<?php

/**
 * Created by IntelliJ IDEA.
 * User: leon
 * Date: 10.05.18
 * Time: 21:06
 */

namespace AppBundle\Worker;


use AppBundle\Entity\Backup;
use AppBundle\Service\Backup\BackupService;
use AppBundle\Service\LxdApi\ImageApi;
use AppBundle\Service\LxdApi\OperationApi;
use AppBundle\Service\LxdApi\SnapshotApi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class BackupWorker extends BaseWorker
{
    protected $snapshotApi;
    protected $imageApi;
    protected $backupService;

    /**
     * BackupWorker constructor.
     * @param EntityManagerInterface $em
     * @param SnapshotApi $snapshotApi
     * @param OperationApi $operationApi
     * @param ImageApi $imageApi
     * @param BackupService $backupService
     */
    public function __construct(EntityManagerInterface $em, SnapshotApi $snapshotApi, OperationApi $operationApi, ImageApi $imageApi, BackupService $backupService, ValidatorInterface $validator)
    {
        parent::__construct($em, $operationApi, $validator);
        $this->snapshotApi = $snapshotApi;
        $this->imageApi = $imageApi;
        $this->backupService = $backupService;
    }

    public function getName()
    {
        return "backup";
    }

    /**
     * @param int $backupId
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function createManualBackup($backupId)
    {
        $backup = $this->em->getRepository(Backup::class)->find($backupId);
        $containers = $backup->getContainers();
        $host = $containers[0]->getHost();

        $this->backupService->makeTmpBackupFolder($host, $backup);

        foreach ($containers as $container) {
            $snapOp = $this->snapshotApi->create($host, $container, $backup->getManualBackupName(), false);
            if ($this->checkForErrors($snapOp)) {
                return;
            }
            $snapOpWait = $this->operationApi->getOperationsLinkWithWait($host, $snapOp->body->metadata->id);
            if ($this->checkForErrors($snapOpWait)) {
                return;
            }


            $imgOp = $this->imageApi->createImage($host, [
                "filename" => $backup->getManualBackupName() . '_' . $container->getName(),
                "source" => [
                    "type" => "snapshot",
                    "name" => $container->getName() . "/" . $backup->getManualBackupName()
                ]
            ]);
            if ($this->checkForErrors($imgOp)) {
                return;
            }
            $imgOpWait = $this->operationApi->getOperationsLinkWithWait($host, $imgOp->body->metadata->id);
            if ($this->checkForErrors($imgOpWait)) {
                return;
            }


            $fingerprint = $imgOpWait->body->metadata->metadata->fingerprint;
            $this->backupService->exportImageToTmp($host, $container, $backup, $fingerprint);

            $snapDelOp = $this->snapshotApi->delete($host, $container, $backup->getManualBackupName());
            if ($this->checkForErrors($snapDelOp)) {
                return;
            }
            $snapDelOpWait = $this->operationApi->getOperationsLinkWithWait($host, $snapDelOp->body->metadata->id);
            if ($this->checkForErrors($snapDelOpWait)) {
                return;
            }

            $imgDelOp = $this->imageApi->removeImageByFingerprint($host, $fingerprint);
            if ($this->checkForErrors($imgDelOp)) {
                return;
            }
            $imgDelOpWait = $this->operationApi->getOperationsLinkWithWait($host, $imgDelOp->body->metadata->id);
            if ($this->checkForErrors($imgDelOpWait)) {
                return;
            }
        }

        $this->backupService->makeDuplicityCall($host, $backup);
        $this->backupService->removeTmpBackupFolder($host, $backup);

        $backup->setTimestamp();
        if (!$this->validation($backup)) {
            $this->em->flush($backup);
        }
    }


}