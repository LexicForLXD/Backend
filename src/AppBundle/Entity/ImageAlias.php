<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Swagger\Annotations as OAS;

/**
 * Class ImageAlias
 * @package AppBundle\Entity
 * @ORM\Entity
 * @ORM\Table(name="imagealias")
 * @OAS\Schema(schema="imageAlias", type="object")
 */
class ImageAlias
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @OAS\Property(example="3")
     * @var integer
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     * @OAS\Property(example="my-alias")
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(type="string")
     * @OAS\Property(example="This is a description string")
     * @var string
     */
    protected $description;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Image", inversedBy="aliases")
     * @ORM\JoinColumn(name="image_id", referencedColumnName="id")
     * @var mixed
     */
    protected $image;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
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
    public function setName(string $name)
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
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param mixed $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }
}