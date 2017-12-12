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
    private $id;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @Assert\Ip
     *
     * @OAS\Property(example="192.168.178.5")
     * var string
     */
    private $ipv4;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @Assert\Ip(version = 6)
     *
     * @OAS\Property(example="fe80::5")
     * var string
     */
    private $ipv6;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @Assert\Regex("/[.]/")
     *
     * @OAS\Property(example="host2.localnet.com")
     * var string
     */
    private $domainName;

    /**
     * @ORM\Column(type="string", unique=true)
     * @Assert\NotNull()
     *
     * @OAS\Property(example="host2")
     * var string
     */
    private $name;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     *
     * @OAS\Property(example="82-75-93-4D-B8-6F")
     * var string
     */
    private $mac;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @OAS\Property(example="22")
     * var integer
     */
    private $port;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @OAS\Property(example="TODO Settings")
     * var string
     */
    private $settings;

    /**
     * Undocumented variable
     *
     * @var [type]
     *
     * @ORM\OneToMany(targetEntity="Container", mappedBy="host")
     */
    private $containers;


    public function __construct()
    {
        $this->containers = new ArrayCollection();
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

    /** @see \Serializable::serialize() */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->name,
            $this->ipv4,
            $this->ipv6,
            $this->domainName,
            $this->mac,
            $this->port,
            $this->settings
        ));
    }

    /** @see \Serializable::unserialize() */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->name,
            $this->ipv4,
            $this->ipv6,
            $this->domainName,
            $this->mac,
            $this->port,
            $this->settings
            ) = unserialize($serialized);
    }
}