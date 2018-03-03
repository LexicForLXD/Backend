<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * Class BackupSchedule
 * @package AppBundle\Entity
 *
 * @ORM\Entity
 */
class BackupSchedule
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true, nullable=false)
     */
    protected $name;

    /**
     * @var string | null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $description;

    /**
     * @var int | null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $minute;

    /**
     * @var int | null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $hour;

    /**
     * @var int | null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $day;

    /**
     * @var int | null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $month;

    /**
     * @var int | null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $weekday;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $destination;

    /**
     * @var Container
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Container", inversedBy="backup_schedule")
     * @ORM\JoinTable(
     *  joinColumns={
     *      @ORM\JoinColumn(name="backup_schedule_id", referencedColumnName="id")
     *  },
     *  inverseJoinColumns={
     *      @ORM\JoinColumn(name="container_id", referencedColumnName="id")
     *  }
     * )
     *
     * @JMS\Exclude()
     */
    protected $containers;

    /**
     * BackupSchedule constructor.
     */
    public function __construct()
    {
        $this->containers = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return null|string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param null|string $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return int|null
     */
    public function getMinute(): ?int
    {
        return $this->minute;
    }

    /**
     * @param int|null $minute
     */
    public function setMinute(?int $minute): void
    {
        $this->minute = $minute;
    }

    /**
     * @return int|null
     */
    public function getHour(): ?int
    {
        return $this->hour;
    }

    /**
     * @param int|null $hour
     */
    public function setHour(?int $hour): void
    {
        $this->hour = $hour;
    }

    /**
     * @return int|null
     */
    public function getDay(): ?int
    {
        return $this->day;
    }

    /**
     * @param int|null $day
     */
    public function setDay(?int $day): void
    {
        $this->day = $day;
    }

    /**
     * @return int|null
     */
    public function getMonth(): ?int
    {
        return $this->month;
    }

    /**
     * @param int|null $month
     */
    public function setMonth(?int $month): void
    {
        $this->month = $month;
    }

    /**
     * @return int|null
     */
    public function getWeekday(): ?int
    {
        return $this->weekday;
    }

    /**
     * @param int|null $weekday
     */
    public function setWeekday(?int $weekday): void
    {
        $this->weekday = $weekday;
    }

    /**
     * @return string
     */
    public function getDestination(): string
    {
        return $this->destination;
    }

    /**
     * @param string $destination
     */
    public function setDestination(string $destination): void
    {
        $this->destination = $destination;
    }

    /**
     * @return Container
     */
    public function getContainers(): Container
    {
        return $this->containers;
    }

    /**
     * @param Container $container
     */
    public function addContainer(Container $container)
    {
        if ($this->containers->contains($container)) {
            return;
        }
        $this->containers->add($container);
        $container->addBackupSchedule($this);
    }

    /**
     * @param Container $container
     */
    public function removeContainer(Container $container){
        if (!$this->containers->contains($container)) {
            return;
        }
        $this->containers->removeElement($container);
        $container->removeBackupSchedule($this);
    }

    /**
     * @return array
     *
     * @JMS\VirtualProperty()
     */
    public function getContainerId(){
        $ids[] = null;

        if($this->containers->isEmpty()){
            return $ids;
        }

        $this->containers->first();
        do{
            $ids[] = $this->containers->current()->getId();
        }while($this->containers->next());

        return $ids;
    }
}