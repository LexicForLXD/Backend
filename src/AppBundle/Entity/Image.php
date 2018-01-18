<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as OAS;

/**
 * Class Image
 * @package AppBundle\Entity
 * @ORM\Entity
 * @ORM\Table(name="images")
 *
 * @OAS\Schema(schema="image", type="object")
 */
class Image
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @OAS\Property(example="3")
     * var integer
     */
    protected $id;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @OAS\Property(example="a49d26ce5808075f5175bf31f5cb90561f5023dcd408da8ac5e834096d46b2d8")
     * var string
     */
    protected $fingerprint;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ImageAlias", mappedBy="image")
     * @ORM\JoinColumn(name="alias_id", referencedColumnName="id")
     * @OAS\Property(ref="#/components/schemas/imageAlias")
     * var string
     */
    protected $aliases;

    /**
     * @ORM\Column(type="string", nullable=true)
     *
     * @OAS\Property(example="x86_64")
     * var string
     */
    protected $architecture;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @OAS\Property(example="1602345")
     * var int
     */
    protected $size;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Host")
     * @ORM\JoinColumn(name="host_id", referencedColumnName="id")
     * @JMS\Exclude()
     */
    protected $host;

    /**
     * @ORM\Column(type="boolean")
     * @OAS\Property(example="true")
     * @var bool
     */
    protected $public;

    /**
     * @ORM\Column(type="string")
     * @OAS\Property(example="imageFilename")
     * @var string
     */
    protected $filename;

    /**
     * @ORM\Column(type="json")
     * @OAS\Property(example="{json-Object}")
     * @var array
     */
    protected $properties;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var mixed
     */
    protected $error;
    /**
     * @ORM\Column(type="boolean")
     * @OAS\Property(example="true")
     * @var bool
     */
    protected $finished;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Container", mappedBy="image")
     * @var ArrayCollection
     */
    protected $containers;


    public function __construct()
    {
        $this->aliases = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getFingerprint()
    {
        return $this->fingerprint;
    }

    /**
     * @param mixed $fingerprint
     */
    public function setFingerprint($fingerprint)
    {
        $this->fingerprint = $fingerprint;
    }

    /**
     * @return PersistentCollection
     */
    public function getAliases() : PersistentCollection
    {
        return $this->aliases;
    }

    /**
     * @return mixed
     */
    public function getArchitecture()
    {
        return $this->architecture;
    }

    /**
     * @param mixed $architecture
     */
    public function setArchitecture($architecture)
    {
        $this->architecture = $architecture;
    }

    /**
     * @return mixed
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param mixed $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * @return mixed
     * @OAS\Property(property="hostId", example="1")
     * @JMS\VirtualProperty()
     */
    public function getHostId()
    {
        return $this->host->getId();
    }

    /**
     * @return mixed
     */
    public function getHost()
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
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->public;
    }

    /**
     * @param bool $public
     */
    public function setPublic(bool $public)
    {
        $this->public = $public;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     */
    public function setFilename(string $filename)
    {
        $this->filename = $filename;
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param array $properties
     */
    public function setProperties(array $properties)
    {
        $this->properties = $properties;
    }

    public function addAlias(ImageAlias $imageAlias){
        if ($this->aliases->contains($imageAlias)) {
            return;
        }
        $this->aliases->add($imageAlias);
        $imageAlias->setImage($this);
    }

    public function removeAlias(ImageAlias $imageAlias){
        $this->aliases->remove($imageAlias);
    }

    /**
     * @return bool
     */
    public function isFinished(): bool
    {
        return $this->finished;
    }

    /**
     * @param bool $finished
     */
    public function setFinished(bool $finished)
    {
        $this->finished = $finished;
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param mixed $error
     */
    public function setError($error)
    {
        $this->error = $error;
    }

    /**
     * @return ArrayCollection
     */
    public function getContainers(): ArrayCollection
    {
        return $this->containers;
    }

    /**
     * @param ArrayCollection $containers
     */
    public function setContainers(ArrayCollection $containers): void
    {
        $this->containers = $containers;
    }

    public function addContainer(Container $container)
    {
        if ($this->containers->contains($container)) {
            return;
        }
        $this->containers->add($container);
        $container->setImage($this);
    }


}