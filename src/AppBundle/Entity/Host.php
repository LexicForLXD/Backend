<?php
/**
 * Created by PhpStorm.
 * User: Leon
 * Date: 06.11.2017
 * Time: 19:10
 */
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Swagger\Annotations as OAS;
use JMS\Serializer\Annotation as JMS;


/**
 * Class Host
 * @package AppBundle\Entity
 * @ORM\Entity
 * @ORM\Table(name="hosts")
 * @UniqueEntity("ipv4")
 * @UniqueEntity("ipv6")
 * @UniqueEntity("domainName")
 * @UniqueEntity("name")
 * @UniqueEntity("mac")
 *
 * @OAS\Schema(schema="host", type="object")
 */
class Host
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @OAS\Property(example="2")
     * var integer
     */
    protected $id;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @Assert\Ip
     *
     * @OAS\Property(example="192.168.178.5")
     * var string
     */
    protected $ipv4;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @Assert\Ip(version = 6)
     *
     * @OAS\Property(example="fe80::5")
     * var string
     */
    protected $ipv6;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @Assert\Regex("/[.]/")
     *
     * @OAS\Property(example="host2.localnet.com")
     * var string
     */
    protected $domainName;

    /**
     * @ORM\Column(type="string", unique=true)
     * @Assert\NotNull()
     *
     * @OAS\Property(example="host2")
     * var string
     */
    protected $name;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     *
     * @OAS\Property(example="82-75-93-4D-B8-6F")
     * var string
     */
    protected $mac;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @OAS\Property(example="22")
     * var integer
     */
    protected $port;

    /**
     * @ORM\Column(type="json", nullable=true)
     *
     * @OAS\Property(example="TODO Settings")
     * var string
     */
    protected $settings;

    /**
     * Undocumented variable
     *
     * @var [type]
     *
     * @ORM\OneToMany(targetEntity="Container", mappedBy="host")
     */
    protected $containers;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Profile", mappedBy="hosts")
     * @JMS\Exclude()
     */
    protected $profiles;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Image", mappedBy="host")
     * @JMS\Exclude()
     */
    protected $images;

    /**
     * @var HostStatus
     * @ORM\OneToOne(targetEntity="HostStatus")
     * @ORM\JoinColumn(name="status_id", referencedColumnName="id")
     * @JMS\Exclude()
     */
    protected $status;


    public function __construct()
    {
        $this->containers = new ArrayCollection();
        $this->profiles = new ArrayCollection();
        $this->images = new ArrayCollection();
    }


    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getIpv4()
    {
        return $this->ipv4;
    }

    /**
     * @return mixed
     */
    public function getIpv6()
    {
        return $this->ipv6;
    }

    /**
     * @return mixed
     */
    public function getDomainName()
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
     * @return mixed
     */
    public function getName()
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
    public function getMac()
    {
        return $this->mac;
    }

    /**
     * @param mixed $mac
     */
    public function setMac($mac)
    {
        $this->mac = $mac;
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


    public function getPort()
    {
        return $this->port;
    }

    public function setPort($port)
    {
        $this->port = $port;
    }

    public function getContainers()
    {
        return $this->containers;
    }

    public function setContainers($containers)
    {
        $this->containers = $containers;
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
    public function setProfiles($profiles)
    {
        $this->profiles = $profiles;
    }

    /**
     * @return mixed
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @param mixed $images
     */
    public function setImages($images)
    {
        $this->images = $images;
    }



    /**
     * Checks if the host has at least on URI
     *
     * @Assert\IsTrue(message = "You have to use at least one of the following: ipv4, ipv6, domainname")
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
        $profile->addHost($this);
    }

    /**
     * @param Profile $profile
     */
    public function removeProfile(Profile $profile){
        if (!$this->profiles->contains($profile)) {
            return;
        }
        $this->profiles->removeElement($profile);
        $profile->removeHost($this);
    }

    /**
     * Adds a container to the Host.
     * @param Container $container
     */
    public function addContainer(Container $container){
        if ($this->containers->contains($container)) {
            return;
        }
        $this->containers->add($container);
        $container->setHost($this);
    }

    /**
     * Removes a container from the Host.
     * @param Container $container
     */
    public function removeContainer(Container $container){
        if (!$this->containers->contains($container)) {
            return;
        }
        $this->containers->removeElement($container);
        $container->removeHost($this);
    }

    /**
    * Adds a Image to the Host.
    * @param Image $image
    */
    public function addImage(Image $image){
        if ($this->images->contains($image)) {
            return;
        }
        $this->images->add($image);
        $image->addHost($this);
    }

    /**
     * Removes a Image from the Host.
     * @param Image $image
     */
    public function removeImage(Image $image){
        if (!$this->images->contains($image)) {
            return;
        }
        $this->images->removeElement($image);
        $image->removeHost($this);
    }

    /**
     * Checks whether the Host has any Containers
     * @return bool
     */
    public function hasContainers() : bool {
        if($this->containers->count() > 0){
            return true;
        }else {
            return false;
        }
    }

    /**
     * Checks whether the Host has any Profiles
     * @return bool
     */
    public function hasProfiles() : bool {
        if($this->profiles->count() > 0){
            return true;
        }else {
            return false;
        }
    }

    /**
     * Checks whether the Host has any Images
     * @return bool
     */
    public function hasImages() : bool {
        if($this->images->count() > 0){
            return true;
        }else {
            return false;
        }
    }

    public function hasAnything() : bool {
        if($this->hasImages() || $this->hasContainers() || $this->hasProfiles()){
            return true;
        } else{
            return false;
        }

    }

    /**
     * Deletes all associations
     * @return bool
     */
    public function deleteAnything() : bool {

        if($this->hasProfiles()) {
            foreach ($this->profiles as $profile) {
                $this->removeProfile($profile);
            }
        }

        if($this->hasContainers()) {
            foreach ($this->containers as $container) {
                $this->removeContainer($container);
            }
        }

        if($this->hasImages()) {
            foreach ($this->images as $image) {
                $this->removeImage($image);
            }
        }

        return true;
    }

    /**
     * @return HostStatus
     */
    public function getStatus(): HostStatus
    {
        return $this->status;
    }

    /**
     * @param HostStatus $status
     */
    public function setStatus(HostStatus $status)
    {
        $this->status = $status;
    }


    /** @see \Serializable::serialize() */
    // public function serialize()
    // {
    //     return serialize(array(
    //         $this->id,
    //         $this->name,
    //         $this->ipv4,
    //         $this->ipv6,
    //         $this->domainName,
    //         $this->mac,
    //         $this->port,
    //         $this->settings
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
    //         $this->domainName,
    //         $this->mac,
    //         $this->port,
    //         $this->settings
    //         ) = unserialize($serialized);
    // }
}