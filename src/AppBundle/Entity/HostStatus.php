<?php

namespace AppBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Swagger\Annotations as OAS;


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
     * gibt an, ob für den Container healthCheck aktiviert oder deaktiviert sein soll (true/false)
     *
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     * @OAS\Property(example="true")
     */
    protected $healthCheckEnabled;

    /**
     * gibt an, ob der Container den HealthCheck besteht oder nicht
     *
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @OAS\Property(example="true")
     */
    protected $healthCheck;


    /**
     * gibt an, wann der letzte erfolgreiche Ping ausgeführt wurde
     *
     * @var datetime
     * @Assert\DateTime(format="Y-m-d\TH:i:sP")
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @OAS\Property(example="2017-08-15T15:52:01+00:00")
     */
    protected $lastSuccessfullPing;

    /**
     * gibt an, wann der letzte fehlgeschlagene Ping ausgeführt wurde
     *
     * @var datetime
     * @Assert\DateTime(format="Y-m-d\TH:i:sP")
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @OAS\Property(example="2017-08-14T11:50:01+00:00")
     */
    protected $lastFailedPing;

    /**
     * gibt die zuletzt gemessen RoundTripTime an (bei erfolgreichem Ping)
     *
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @OAS\Property(example="18")
     */
    protected $lastRtt;

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

    



}