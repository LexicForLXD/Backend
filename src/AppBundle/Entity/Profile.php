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
     * @ORM\Column(type="string")
     *
     * @OAS\Property(example="TODO example description")
     * var string
     */
    protected $description;

    /**
     * @ORM\Column(type="json_array")
     *
     * @OAS\Property(example="TODO example config")
     * var json_array
     */
    protected $config;

    /**
     * @ORM\Column(type="json_array")
     *
     * @OAS\Property(example="TODO example devices")
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
}