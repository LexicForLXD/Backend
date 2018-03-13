<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;
use AppBundle\Entity\BackupSchedule;

/**
 * Class BackupDestination
 * @package AppBundle\Entity
 *
 * @ORM\Entity
 */
class BackupDestination
{
    /**
     * id
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @var integer
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true, nullable=false)
     * @Assert\NotNull
     * @Assert\NotBlank()
     * @Assert\Type("string")
     */
    protected $name;

    /**
     * @var string | null
     *
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Type("string")
     */
    protected $description;

    /**
     * which protocol should be used
     *
     * @ORM\Column(type="string")
     * @Assert\NotNull
     * @Assert\NotBlank()
     * @Assert\Type("string")
     * @var string
     */
    protected $protocol;

    /**
     * username for authentification
     *
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Type("string")
     * @var string
     */
    protected $username;

    /**
     * password for authentification
     *
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Type("string")
     * @var string
     */
    protected $password;


    /**
     * hostname of backup destination
     *
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $hostname;


    /**
     * path to backups
     *
     * @ORM\Column(type="string")
     * @var string
     */
    protected $path;



    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="BackupSchedule", mappedBy="destination")
     * @JMS\Exclude()
     */
    protected $backupSchedules;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Backup", mappedBy="destination")
     * @JMS\Exclude()
     */
    protected $backups;

    public function __constructor()
    {
        $this->backupSchedules = new ArrayCollection();
        $this->backups = new ArrayCollection();
    }



    /**
     * Get id
     *
     * @return  integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param  integer  $id  id
     *
     * @return  self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of name
     *
     * @return  string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @param  string  $name
     *
     * @return  self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get | null
     *
     * @return  string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set | null
     *
     * @param  string  $description  | null
     *
     * @return  self
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get which protocol should be used
     *
     * @return  string
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * Set which protocol should be used
     *
     * @param  string  $protocol  which protocol should be used
     *
     * @return  self
     */
    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;

        return $this;
    }

    /**
     * Get username for authentification
     *
     * @return  string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set username for authentification
     *
     * @param  string  $username  username for authentification
     *
     * @return  self
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return PersistentCollection
     */
    public function getBackupSchedules() : PersistentCollection
    {
        return $this->backupSchedules;
    }

    /**
     * Set undocumented variable
     *
     * @param mixed $backupSchedules
     */
    public function setBackupSchedules($backupSchedules)
    {
        $this->backupSchedules = $backupSchedules;
    }


    /**
     * @param BackupSchedule $backupSchedule
     */
    public function addBackupSchedule(BackupSchedule $backupSchedule)
    {
        if ($this->backupSchedules->contains($backupSchedule)) {
            return;
        }
        $this->backupSchedules->add($backupSchedule);
        $backupSchedule->setDestination($this);
    }

    /**
     * @param BackupSchedule $backupSchedule
     */
    public function removeBackupSchedule(BackupSchedule $backupSchedule)
    {
        if (!$this->backupSchedules->contains($backupSchedule)) {
            return;
        }
        $this->backupSchedules->removeElement($backupSchedule);
        $backupSchedule->setDestination(null);
    }

    /**
     * @param Backup $backup
     */
    public function addBackup(Backup $backup)
    {
        if ($this->backups->contains($backup)) {
            return;
        }
        $this->backups->add($backup);
        $backup->setDestination($this);
    }

    /**
     * @param Backup $backup
     */
    public function removeBackup(Backup $backup)
    {
        if (!$this->backups->contains($backup)) {
            return;
        }
        $this->backups->removeElement($backup);
        $backup->setDestination(null);
    }

    /**
     * Get hostname of backup destination
     *
     * @return  string
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * Set hostname of backup destination
     *
     * @param  string  $hostname  hostname of backup destination
     *
     * @return  self
     */
    public function setHostname($hostname)
    {
        $this->hostname = $hostname;

        return $this;
    }

    /**
     * Get path to backups
     *
     * @return  string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set path to backups
     *
     * @param  string  $path  path to backups
     *
     * @return  self
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }


    /**
     * Get text for duplicity command
     *
     * @return void
     */
    public function getDestinationText()
    {
        if ($this->username) {
            return $this->protocol . '://' . $this->username . '@' . $this->hostname . '/' . $this->path . '/';
        }
        return $this->protocol . '://' . $this->hostname . '/' . $this->path . '/';
    }

    /**
     * Get password for authentification
     *
     * @return  string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set password for authentification
     *
     * @param  string  $password  password for authentification
     *
     * @return  self
     */
    public function setPassword(string $password)
    {
        $this->password = $password;

        return $this;
    }
}