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
     * @Assert\Type(type="array")
     * @OAS\Property(example="{'limits.cpu': '2'}")
     */
    protected $expandedConfig;

    /**
     * @var array
     * @ORM\Column(type="json", nullable=true)
     * @Assert\Type(type="array")
     * @OAS\Property(example="{'root': {'path': '/'}}")
     * @Assert\NotBlank()
     */
    protected $devices;

    /**
     * @var array
     * @ORM\Column(type="json", nullable=true)
     * @Assert\Type(type="array")
     * @OAS\Property(example="{'root': {'path': '/'}}")
     */
    protected $expandedDevices;

    /**
     * @var
     * @ORM\Column(type="json", nullable=true)
     * @Assert\Type(type="array")
     * @OAS\Property(example="{'limits.cpu': '2'}") //TODO Maybe not required with the new Network Entity
     */
    protected $network;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $createdAt;

    /**
     * @var mixed
     * @ORM\Column(type="string", nullable=true)
     *
     */
    protected $error;

    /**
     * @ORM\ManyToOne(targetEntity="Host", inversedBy="containers")
     * @ORM\JoinColumn(name="host_id", referencedColumnName="id")
     * @Assert\NotBlank()
     * @JMS\Exclude()
     */
    protected $host;

    /**
     * @ORM\OneToMany(targetEntity="ContainerStatus", mappedBy="container")
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
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Network", mappedBy="containers")
     * @JMS\Exclude()
     */
    protected $networks;

    public function __construct()
    {
        $this->profiles = new ArrayCollection();
        $this->statuses = new ArrayCollection();
        $this->backupSchedules = new ArrayCollection();
        $this->backups = new ArrayCollection();
        $this->networks = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId() :int
    {
        return $this->id;
    }

    /**
     * @param ContainerStatus $containerStatus
     */
    public function addStatus(ContainerStatus $containerStatus){
        if(!$this->statuses->contains($containerStatus)){
            $containerStatus->setContainer($this);
            $this->statuses->add($containerStatus);
        }
    }

    /**
     * @param ContainerStatus $containerStatus
     */
    public function removeStatus(ContainerStatus $containerStatus){
        if(!$this->statuses->contains($containerStatus)){
            $containerStatus->setContainer(null);
            $this->statuses->remove($containerStatus);
        }
    }

    /**
     * @return string | null
     */
    public function getIpv4() : ?string
    {
        return $this->ipv4;
    }

    /**
     * @return string | null
     */
    public function getIpv6() : ?string
    {
        return $this->ipv6;
    }

    /**
     * @param mixed $ipv4
     */
    public function setIpv4($ipv4)
    {
        $this->ipv4 = $ipv4;
    }

    /**
     * @param mixed $ipv6
     */
    public function setIpv6($ipv6)
    {
        $this->ipv6 = $ipv6;
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
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param mixed $settings
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
     * @return PersistentCollection
     */
    public function getStatuses() :PersistentCollection
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
     * @return string | null
     */
    public function getDomainName() : ?string
    {
        return $this->domainName;
    }

    /**
     * @param mixed $domainName
     */
    public function setDomainName($domainName)
    {
        $this->domainName = $domainName;
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
    public function getArchitecture(): string
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
    public function setConfig($config): void
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
    public function setExpandedConfig($expandedConfig): void
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
    public function setDevices($devices): void
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
    public function setExpandedDevices($expandedDevices): void
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
    public function setNetwork($network): void
    {
        $this->network = $network;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt($createdAt): void
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
    public function setError($error): void
    {
        $this->error = $error;
    }

    /**
     * @return bool
     */
    public function isEphemeral(): bool
    {
        return $this->ephemeral;
    }

    /**
     * @param bool $ephemeral
     */
    public function setEphemeral($ephemeral): void
    {
        $this->ephemeral = $ephemeral;
    }



    /**
     * @return Image
     */
    public function getImage(): Image
    {
        return $this->image;
    }

    /**
     * @param Image $image
     */
    public function setImage(Image $image): void
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
    public function setProfiles($profiles): void
    {
        $this->profiles = $profiles;
    }





    /**
     * @param Profile $profile
     */
    public function addProfile(Profile $profile){
        if ($this->profiles->contains($profile)) {
            return;
        }
        $this->profiles->add($profile);
        $profile->addContainer($this);
    }

    /**
     * @param Profile $profile
     */
    public function removeProfile(Profile $profile){
        if (!$this->profiles->contains($profile)) {
            return;
        }
        $this->profiles->removeElement($profile);
        $profile->removeContainer($this);
    }

    /**
     * @param BackupSchedule $backupSchedule
     */
    public function addBackupSchedule(BackupSchedule $backupSchedule){
        if ($this->backupSchedules->contains($backupSchedule)) {
            return;
        }
        $this->backupSchedules->add($backupSchedule);
        $backupSchedule->addContainer($this);
    }

    /**
     * @param BackupSchedule $backupSchedule
     */
    public function removeBackupSchedule(BackupSchedule $backupSchedule){
        if (!$this->backupSchedules->contains($backupSchedule)) {
            return;
        }
        $this->backupSchedules->removeElement($backupSchedule);
        $backupSchedule->removeContainer($this);
    }

    /**
     * @param Backup $backup
     */
    public function addBackup(Backup $backup){
        if ($this->backups->contains($backup)) {
            return;
        }
        $this->backups->add($backup);
        $backup->addContainer($this);
    }

    /**
     * @param Backup $backup
     */
    public function removeBackup(Backup $backup){
        if (!$this->backups->contains($backup)) {
            return;
        }
        $this->backups->removeElement($backup);
        $backup->removeContainer($this);
    }

    /**
     * @param Network $network
     */
    public function addNetwork(Network $network){
        if ($this->networks->contains($network)) {
            return;
        }
        $this->networks->add($network);
        $this->networks->addContainer($this);
    }

    /**
     * @param Network $network
     */
    public function removeNetwork(Network $network){
        if (!$this->backups->contains($network)) {
            return;
        }
        $this->networks->removeElement($network);
        $network->removeContainer($this);
    }

    /**
     * @return array
     *
     * @OAS\Property(property="profile_id", example="[1]")
     *
     * @JMS\VirtualProperty()
     */
    public function getProfileId(){
        if($this->profiles->isEmpty()){
            return null;
        }

        $this->profiles->first();
        do{
            $ids[] = $this->profiles->current()->getId();
        }while($this->profiles->next());

        return $ids;
    }

    /**
     * @return PersistentCollection
     */
    public function getBackupSchedules(): PersistentCollection
    {
        return $this->backupSchedules;
    }

    /**
     * @return PersistentCollection
     */
    public function getBackups(): PersistentCollection
    {
        return $this->backups;
    }
}