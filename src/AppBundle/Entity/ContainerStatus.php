<?php

namespace AppBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


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
     * gibt an, ob für den Container healthCheck aktiviert oder deaktiviert sein soll (true/false)
     *
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    protected $healthCheckEnabled;

    /**
     * gibt an, ob der Container den HealthCheck besteht oder nicht
     *
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    protected $healthCheck;


    /**
     * gibt an, wann der letzte erfolgreiche Ping ausgeführt wurde
     *
     * @var datetime
     *
     * @ORM\Column(type="datetime")
     */
    protected $lastSuccessfullPing;

    /**
     * gibt an, wann der letzte fehlgeschlagene Ping ausgeführt wurde
     *
     * @var datetime
     *
     * @ORM\Column(type="datetime")
     */
    protected $lastFailedPing;

    /**
     * gibt die zuletzt gemessen RoundTripTime an (bei erfolgreichem Ping)
     *
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    protected $lastRtt;

    /**
     * gibt den state des Container laut lxc an
     *
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $state;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return bool
     */
    public function isHealthCheckEnabled(): bool
    {
        return $this->healthCheckEnabled;
    }

    /**
     * @param bool $healthCheckEnabled
     */
    public function setHealthCheckEnabled(bool $healthCheckEnabled)
    {
        $this->healthCheckEnabled = $healthCheckEnabled;
    }

    /**
     * @return bool
     */
    public function isHealthCheck(): bool
    {
        return $this->healthCheck;
    }

    /**
     * @param bool $healthCheck
     */
    public function setHealthCheck(bool $healthCheck)
    {
        $this->healthCheck = $healthCheck;
    }

    /**
     * @return datetime
     */
    public function getLastSuccessfullPing()
    {
        return $this->lastSuccessfullPing;
    }

    /**
     * @param datetime $lastSuccessfullPing
     */
    public function setLastSuccessfullPing($lastSuccessfullPing)
    {
        $this->lastSuccessfullPing = $lastSuccessfullPing;
    }

    /**
     * @return datetime
     */
    public function getLastFailedPing()
    {
        return $this->lastFailedPing;
    }

    /**
     * @param datetime $lastFailedPing
     */
    public function setLastFailedPing($lastFailedPing)
    {
        $this->lastFailedPing = $lastFailedPing;
    }

    /**
     * @return int
     */
    public function getLastRtt(): int
    {
        return $this->lastRtt;
    }

    /**
     * @param int $lastRtt
     */
    public function setLastRtt(int $lastRtt)
    {
        $this->lastRtt = $lastRtt;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState(string $state)
    {
        $this->state = $state;
    }

    



}