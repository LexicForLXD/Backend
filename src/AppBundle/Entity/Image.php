<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Image
 * @package AppBundle\Entity
 * @ORM\Entity
 * @ORM\Table(name="images")
 */
class Image
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var integer
     */
    protected $id;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Type("string")
     *
     * @var string
     */
    protected $fingerprint;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ImageAlias", mappedBy="image")
     * @ORM\JoinColumn(name="alias_id", referencedColumnName="id")
     * var string
     */
    protected $aliases;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Type("string")
     *
     * @var string
     */
    protected $architecture;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Type("int")
     *
     * @var int
     */
    protected $size;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Host")
     * @ORM\JoinColumn(name="host_id", referencedColumnName="id")
     * @var Host
     * @JMS\Exclude()
     */
    protected $host;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\NotNull
     * @Assert\Type("bool")
     *
     * @var bool
     */
    protected $public;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Type("string")
     *
     * @var string
     */
    protected $filename;

    /**
     * @ORM\Column(type="json", nullable=true)
     *
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
     * @var bool
     */
    protected $finished;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Container", mappedBy="image")
     * @JMS\Exclude()
     * @var ArrayCollection
     */
    protected $containers;


    public function __construct()
    {
        $this->aliases = new ArrayCollection();
        $this->containers = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @return string | null
     */
    public function getFingerprint() : ? string
    {
        return $this->fingerprint;
    }

    /**
     * @param string $fingerprint
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
     * @return string | null
     */
    public function getArchitecture() : ? string
    {
        return $this->architecture;
    }

    /**
     * @param string $architecture
     */
    public function setArchitecture($architecture)
    {
        $this->architecture = $architecture;
    }

    /**
     * @return int | null
     */
    public function getSize() : ? int
    {
        return $this->size;
    }

    /**
     * @param int $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * @return int | null
     * @JMS\VirtualProperty()
     */
    public function getHostId()
    {
        return $this->host->getId();
    }

    /**
     * @return Host | null
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
    public function isPublic() : bool
    {
        return $this->public;
    }

    /**
     * @param bool $public
     */
    public function setPublic($public)
    {
        $this->public = $public;
    }

    /**
     * @return string | null
     */
    public function getFilename() : ? string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    /**
     * @return array | null
     */
    public function getProperties() : ? array
    {
        return $this->properties;
    }

    /**
     * @param array $properties
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
    }

    public function addAlias(ImageAlias $imageAlias)
    {
        if ($this->aliases->contains($imageAlias)) {
            return;
        }
        $this->aliases->add($imageAlias);
        $imageAlias->setImage($this);
    }

    public function removeAlias(ImageAlias $imageAlias)
    {
        $this->aliases->removeElement($imageAlias);
    }

    /**
     * @return bool
     */
    public function isFinished() : bool
    {
        return $this->finished;
    }

    /**
     * @param bool $finished
     */
    public function setFinished($finished)
    {
        $this->finished = $finished;
    }

    /**
     * @return string | null
     */
    public function getError() : ? string
    {
        return $this->error;
    }

    /**
     * @param string $error
     */
    public function setError($error)
    {
        $this->error = $error;
    }

    /**
     * @return PersistentCollection
     */
    public function getContainers() : PersistentCollection
    {
        return $this->containers;
    }

    /**
     * @return array
     *
     * @JMS\VirtualProperty()
     */
    public function getContainerId()
    {
        return $this->containers->map(function ($o) {
            return $o->getId();
        })->toArray();
    }

    /**
     * @param ArrayCollection $containers
     */
    public function setContainers(ArrayCollection $containers) : void
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


    public function removeContainer(Container $container)
    {
        if ($this->containers->removeElement($container)) {
            $container->setImage();
        }
    }


}