<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Backup
 * @package AppBundle\Entity
 *
 * @ORM\Entity
 *
 */
class Backup
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var \DateTime
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
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @return BackupSchedule | null
     */
    public function getBackupSchedule() : ? BackupSchedule
    {
        return $this->backupSchedule;
    }

    /**
     * @param BackupSchedule $backupSchedule
     */
    public function setBackupSchedule(BackupSchedule $backupSchedule) : void
    {
        $this->backupSchedule = $backupSchedule;
    }

    /**
     * @return \DateTime
     */
    public function getTimestamp() : \DateTime
    {
        return $this->timestamp;
    }

    public function setTimestamp() : void
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
    public function removeContainer(Container $container)
    {
        if (!$this->containers->contains($container)) {
            return;
        }
        $this->containers->removeElement($container);
        $container->removeBackup($this);
    }

    /**
     * @return BackupDestination
     */
    public function getDestination() : BackupDestination
    {
        return $this->destination;
    }

    /**
     * @param BackupDestination $destination
     */
    public function setDestination($destination) : void
    {
        $this->destination = $destination;
    }

    /**
     * @return int
     *
     * @JMS\VirtualProperty()
     */
    public function getDestinationId()
    {
        if ($this->destination) {
            return $this->destination->getId();
        }
        return null;
    }

    /**
     * @return array
     *
     * @JMS\VirtualProperty()
     */
    public function getContainerId()
    {
        if ($this->containers->isEmpty()) {
            return null;
        }

        $this->containers->first();
        do {
            $ids[] = $this->containers->current()->getId();
        } while ($this->containers->next());

        return $ids;
    }

    /**
     * @return PersistentCollection
     */
    public function getContainers() : PersistentCollection
    {
        return $this->containers;
    }

    /**
     * @JMS\VirtualProperty()
     * @return int|null
     */
    public function getBackupScheduleId()
    {
        if ($this->backupSchedule) {
            return $this->backupSchedule->getId();
        }
        return null;
    }

    /**
     * @return null|string
     */
    public function getManualBackupName() : ? string
    {
        return $this->manualBackupName;
    }

    /**
     * @param null|string $manualBackupName
     */
    public function setManualBackupName($manualBackupName) : void
    {
        $this->manualBackupName = $manualBackupName;
    }
}