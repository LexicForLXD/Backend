<?php

namespace AppBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;


/**
 * Class ContainerStatus
 * @package AppBundle\Entity
 * @ORM\Table(name="container_status")
 *
 * @ORM\Entity
 */
class ContainerStatus
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var boolean
     * @ORM\Column(type="boolean")
     * @Assert\NotNull()
     * @Assert\Type("bool")
     *
     */
    protected $nagiosEnabled;

    /**
     * @var String
     * @ORM\Column(type="string")
     * @Assert\NotNull
     * @Assert\NotBlank()
     * @Assert\Type("string")
     *
     */
    protected $nagiosName;

    /**
     * @var String
     * @ORM\Column(type="string")
     * @Assert\NotNull
     * @Assert\NotBlank()
     * @Assert\Type("string")
     */
    protected $checkName;

    /**
     * @var int
     * @ORM\Column(type="integer")
     * @Assert\NotNull
     * @Assert\NotBlank()
     * @Assert\Type("int")
     */
    protected $sourceNumber;

    /**
     * @var String
     * @ORM\Column(type="string")
     * @Assert\NotNull
     * @Assert\NotBlank()
     * @Assert\Url()
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
    public function isNagiosEnabled() : bool
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
    public function getNagiosName() : String
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
    public function getNagiosUrl() : String
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
    public function getCheckName() : string
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
    public function getSourceNumber() : int
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