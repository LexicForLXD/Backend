<?php

namespace AppBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
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
     * @OAS\Property(example="ContainerWebServer1")
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
     * @ORM\ManyToOne(targetEntity="Container", inversedBy="statuses")
     * @ORM\JoinColumn(name="container_id", referencedColumnName="id", onDelete="CASCADE")
     *
     * @JMS\Exclude()
     */
    protected $container;

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
    public function setNagiosEnabled($nagiosEnabled)
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
     * @param string $nagiosName
     */
    public function setNagiosName($nagiosName)
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
     * @param string $nagiosUrl
     */
    public function setNagiosUrl($nagiosUrl)
    {
        $this->nagiosUrl = $nagiosUrl;
    }

    /**
     * @return String
     */
    public function getCheckName(): string
    {
        return $this->checkName;
    }

    /**
     * @param string $checkName
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
     * @param int $sourceNumber
     */
    public function setSourceNumber($sourceNumber)
    {
        $this->sourceNumber = $sourceNumber;
    }

    /**
     * @return Container
     */
    public function getContainer() : Container
    {
        return $this->container;
    }

    /**
     * @param Container $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }
}