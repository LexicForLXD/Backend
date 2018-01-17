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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Swagger\Annotations as OAS;
use JMS\Serializer\Annotation as JMS;


/**
 * Class Container
 * @package AppBundle\Entity
 * @ORM\Table(name="containers")
 * @UniqueEntity("ipv4")
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
     * @ORM\Column(type="text")
     *
     * @OAS\Property(example="TODO Settings")
     * var string
     */
    protected $state;

    /**
     * @ORM\ManyToOne(targetEntity="Host", inversedBy="containers")
     * @ORM\JoinColumn(name="host_id", referencedColumnName="id")
     *
     * @OAS\Property(ref="#/components/schemas/host")
     */
    protected $host;

    /**
     * gibt den Status zu einem Container an
     *
     * @var
     *
     * @ORM\OneToOne(targetEntity="ContainerStatus")
     * @ORM\JoinColumn(name="status_id", referencedColumnName="id")
     */
    protected $status;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Profile", mappedBy="containers")
     * @JMS\Exclude()
     */
    protected $profiles;

    public function __construct()
    {
        $this->profiles = new ArrayCollection();
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

    public function getHost()
    {
        return $this->host;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setHost($host)
    {
        $this->host = $host;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getDomainName()
    {
        return $this->domainName;
    }

    public function setDomainName($domainName)
    {
        $this->domainName = $domainName;
    }

    public function getState()
    {
        return $this->state;
    }

    public function setState($state)
    {
        $this->state = $state;
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