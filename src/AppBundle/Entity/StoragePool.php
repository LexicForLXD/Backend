<?php
/**
 * Created by IntelliJ IDEA.
 * User: leon
 * Date: 12.06.18
 * Time: 12:50
 */

namespace AppBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Swagger\Annotations as OAS;
use JMS\Serializer\Annotation as JMS;


/**
 * Class StoragePool
 * @package AppBundle\Entity
 * @ORM\Entity
 * @ORM\Table(name="storage_pools")
 *
 * @UniqueEntity("name")
 *
 * @OAS\Schema(schema="storage_pool", type="object")
 */
class StoragePool
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @OAS\Property(example="14")
     * @var integer
     */
    protected $id;

    /**
     * @ORM\Column(type="text")
     * @Assert\Type(type="string")
     * @Assert\NotBlank()
     * @OAS\Property(example="pool1")
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(type="text")
     * @Assert\Type(type="string")
     * @Assert\NotBlank()
     * @OAS\Property(example="zfs")
     * @var string
     */
    protected $driver;

    /**
     * @var array
     * @ORM\Column(type="json", nullable=true)
     * @OAS\Property(example="{'size': '10GB'}")
     */
    protected $config;


    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Host", inversedBy="storagePools")
     * @ORM\JoinColumn(name="host_id", referencedColumnName="id")
     * @Assert\NotBlank()
     * @JMS\Exclude()
     */
    protected $host;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Container", mappedBy="storagePool")
     * @JMS\Exclude()
     * @var ArrayCollection
     * @JMS\Exclude()
     */
    protected $containers;


    public function __construct()
    {
        $this->containers = new ArrayCollection();
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



    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param mixed $host
     */
    public function setHost($host): void
    {
        $this->host = $host;
    }

    /**
     * @return int | null
     * @OAS\Property(property="hostId", example="1")
     * @JMS\VirtualProperty()
     */
    public function getHostId()
    {
        return $this->host->getId();
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
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * @param string $driver
     */
    public function setDriver($driver): void
    {
        $this->driver = $driver;
    }

    /**
     * @return array|null
     */
    public function getConfig():array
    {
        return json_decode($this->config);
    }

    /**
     * @param $config
     */
    public function setConfig($config): void
    {
        $this->config = $config;
    }



    public function getData(): array
    {
        return [
            "name" => $this->getName(),
            "driver" => $this->getDriver(),
            "config" => $this->getConfig()
        ];
    }

    /**
     * Internally used to check if a storage pool is used by one or more containers
     *
     * @return bool
     */
    public function isUsedByContainer() : bool {
        if($this->containers->count() > 0){
            return true;
        }

        return false;
    }

}