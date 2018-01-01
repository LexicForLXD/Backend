<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Swagger\Annotations as OAS;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Class Profile
 * @package AppBundle\Entity
 * @ORM\Entity
 * @ORM\Table(name="lxcprofiles")
 *
 * @UniqueEntity("name")
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
     * @Assert\NotNull
     * @Assert\NotBlank()
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
     * @OAS\Property(example="Config JSON array")
     * var json_array
     */
    protected $config;

    /**
     * @ORM\Column(type="json_array")
     *
     * @OAS\Property(example="Devices JSON array")
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
     *
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
     *
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
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDescription() : string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * @return array
     */
    public function getConfig() : array
    {
        return $this->config;
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getDevices() : array
    {
        return $this->devices;
    }

    /**
     * @param array $devices
     */
    public function setDevices(array $devices)
    {
        $this->devices = $devices;
    }

    /**
     * @return ArrayCollection
     */
    public function getHosts() : ArrayCollection
    {
        return $this->hosts;
    }

    /**
     * @return ArrayCollection
     */
    public function getContainers() : ArrayCollection
    {
        return $this->containers;
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
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return array
     *
     * @OAS\Property(property="host_id", example="[1]")
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
     * @OAS\Property(property="container_id", example="[1]")
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

    /**
     * Internally used to check if a profile is used by one or more containers
     *
     * @return bool
     */
    public function isUsedByContainer() : bool {
        if($this->containers->count() > 0){
            return true;
        }

        return false;
    }

    /**
     * Returns the number of Containers from the param var using this LXC-Profile
     * @param ArrayCollection $containers
     * @return int
     */
    public function numberOfContainersMatchingProfile(ArrayCollection $containers) : int {
        $total = 0;
        while($containers->next()){
            $container = $containers->current();
            if($this->containers->contains($container)){
                $total++;
            }
        }
        return $total;
    }

    /**
     * Internally used to check if a profile is present on one or more hosts
     * @return bool
     */
    public function linkedToHost() : bool {
        if($this->hosts->count() > 0){
            return true;
        }

        return false;
    }

    /**
     * Returns true or false to show if the Host is already using this LXC-Profile
     * @param Host $host
     * @return bool
     */
    public function isHostLinked(Host $host) : bool {
        return $this->hosts->contains($host);
    }
}