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
use Doctrine\ORM\PersistentCollection;
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
     * @var integer
     */
    protected $id;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @Assert\Ip
     *
     * @Assert\Type(type="string")
     * @OAS\Property(example="192.168.178.5")
     * @var string
     */
    protected $ipv4;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @Assert\Ip(version = 6)
     *
     * @Assert\Type(type="string")
     * @OAS\Property(example="fe80::5")
     * @var string
     */
    protected $ipv6;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @Assert\Regex("/[.]/")
     *
     * @Assert\Type(type="string")
     * @OAS\Property(example="host2.localnet.com")
     * @var string
     */
    protected $domainName;

    /**
     * @ORM\Column(type="string", unique=true)
     * @Assert\NotNull()
     *
     * @Assert\Type(type="string")
     * @OAS\Property(example="host2")
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     *
     * @Assert\Type(type="string")
     * @OAS\Property(example="82-75-93-4D-B8-6F")
     * @var string
     */
    protected $mac;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Assert\Type(type="int")
     * @OAS\Property(example="22")
     * @var integer
     */
    protected $port;

    /**
     * @ORM\Column(type="json", nullable=true)
     *
     * @OAS\Property(example="TODO Settings")
     * @var string
     */
    protected $settings;

    /**
     * @ORM\Column(type="boolean", options={"default":false}, nullable=true)
     *
     * @Assert\Type(type="bool")
     * @OAS\Property(example="true")
     * @var boolean
     */
    protected $authenticated;

    /**
     * Undocumented variable
     *
     * @var [type]
     *
     * @ORM\OneToMany(targetEntity="Container", mappedBy="host")
     * @JMS\Exclude()
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
     * @ORM\OneToMany(targetEntity="HostStatus", mappedBy="host")
     * @JMS\Exclude()
     */
    protected $statuses;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Network", mappedBy="host")
     * @JMS\Exclude()
     */
    protected $lxdNetworks;


    public function __construct()
    {
        $this->containers = new ArrayCollection();
        $this->profiles = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->statuses = new ArrayCollection();
        $this->lxdNetworks = new ArrayCollection();
    }


    /**
     * @return int
     */
    public function getId() : ?int
    {
        return $this->id;
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
    public function getName() : ?string
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName( $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getMac() : ?string
    {
        return $this->mac;
    }

    /**
     * @param mixed $mac
     */
    public function setMac( $mac)
    {
        $this->mac = $mac;
    }

    /**
     * @return string
     */
    public function getSettings() : ?string
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
     * @return int
     */
    public function getPort() : ?int
    {
        return $this->port;
    }

    /**
     * @param mixed $port
     */
    public function setPort( $port)
    {
        $this->port = $port;
    }

    /**
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return $this->authenticated;
    }

    /**
     * @param mixed $authenticated
     */
    public function setAuthenticated($authenticated): void
    {
        $this->authenticated = $authenticated;
    }


    /**
     * @return PersistentCollection
     */
    public function getContainers() : PersistentCollection
    {
        return $this->containers;
    }

    /**
     * @param mixed $containers
     */
    public function setContainers( $containers)
    {
        $this->containers = $containers;
    }

    /**
     * @return PersistentCollection
     */
    public function getProfiles() : PersistentCollection
    {
        return $this->profiles;
    }

    /**
     * @param mixed $profiles
     */
    public function setProfiles( $profiles)
    {
        $this->profiles = $profiles;
    }

    /**
     * @return PersistentCollection
     */
    public function getImages() : PersistentCollection
    {
        return $this->images;
    }

    /**
     * @param mixed $images
     */
    public function setImages( $images)
    {
        $this->images = $images;
    }

    /**
     * @param HostStatus $hostStatus
     */
    public function addStatus(HostStatus $hostStatus){
        if(!$this->statuses->contains($hostStatus)){
            $hostStatus->setHost($this);
            $this->statuses->add($hostStatus);
        }
    }

    /**
     * @param HostStatus $hostStatus
     */
    public function removeStatus(HostStatus $hostStatus){
        if(!$this->statuses->contains($hostStatus)){
            $hostStatus->setHost(null);
            $this->statuses->remove($hostStatus);
        }
    }

    /**
     * Checks if the host has at least on URI
     *
     * @Assert\IsTrue(message = "You have to use at least one of the following: ipv4, ipv6, domainname")
     *
     * @return boolean
     */
    public function hasUri() : bool
    {
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
    * @param Network $network
    */
    public function addLXDNetwork(Network $network){
        if ($this->lxdNetworks->contains($network)) {
            return;
        }
        $this->lxdNetworks->add($network);
        $network->setHost($this);
    }

    /**
     * @param Network $network
     */
    public function removeLXDNetwork(Network $network){
        if (!$this->lxdNetworks->contains($network)) {
            return;
        }
        $this->lxdNetworks->removeElement($network);
        $network->setHost(null);
    }

    /**
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

    /**
     * Checks whether the Host has any images or profiles or containers
     * @return bool
     */
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
     * Returns the url for a host.
     * @return string
     */
    public function getUri() : string
    {
        $hostname = $this->getIpv4() ?: $this->getIpv6() ?: $this->getDomainName() ?: 'localhost';

        $port = $this->getPort() ?: '8443';
        $apiVersion = '1.0';
        $url = 'https://'.$hostname.':'.$port.'/'.$apiVersion.'/';

        return $url;
    }

    /**
     * @return PersistentCollection
     */
    public function getStatuses() : PersistentCollection
    {
        return $this->statuses;
    }

    /**
     * @param mixed $statuses
     */
    public function setStatuses( $statuses)
    {
        $this->statuses = $statuses;
    }



    /**
     * @return array
     *
     * @OAS\Property(property="container_id", example="[1]")
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
}