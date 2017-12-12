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



}