<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Swagger\Annotations as OAS;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Profile
 * @package AppBundle\Entity
 * @ORM\Entity
 * @ORM\Table(name="lxcprofiles")
 *
 * @OAS\Schema(schema="profile", type="object")
 */
class Profile
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
     * @ORM\Column(type="string", unique=true, nullable=false)
     *
     * @OAS\Property(example="my-profilename")
     * var string
     */
    protected $name;

    /**
     * @ORM\Column(type="string")
     *
     * @OAS\Property(example="Some description string")
     * var string
     */
    protected $description;

    /**
     * @ORM\Column(type="json_array")
     *
     * @OAS\Property(example="TODO find out how to input JSON")
     * var json_array
     */
    protected $config;

    /**
     * @ORM\Column(type="json_array")
     *
     * @OAS\Property(example="TODO find out how to input JSON")
     * var json_array
     */
    protected $devices;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Host", inversedBy="profiles")
     * @ORM\JoinTable(
     *  joinColumns={
     *      @ORM\JoinColumn(name="host_id", referencedColumnName="id")
     *  },
     *  inverseJoinColumns={
     *      @ORM\JoinColumn(name="profile_id", referencedColumnName="id")
     *  }
     * )
     * @JMS\Exclude()
     */
    protected $hosts;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Container", inversedBy="profiles")
     * @ORM\JoinTable(
     *  joinColumns={
     *      @ORM\JoinColumn(name="container_id", referencedColumnName="id")
     *  },
     *  inverseJoinColumns={
     *      @ORM\JoinColumn(name="profile_id", referencedColumnName="id")
     *  }
     * )
     * @JMS\Exclude()
     */
    protected $containers;

    /**
     * Profile constructor.-
     */
    public function __construct()
    {
        $this->hosts = new ArrayCollection();
        $this->containers = new ArrayCollection();
    }

    /**
     * @param Host $host
     */
    public function addHost(Host $host)
    {
        if ($this->hosts->contains($host)) {
            return;
        }
        $this->hosts->add($host);
        $host->addProfile($this);
    }

    /**
     * @param Host $host
     */
    public function removeHost(Host $host){
        if (!$this->hosts->contains($host)) {
            return;
        }
        $this->hosts->removeElement($host);
        $host->removeProfile($this);
    }

    /**
     * @param Container $container
     */
    public function addContainer(Container $container)
    {
        if ($this->containers->contains($container)) {
            return;
        }
        $this->containers->add($container);
        $container->addProfile($this);
    }

    /**
     * @param Container $container
     */
    public function removeContainer(Container $container){
        if (!$this->containers->contains($container)) {
            return;
        }
        $this->containers->removeElement($container);
        $container->removeProfile($this);
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
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
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
    public function setConfig($config)
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
    public function setDevices($devices)
    {
        $this->devices = $devices;
    }

    /**
     * @return mixed
     */
    public function getHosts()
    {
        return $this->hosts;
    }

    /**
     * @return mixed
     */
    public function getContainers()
    {
        return $this->containers;
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
     * @return array
     *
     * @JMS\VirtualProperty()
     */
    public function getHostId(){
        $ids[] = null;

        while($this->hosts->next()){
            $ids[] = $this->hosts->current()->getId();
        }

        return $ids;
    }

    /**
     * @return array
     *
     * @JMS\VirtualProperty()
     */
    public function getContainerId(){
        $ids[] = null;

        while($this->containers->next()){
            $ids[] = $this->containers->current()->getId();
        }

        return $ids;
    }

}