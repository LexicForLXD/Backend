<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
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
     * @ORM\Column(type="string")
     *
     * @OAS\Property(example="a49d26ce5808075f5175bf31f5cb90561f5023dcd408da8ac5e834096d46b2d8")
     * var string
     */
    protected $fingerprint;

    /**
     * @ORM\Column(type="string")
     *
     * @OAS\Property(example="alpine edge")
     * var string
     */
    protected $alias;

    /**
     * @ORM\Column(type="string")
     *
     * @OAS\Property(example="x86_64")
     * var string
     */
    protected $architecture;

    /**
     * @ORM\Column(type="string")
     *
     * @OAS\Property(example="160.23MB")
     * var string
     */
    protected $size;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Host")
     * @ORM\JoinColumn(name="host_id", referencedColumnName="id")
     * @JMS\Exclude()
     */
    protected $host;

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
     * @return mixed
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param mixed $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
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
     * /**
     * @JMS\VirtualProperty()
     */
    public function getHostId()
    {
        return $this->host->getId();
    }

    public function getHost()
    {
        return $this->host;
    }




}