<?php

namespace AppBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;


/**
 * Class HostStatus
 * @package AppBundle\Entity
 * @ORM\Table(name="host_status")
 *
 * @ORM\Entity
 */
class HostStatus
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
     */
    protected $nagiosEnabled;

    /**
     * @var String
     * @ORM\Column(type="string")
     * @Assert\NotNull
     * @Assert\NotBlank()
     * @Assert\Type("string")
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
     * @ORM\ManyToOne(targetEntity="Host", inversedBy="statuses")
     * @ORM\JoinColumn(name="host_id", referencedColumnName="id", onDelete="CASCADE")
     *
     * @JMS\Exclude()
     */
    protected $host;

    /**
     * @return int
     */
    public function getId() : int
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
     * @param string $nagiosEnabled
     */
    public function setNagiosEnabled($nagiosEnabled)
    {
        $this->nagiosEnabled = $nagiosEnabled;
    }

    /**
     * @return string
     */
    public function getNagiosName() : string
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
     * @return string
     */
    public function getCheckName() : string
    {
        return $this->checkName;
    }

    /**
     * @param int $checkName
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
     * @return string
     */
    public function getNagiosUrl() : string
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
     * @return mixed
     */
    public function getHost() : Host
    {
        return $this->host;
    }

    /**
     * @param Host $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }
}