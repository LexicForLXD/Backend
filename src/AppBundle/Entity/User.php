<?php
/**
 * Created by PhpStorm.
 * User: Leon
 * Date: 06.11.2017
 * Time: 19:50
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * Class Host
 * @package AppBundle\Entity
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $firstName;

    /**
     * @ORM\Column(type="string")
     */
    private $lastName;

    /**
     * @ORM\Column(type="email", unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", unique=true)
     */
    private $apiKey;

}