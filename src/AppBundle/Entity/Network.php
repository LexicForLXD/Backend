<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\ORM\Mapping as ORM;
use Swagger\Annotations as OAS;

/**
 * Class Network
 * @package AppBundle\Entity
 * @ORM\Entity
 * @ORM\Table(name="lxdNetwork")
 *
 * @OAS\Schema(schema="network", type="object")
 */
class Network
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @OAS\Property(example="2")
     * @var integer
     */
    protected $id;

    /**
     * @ORM\Column(type="string", unique=true)
     * @Assert\Type(type="string")
     * @Assert\NotNull()
     *
     * @OAS\Property(example="lxd-network-1")
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Type(type="string")
     *
     * @OAS\Property(example="Some description string")
     * @var string
     */
    protected $description;

    /**
     * @ORM\Column(type="json")
     * @Assert\NotNull()
     * @Assert\Type(type="array")
     *
     * @var array
     */
    protected $config;

    /**
     * @ORM\Column(type="string")
     * @Assert\Type(type="string")
     * @Assert\Choice({"bridge"}, strict="true")
     *
     * @OAS\Property(example="bridge")
     * @var string
     */
    protected $type;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\NotNull()
     * @Assert\Type(type="boolean")
     *
     * @OAS\Property(example=true)
     * @var boolean
     */
    protected $managed;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Type(type="string")
     *
     * @OAS\Property(example="Created")
     * @var string
     */
    protected $status;

    /**
     * @ORM\Column(type="array", nullable=true)
     * @Assert\Type(type="array")
     *
     * @OAS\Property(example="Json array with locations")
     * @var array
     */
    protected $locations;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Container", inversedBy="networks")
     * @ORM\JoinTable(
     *  joinColumns={
     *      @ORM\JoinColumn(name="lxdnetwork_id", referencedColumnName="id")
     *  },
     *  inverseJoinColumns={
     *      @ORM\JoinColumn(name="container_id", referencedColumnName="id")
     *  }
     * )
     *
     * @JMS\Exclude()
     *
     * @var PersistentCollection
     */
    protected $containers;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Host")
     * @ORM\JoinColumn(name="host_id", referencedColumnName="id")
     * @JMS\Exclude()
     *
     * @var Host
     */
    protected $host;

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
     * @return Host | null
     */
    public function getHost() : ?Host
    {
        return $this->host;
    }

    /**
     * @param Host $host
     */
    public function setHost(Host $host)
    {
        $this->host = $host;
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
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return bool
     */
    public function isManaged(): bool
    {
        return $this->managed;
    }

    /**
     * @param bool $managed
     */
    public function setManaged(bool $managed): void
    {
        $this->managed = $managed;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return PersistentCollection
     */
    public function getContainers(): PersistentCollection
    {
        return $this->containers;
    }

    /**
     * @param PersistentCollection $containers
     */
    public function setContainers(PersistentCollection $containers): void
    {
        $this->containers = $containers;
    }

    /**
     * @return array
     */
    public function getLocations(): ?array
    {
        return $this->locations;
    }

    /**
     * @param array $locations
     */
    public function setLocations($locations): void
    {
        $this->locations = $locations;
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
        $container->addNetwork($this);
    }

    /**
     * @param Container $container
     */
    public function removeContainer(Container $container){
        if (!$this->containers->contains($container)) {
            return;
        }
        $this->containers->removeElement($container);
        $container->removeNetwork($this);
    }

    /**
     * @return array
     *
     * @OAS\Property(property="containerId", example="[1]")
     *
     * @JMS\VirtualProperty()
     */
    public function getContainerId(){
        if($this->containers->isEmpty()){
            return null;
        }

        $this->containers->first();
        do{
            $ids[] = $this->containers->current()->getId();
        }while($this->containers->next());

        return $ids;
    }
}