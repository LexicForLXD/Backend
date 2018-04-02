<?php
/**
 * Created by IntelliJ IDEA.
 * User: leon
 * Date: 02.04.18
 * Time: 19:58
 */

namespace AppBundle\EventListener;

use AppBundle\Entity\Container;
use AppBundle\Entity\Host;
use AppBundle\Event\ManualBackupEvent;
use AppBundle\Service\LxdApi\ImageApi;
use AppBundle\Service\LxdApi\OperationApi;
use AppBundle\Service\LxdApi\SnapshotApi;
use AppBundle\Service\Restore\BackupService;
use Doctrine\ORM\EntityManager;

class BackupListener
{
    protected $em;
    protected $operationApi;
    protected $snapshotApi;
    protected $imageApi;
    protected $backupService;

    public function __construct(EntityManager $em, SnapshotApi $snapshotApi, OperationApi $operationApi, ImageApi $imageApi, BackupService $backupService)
    {
        $this->em = $em;
        $this->operationApi = $operationApi;
        $this->snapshotApi = $snapshotApi;
        $this->imageApi = $imageApi;
        $this->backupService = $backupService;
    }

    /**
     * @param ManualBackupEvent $event
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function onManualBackup(ManualBackupEvent $event)
    {
        echo "START MANUAL BACKUP: " . $event->getBackup()->getManualBackupName();

        $containers = $event->getBackup()->getContainers();

        $makeFolderResult = $this->backupService->makeTmpBackupFolder($event->getHost(), $event->getBackup());


        foreach ($containers as $container) {
            $snapshotOperation = $this->snapshotApi->create($event->getHost(), $container, $event->getBackup()->getManualBackupName());
            $operationResult = $this->operationApi->getOperationsLinkWithWait($event->getHost(), $snapshotOperation->body->metadata->id);


            $imageOperation = $this->imageApi->createImage($event->getHost(), [
                "filename" => $event->getBackup()->getManualBackupName() . '_' . $container->getName(),
                "source" => [
                    "type" => "snapshot",
                    "name" => $container->getName() . "/" . $event->getBackup()->getManualBackupName()
                ]
            ]);
            $operationResult = $this->operationApi->getOperationsLinkWithWait($event->getHost(), $imageOperation->body->metadata->id);


            $fingerprint = $operationResult->body->metadata->metadata->fingerprint;

            $imageExportResult = $this->backupService->exportImageToTmp($event->getHost(), $container, $event->getBackup(), $fingerprint);


            $snapshotDeleteOperation = $this->snapshotApi->delete($event->getHost(), $container, $event->getBackup()->getManualBackupName());
            $operationResult = $this->operationApi->getOperationsLinkWithWait($event->getHost(), $snapshotDeleteOperation->body->metadata->id);


            $imageDeleteOperation = $this->imageApi->removeImageByFingerprint($event->getHost(), $fingerprint);
            $operationResult = $this->operationApi->getOperationsLinkWithWait($event->getHost(), $imageDeleteOperation->body->metadata->id);

        }

        $duplicityResult = $this->backupService->makeDuplicityCall($event->getHost(), $event->getBackup());

        $removeFolderResult = $this->backupService->removeTmpBackupFolder($event->getHost(), $event->getBackup());

        $backup = $this->em->getRepository(Backup::class)->find($event->getBackup()->getId());
        $backup->setTimestamp();
        $this->em->flush($backup);


        echo "FINISH MANUAL BACKUP: " . $event->getBackup()->getManualBackupName();
    }


}