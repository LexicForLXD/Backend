<?php

/**
 * Created by PhpStorm.
 * User: Leon
 * Date: 07.11.2017
 * Time: 15:14
 */

namespace AppBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Swagger\Annotations as OAS;
use JMS\Serializer\Annotation as JMS;


/**
 * Class Container
 * @package AppBundle\Entity
 * @ORM\Table(name="containers")
 * @UniqueEntity("name")
 * @ORM\Entity
 *
 * @OAS\Schema(schema="container", type="object")
 */
class Container
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @OAS\Property(example="14")
     * @var integer
     */
    protected $id;

    /**
     * @ORM\Column(type="text")
     * @Assert\Type(type="string")
     * @Assert\NotBlank()
     * @OAS\Property(example="WebServer1")
     * @var string
     */
    protected $name;


    /**
     * @ORM\Column(type="json", nullable=true)
     * @deprecated 2.0
     */
    protected $settings;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\Type(type="bool")
     * @Assert\NotNull()
     * @OAS\Property(example="false")
     * @var bool
     */
    protected $ephemeral;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Assert\Type(type="string")
     * @OAS\Property(example="TODO Settings")
     * @var string
     */
    protected $state;


    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     * @Assert\Type(type="string")
     * @OAS\Property(example="x86_64")
     */
    protected $architecture;

    /**
     * @var array
     * @ORM\Column(type="json", nullable=true)
     * @OAS\Property(example="{'limits.cpu': '2'}")
     */
    protected $config;

    /**
     * @var array
     * @ORM\Column(type="json", nullable=true)
     * @OAS\Property(example="{'limits.cpu': '2'}")
     */
    protected $expandedConfig;

    /**
     * @var array
     * @ORM\Column(type="json", nullable=true)
     * @OAS\Property(example="{'root': {'path': '/'}}")
     */
    protected $devices;

    /**
     * @var array
     * @ORM\Column(type="json", nullable=true)
     * @OAS\Property(example="{'root': {'path': '/'}}")
     */
    protected $expandedDevices;

    /**
     * @var
     * @ORM\Column(type="json", nullable=true)
     * @OAS\Property(example="{'limits.cpu': '2'}")
     */
    protected $network;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $createdAt;

    /**
     * @var mixed
     * @ORM\Column(type="text", nullable=true)
     *
     */
    protected $error;

    /**
     * @var mixed
     * @ORM\Column(type="array", nullable=true)
     */
    protected $source;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Host", inversedBy="containers")
     * @ORM\JoinColumn(name="host_id", referencedColumnName="id")
     * @Assert\NotBlank()
     * @JMS\Exclude()
     */
    protected $host;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ContainerStatus", mappedBy="container")
     * @JMS\Exclude()
     */
    protected $statuses;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Profile", mappedBy="containers")
     * @JMS\Exclude()
     */
    protected $profiles;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\BackupSchedule", mappedBy="containers")
     * @JMS\Exclude()
     */
    protected $backupSchedules;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Backup", mappedBy="containers")
     * @JMS\Exclude()
     */
    protected $backups;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Image", inversedBy="containers")
     * @var Image
     */
    protected $image;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\StoragePool", inversedBy="containers")
     * @var StoragePool
     * @Assert\NotBlank()
     */
    protected $storagePool;


    public function __construct()
    {
        $this->profiles = new ArrayCollection();
        $this->statuses = new ArrayCollection();
        $this->backupSchedules = new ArrayCollection();
        $this->backups = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @param ContainerStatus $containerStatus
     */
    public function addStatus(ContainerStatus $containerStatus)
    {
        if (!$this->statuses->contains($containerStatus)) {
            $containerStatus->setContainer($this);
            $this->statuses->add($containerStatus);
        }
    }

    /**
     * @param ContainerStatus $containerStatus
     */
    public function removeStatus(ContainerStatus $containerStatus)
    {
        if (!$this->statuses->contains($containerStatus)) {
            $containerStatus->setContainer(null);
            $this->statuses->remove($containerStatus);
        }
    }


    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }


    /**
     * @return mixed
     * @deprecated 2.0
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param mixed $settings
     * @deprecated 2.0
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;
    }

    /**
     * @return Host | null
     */
    public function getHost() : Host
    {
        return $this->host;
    }


    /**
     * @return int | null
     * @OAS\Property(property="hostId", example="1")
     * @JMS\VirtualProperty()
     */
    public function getHostId()
    {
        return $this->host->getId();
    }

    /**
     * @return ArrayCollection|PersistentCollection
     */
    public function getStatuses()
    {
        return $this->statuses;
    }

    /**
     * @param Host $host
     */
    public function setHost(Host $host)
    {
        $this->host = $host;
    }


    /**
     * @return string
     */
    public function getState() : string
    {
        return $this->state;
    }

    /**
     * @param mixed $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return string
     */
    public function getArchitecture() : string
    {
        return $this->architecture;
    }

    /**
     * @param string $architecture
     */
    public function setArchitecture($architecture)
    {
        $this->architecture = $architecture;
    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param mixed $config
     */
    public function setConfig($config) : void
    {
        $this->config = $config;
    }

    /**
     * @return mixed
     */
    public function getExpandedConfig()
    {
        return $this->expandedConfig;
    }

    /**
     * @param mixed $expandedConfig
     */
    public function setExpandedConfig($expandedConfig) : void
    {
        $this->expandedConfig = $expandedConfig;
    }

    /**
     * @return mixed
     */
    public function getDevices()
    {
        return $this->devices;
    }

    /**
     * @param mixed $devices
     */
    public function setDevices($devices) : void
    {
        $this->devices = $devices;
    }

    /**
     * @return mixed
     */
    public function getExpandedDevices()
    {
        return $this->expandedDevices;
    }

    /**
     * @param mixed $expandedDevices
     */
    public function setExpandedDevices($expandedDevices) : void
    {
        $this->expandedDevices = $expandedDevices;
    }

    /**
     * @return mixed
     */
    public function getNetwork()
    {
        return $this->network;
    }

    /**
     * @param mixed $network
     */
    public function setNetwork($network) : void
    {
        $this->network = $network;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt() : \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt($createdAt) : void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param mixed $error
     */
    public function setError($error) : void
    {
        $this->error = $error;
    }

    /**
     * @return bool
     */
    public function isEphemeral() : bool
    {
        return $this->ephemeral;
    }

    /**
     * @param bool $ephemeral
     */
    public function setEphemeral($ephemeral) : void
    {
        $this->ephemeral = $ephemeral;
    }



    /**
     * @return Image
     */
    public function getImage() : Image
    {
        return $this->image;
    }

    /**
     * @param Image $image
     */
    public function setImage(Image $image) : void
    {
        $this->image = $image;
    }

    /**
     * @return mixed
     */
    public function getProfiles()
    {
        return $this->profiles;
    }

    /**
     * @param mixed $profiles
     */
    public function setProfiles($profiles) : void
    {
        $this->profiles = $profiles;
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param mixed $source
     */
    public function setSource($source) : void
    {
        $this->source = $source;
    }


    public function getBody() : array
    {

        $bodyDevices = $this->devices;
        if ($this->storagePool) {
            $bodyDevices["root"] = [
                "path" => "/",
                "type" => "disk",
                "pool" => $this->storagePool->getName()
            ];
        }

        $body = [
            "name" => $this->getName(),
            "architecture" => $this->getArchitecture(),
            "profiles" => $this->getProfileNames(),
            "ephemeral" => $this->isEphemeral(),
            "config" => $this->getConfig(),
            "devices" => $bodyDevices,
            "source" => $this->getSource()
        ];

        return $body;
    }




    /**
     * @param Profile $profile
     */
    public function addProfile(Profile $profile)
    {
        if ($this->profiles->contains($profile)) {
            return;
        }
        $this->profiles->add($profile);
        $profile->addContainer($this);
    }

    /**
     * @param Profile $profile
     */
    public function removeProfile(Profile $profile)
    {
        if (!$this->profiles->contains($profile)) {
            return;
        }
        $this->profiles->removeElement($profile);
        $profile->removeContainer($this);
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
        $backupSchedule->addContainer($this);
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
        $backupSchedule->removeContainer($this);
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
        $backup->addContainer($this);
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
        $backup->removeContainer($this);
    }



    /**
     * @return array
     *
     * @OAS\Property(property="profile_id", example="[1]")
     *
     * @JMS\VirtualProperty()
     */
    public function getProfileId()
    {
        return $this->profiles->map(function ($o) {
            return $o->getId();
        })->toArray();
    }

    /**
     * Returns an array of all profilenames associated with this container
     *
     * @return array
     */
    public function getProfileNames()
    {
        return $this->profiles->map(function ($o) {
            return $o->getName();
        })->toArray();
    }

    /**
     * @return ArrayCollection|PersistentCollection
     */
    public function getBackupSchedules()
    {
        return $this->backupSchedules;
    }

    /**
     * @return ArrayCollection|PersistentCollection
     */
    public function getBackups()
    {
        return $this->backups;
    }

    /**
     * @return StoragePool
     */
    public function getStoragePool() : StoragePool
    {
        return $this->storagePool;
    }

    /**
     * @param StoragePool $storagePool
     */
    public function setStoragePool(StoragePool $storagePool) : void
    {
        $this->storagePool = $storagePool;
    }



}