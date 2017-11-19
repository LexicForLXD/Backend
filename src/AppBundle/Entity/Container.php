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


/**
 * Class Container
 * @package AppBundle\Entity
 * @ORM\Entity
 * @ORM\Table(name="containers")
 */
class Container
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $ipv4;

    /**
     * @ORM\Column(type="string")
     */
    private $ipv6;

    /**
     * @ORM\Column(type="text")
     */
    private $name;


    /**
     * @ORM\Column(type="text")
     */
    private $settings;


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

    /** @see \Serializable::serialize() */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->name,
            $this->ipv4,
            $this->ipv6,
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
            $this->settings
            ) = unserialize($serialized);
    }
}