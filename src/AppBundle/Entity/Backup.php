<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as OAS;

/**
 * Class Backup
 * @package AppBundle\Entity
 *
 * @ORM\Entity
 *
 * @OAS\Schema(schema="backup", type="object")
 */
class Backup
{
    /**
     * @var int
     * @OAS\Property(example="1")
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var \DateTime
     * @OAS\Property(example="2018-03-10T22:40:28+00:00")
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @Assert\NotNull
     */
    protected $timestamp;

    /**
     * @var BackupSchedule | null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\BackupSchedule")
     * @ORM\JoinColumn(name="backup_schedule_id", referencedColumnName="id")
     * @JMS\Exclude()
     */
    protected $backupSchedule;

    /**
     * @var string | null
     *
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @Assert\Type(type="string")
     */
    protected $manualBackupName;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Container", inversedBy="backups")
     * @ORM\JoinTable(
     *  joinColumns={
     *      @ORM\JoinColumn(name="backup_id", referencedColumnName="id")
     *  },
     *  inverseJoinColumns={
     *      @ORM\JoinColumn(name="container_id", referencedColumnName="id")
     *  }
     * )
     * @JMS\Exclude()
     */
    protected $containers;

    /**
     * @var BackupDestination
     *
     * @ORM\ManyToOne(targetEntity="BackupDestination", inversedBy="backup")
     * @ORM\JoinColumn(name="destination_id", referencedColumnName="id")
     * @Assert\NotNull
     * @JMS\Exclude()
     */
    protected $destination;

    /**
     * Backup constructor.
     */
    public function __construct()
    {
        $this->containers = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return BackupSchedule | null
     */
    public function getBackupSchedule(): ?BackupSchedule
    {
        return $this->backupSchedule;
    }

    /**
     * @param BackupSchedule $backupSchedule
     */
    public function setBackupSchedule(BackupSchedule $backupSchedule): void
    {
        $this->backupSchedule = $backupSchedule;
    }

    /**
     * @return \DateTime
     */
    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    public function setTimestamp(): void
    {
        $this->timestamp = new \DateTime("now");
    }

    /**
     * @param Container $container
     */
    public function addContainer(Container $container)
    {
        if ($this->containers->contains($container)) {
            return;
        }
        $this->containers->add($container);
        $container->addBackup($this);
    }

    /**
     * @param Container $container
     */
    public function removeContainer(Container $container){
        if (!$this->containers->contains($container)) {
            return;
        }
        $this->containers->removeElement($container);
        $container->removeBackup($this);
    }

    /**
     * @return BackupDestination
     */
    public function getDestination(): BackupDestination
    {
        return $this->destination;
    }

    /**
     * @param BackupDestination $destination
     */
    public function setDestination($destination): void
    {
        $this->destination = $destination;
    }

    /**
     * @return int
     *
     * @OAS\Property(property="destinationId", example="1")
     *
     * @JMS\VirtualProperty()
     */
    public function getDestinationId(){
        if($this->destination){
            return $this->destination->getId();
        }
        return null;
    }

    /**
     * @return array
     *
     * @OAS\Property(property="containerId", example="[1]")
     *
     * @JMS\VirtualProperty()
     */
    public function getContainerId(){
        if($this->containers->isEmpty()){
            return null;
        }

        $this->containers->first();
        do{
            $ids[] = $this->containers->current()->getId();
        }while($this->containers->next());

        return $ids;
    }

    /**
     * @return PersistentCollection
     */
    public function getContainers(): PersistentCollection
    {
        return $this->containers;
    }

    /**
     * @OAS\Property(property="backupScheduleId", example="2")
     * @JMS\VirtualProperty()
     * @return int|null
     */
    public function getBackupScheduleId(){
        if($this->backupSchedule){
            return $this->backupSchedule->getId();
        }
        return null;
    }

    /**
     * @return null|string
     */
    public function getManualBackupName(): ?string
    {
        return $this->manualBackupName;
    }

    /**
     * @param null|string $manualBackupName
     */
    public function setManualBackupName($manualBackupName): void
    {
        $this->manualBackupName = $manualBackupName;
    }
}