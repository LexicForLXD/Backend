<?php

namespace AppBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Swagger\Annotations as OAS;


/**
 * Class ContainerStatus
 * @package AppBundle\Entity
 * @ORM\Table(name="container_status")
 *
 * @ORM\Entity
 * @OAS\Schema(schema="containerStatus", type="object")
 */
class ContainerStatus
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @OAS\Property(example="4")
     */
    protected $id;

    /**
     * @var boolean
     * @ORM\Column(type="boolean")
     * @Assert\NotNull()
     *
     * @OAS\Property(example="true")
     */
    protected $nagiosEnabled;

    /**
     * @var String | null
     * @ORM\Column(type="string")
     * @Assert\NotNull
     * @Assert\NotBlank()
     *
     * @OAS\Property(example="ContainerWebServer1")
     */
    protected $nagiosName;

    /**
     * @var String | null
     * @ORM\Column(type="string")
     * @Assert\NotNull
     * @Assert\NotBlank()
     *
     * @OAS\Property(example="https://nagios.example.com/pnp4nagios/")
     */
    protected $nagiosUrl;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isNagiosEnabled(): bool
    {
        return $this->nagiosEnabled;
    }

    /**
     * @param bool $nagiosEnabled
     */
    public function setNagiosEnabled(bool $nagiosEnabled)
    {
        $this->nagiosEnabled = $nagiosEnabled;
    }

    /**
     * @return null|String
     */
    public function getNagiosName(): String
    {
        return $this->nagiosName;
    }

    /**
     * @param null|String $nagiosName
     */
    public function setNagiosName(String $nagiosName)
    {
        $this->nagiosName = $nagiosName;
    }

    /**
     * @return null|String
     */
    public function getNagiosUrl(): String
    {
        return $this->nagiosUrl;
    }

    /**
     * @param null|String $nagiosUrl
     */
    public function setNagiosUrl(String $nagiosUrl)
    {
        $this->nagiosUrl = $nagiosUrl;
    }


}