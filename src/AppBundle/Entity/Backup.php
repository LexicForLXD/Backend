<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
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
     * @OAS\Property(example="2017-10-03T00:12:00+01:00")
     *
     * @ORM\Column(type="date", nullable=false)
     * @Assert\NotNull
     */
    protected $timestamp;

    /**
     * @var string
     * @OAS\Property(example="/backups/46876a46467645as6d3763.tar.gz")
     *
     * @ORM\Column(type="string", nullable=false)
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
}