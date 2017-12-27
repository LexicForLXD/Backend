<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Swagger\Annotations as OAS;

/**
 * Class Profile
 * @package AppBundle\Entity
 * @ORM\Entity
 * @ORM\Table(name="lxc-profiles")
 *
 * @OAS\Schema(schema="profile", type="object")
 */
class Profile
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @OAS\Property(example="2")
     * var integer
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     *
     * @OAS\Property(example="TODO example description")
     * var string
     */
    protected $description;

    /**
     * @ORM\Column(type="json_array")
     *
     * @OAS\Property(example="TODO example config")
     * var json_array
     */
    protected $config;

    /**
     * @ORM\Column(type="json_array")
     *
     * @OAS\Property(example="TODO example devices")
     * var json_array
     */
    protected $devices;
}