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
use Dtc\QueueBundle\Model\Worker;

class BackupWorker extends Worker
{
    protected $em;
    protected $operationApi;
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
    public function __construct(EntityManagerInterface $em, SnapshotApi $snapshotApi, OperationApi $operationApi, ImageApi $imageApi, BackupService $backupService)
    {
        $this->em = $em;
        $this->operationApi = $operationApi;
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
        $backup = $this->getDoctrine()->getRepository(Backup::class)->find($backupId);
        $containers = $backup->getContainers();
        $host = $containers[0]->getHost();

        $this->backupService->makeTmpBackupFolder($host, $backup);

        foreach ($containers as $container) {
            $snapshotOperation = $this->snapshotApi->create($host, $container, $backup->getManualBackupName());
            $this->operationApi->getOperationsLinkWithWait($host, $snapshotOperation->body->metadata->id);


            $imageOperation = $this->imageApi->createImage($host, [
                "filename" => $backup->getManualBackupName() . '_' . $container->getName(),
                "source" => [
                    "type" => "snapshot",
                    "name" => $container->getName() . "/" . $backup->getManualBackupName()
                ]
            ]);
            $operationResult = $this->operationApi->getOperationsLinkWithWait($host, $imageOperation->body->metadata->id);


            $fingerprint = $operationResult->body->metadata->metadata->fingerprint;

            $this->backupService->exportImageToTmp($host, $container, $backup, $fingerprint);


            $snapshotDeleteOperation = $this->snapshotApi->delete($host, $container, $backup->getManualBackupName());
            $this->operationApi->getOperationsLinkWithWait($host, $snapshotDeleteOperation->body->metadata->id);


            $imageDeleteOperation = $this->imageApi->removeImageByFingerprint($host, $fingerprint);
            $this->operationApi->getOperationsLinkWithWait($host, $imageDeleteOperation->body->metadata->id);

        }

        $this->backupService->makeDuplicityCall($host, $backup);

        $this->backupService->removeTmpBackupFolder($host, $backup);

        $backup->setTimestamp();
        $this->em->flush($backup);
    }
}