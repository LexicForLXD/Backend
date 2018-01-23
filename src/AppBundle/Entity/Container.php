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
 *
 * @UniqueEntity("ipv6")
 * @UniqueEntity("domainName")
 * @UniqueEntity("name")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ContainerRepository")
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
     * var integer
     */
    protected $id;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @Assert\Ip
     * @OAS\Property(example="192.168.178.20")
     * var string
     */
    protected $ipv4;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @Assert\Ip(version = 6)
     *
     * @OAS\Property(example="fe80::20")
     * var string
     */
    protected $ipv6;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @Assert\Regex("/[.]/")
     * @OAS\Property(example="container14.localnet.com")
     * var string
     */
    protected $domainName;

    /**
     * @ORM\Column(type="text")
     *
     * @OAS\Property(example="WebServer1")
     * var string
     */
    protected $name;


    /**
     * @ORM\Column(type="json", nullable=true)
     */
    protected $settings;

    /**
     * @ORM\Column(type="json")
     * @OAS\Property(example="TODO Settings")
     */
    protected $state;

    /**
     * @ORM\Column(type="json")
     * @Assert\NotBlank()
     * @OAS\Property(example="{'limits.cpu': '2'}")
     * @var
     */
    protected $config;

    /**
     * @ORM\Column(type="json")
     * @Assert\NotBlank()
     * @OAS\Property(example="'kvm': {'path': '/dev/kvm','type': 'unix-char'}")
     * @var
     */
    protected $devices;
    /**
     * @ORM\Column(type="boolean")
     * @Assert\Type("bool")
     * @OAS\Property(example="true")
     * @var bool
     */
    protected $ephemeral;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     * @OAS\Property(example="x86_64")
     * @var string
     */
    protected $architecture;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\DateTime()
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\DateTime()
     * @var \DateTime
     */
    protected $updatedAt;

    /**
     * @ORM\Column(type="json", nullable=true)
     * @OAS\Property(example="aehnlich zu config allerdings mit config von profiles")
     * @var
     */
    protected $expandedConfig;

    /**
     * @ORM\Column(type="json", nullable=true)
     * @OAS\Property(example="aehnlich zu devices allerdings mit devices von profiles")
     * @var
     */
    protected $expandedDevices;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @OAS\Property(example="Beispiel Fehlermeldung")
     * @var string
     */
    protected $error;

    /**
     * @ORM\ManyToOne(targetEntity="Host", inversedBy="containers")
     * @ORM\JoinColumn(name="host_id", referencedColumnName="id")
     *
     * @OAS\Property(ref="#/components/schemas/host")
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
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Image", inversedBy="containers")
     * @var Image
     */
    protected $image;

    public function __construct()
    {
        $this->profiles = new ArrayCollection();
        $this->statuses = new ArrayCollection();
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
     * @param string $ipv4
     */
    public function setIpv4(string $ipv4)
    {
        $this->ipv4 = $ipv4;
    }

    /**
     * @param string $ipv6
     */
    public function setIpv6(string $ipv6)
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
     * @param string $name
     */
    public function setName(string $name)
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
     * @return ArrayCollection
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
     * @param string $domainName
     */
    public function setDomainName(string $domainName)
    {
        $this->domainName = $domainName;
    }

    /**
     * @return
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState($state)
    {
        $this->state = $state;
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
     * @return bool
     */
    public function isEphemeral(): bool
    {
        return $this->ephemeral;
    }

    /**
     * @param bool $ephemeral
     */
    public function setEphemeral(bool $ephemeral): void
    {
        $this->ephemeral = $ephemeral;
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
    public function setArchitecture(string $architecture): void
    {
        $this->architecture = $architecture;
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
    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt(\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
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
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * @param string $error
     */
    public function setError(string $error): void
    {
        $this->error = $error;
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
     * Checks if the container has at least on URI
     *
     *
     *
     * @return boolean
     */
    public function hasUri(){
        if($this->ipv4 || $this->ipv6 || $this->domainName)
        {
            return true;
        } else {
            return false;
        }
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

    /** @see \Serializable::serialize() */
    // public function serialize()
    // {
    //     return serialize(array(
    //         $this->id,
    //         $this->name,
    //         $this->ipv4,
    //         $this->ipv6,
    //         $this->settings,
    //         $this->host
    //     ));
    // }

    // /** @see \Serializable::unserialize() */
    // public function unserialize($serialized)
    // {
    //     list (
    //         $this->id,
    //         $this->name,
    //         $this->ipv4,
    //         $this->ipv6,
    //         $this->settings,
    //         $this->host
    //         ) = unserialize($serialized);
    // }
}