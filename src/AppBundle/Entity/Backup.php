<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Backup
 * @package AppBundle\Entity
 *
 * @ORM\Entity
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
     * @var string
     *
     * @ORM\Column(type="date", nullable=false)
     * @Assert\NotNull
     * @Assert\Date()
     */
    protected $lastExecuted;

    /**
     * @var string
     *
     * @Assert\NotNull
     * @Assert\NotBlank()
     * @Assert\Type("string")
     */
    protected $filePath;

    /**
     * @var BackupSchedule
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\BackupSchedule")
     * @ORM\JoinColumn(name="backup_schedule_id", referencedColumnName="id")
     * @JMS\Exclude()
     */
    protected $backupSchedule;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getLastExecuted(): string
    {
        return $this->lastExecuted;
    }

    /**
     * @param string $lastExecuted
     */
    public function setLastExecuted($lastExecuted): void
    {
        $this->lastExecuted = $lastExecuted;
    }

    /**
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * @param string $filePath
     */
    public function setFilePath($filePath): void
    {
        $this->filePath = $filePath;
    }

    /**
     * @return BackupSchedule
     */
    public function getBackupSchedule(): BackupSchedule
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
}