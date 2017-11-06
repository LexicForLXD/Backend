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


/**
 * Class Host
 * @package AppBundle\Entity
 * @ORM\Entity
 * @ORM\Table(name="hosts")
 */
class Host
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="inet")
     */
    private $ip;

    /**
     * @ORM\Column(type="text")
     */
    private $name;

    /**
     * @ORM\Column(type="macaddr")
     */
    private $mac;

    /**
     * @ORM\Column(type="text")
     */
    private $settings;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Container",
     *     mappedBy="host",
     *     orphanRemoval=true
     * )
     */
    private $containers;

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
    public function getId()
    {
        return $this->id;
    }


    /**
     * @return mixed
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param mixed $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
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


}