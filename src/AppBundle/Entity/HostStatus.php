<?php

namespace AppBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Swagger\Annotations as OAS;
use JMS\Serializer\Annotation as JMS;


/**
 * Class HostStatus
 * @package AppBundle\Entity
 * @ORM\Table(name="host_status")
 *
 * @ORM\Entity
 * @OAS\Schema(schema="hostStatus", type="object")
 */
class HostStatus
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
     * @Assert\Type("bool")
     *
     * @OAS\Property(example="true")
     */
    protected $nagiosEnabled;

    /**
     * @var String
     * @ORM\Column(type="string")
     * @Assert\NotNull
     * @Assert\NotBlank()
     * @Assert\Type("string")
     *
     * @OAS\Property(example="LXC-Host1")
     */
    protected $nagiosName;

    /**
     * @var String
     * @ORM\Column(type="string")
     * @Assert\NotNull
     * @Assert\NotBlank()
     * @Assert\Type("string")
     *
     * @OAS\Property(example="check_http")
     */
    protected $checkName;

    /**
     * @var int
     * @ORM\Column(type="integer")
     * @Assert\NotNull
     * @Assert\NotBlank()
     * @Assert\Type("int")
     *
     * @OAS\Property(example="1")
     */
    protected $sourceNumber;

    /**
     * @var String
     * @ORM\Column(type="string")
     * @Assert\NotNull
     * @Assert\NotBlank()
     * @Assert\Url()
     *
     * @OAS\Property(example="https://nagios.example.com/pnp4nagios/")
     */
    protected $nagiosUrl;

    /**
     * @ORM\ManyToOne(targetEntity="Host", inversedBy="statuses")
     * @ORM\JoinColumn(name="host_id", referencedColumnName="id")
     *
     * @JMS\Exclude()
     */
    protected $host;

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
     * @param mixed $nagiosEnabled
     */
    public function setNagiosEnabled($nagiosEnabled)
    {
        $this->nagiosEnabled = $nagiosEnabled;
    }

    /**
     * @return String
     */
    public function getNagiosName(): String
    {
        return $this->nagiosName;
    }

    /**
     * @param mixed $nagiosName
     */
    public function setNagiosName($nagiosName)
    {
        $this->nagiosName = $nagiosName;
    }

    /**
     * @return String
     */
    public function getCheckName(): String
    {
        return $this->checkName;
    }

    /**
     * @param mixed $checkName
     */
    public function setCheckName($checkName)
    {
        $this->checkName = $checkName;
    }

    /**
     * @return int
     */
    public function getSourceNumber(): int
    {
        return $this->sourceNumber;
    }

    /**
     * @param mixed $sourceNumber
     */
    public function setSourceNumber( $sourceNumber)
    {
        $this->sourceNumber = $sourceNumber;
    }

    /**
     * @return String
     */
    public function getNagiosUrl(): String
    {
        return $this->nagiosUrl;
    }

    /**
     * @param mixed $nagiosUrl
     */
    public function setNagiosUrl($nagiosUrl)
    {
        $this->nagiosUrl = $nagiosUrl;
    }

    /**
     * @return mixed
     */
    public function getHost() : Host
    {
        return $this->host;
    }

    /**
     * @param mixed $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }
}