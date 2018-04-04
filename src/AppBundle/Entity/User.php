<?php
/**
 * Created by PhpStorm.
 * User: Leon
 * Date: 06.11.2017
 * Time: 19:50
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Class Host
 * @package AppBundle\Entity
 * @ORM\Entity
 * @ORM\Table(name="users")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 * @UniqueEntity("email")
 * @UniqueEntity("username")
 */
class User implements AdvancedUserInterface, \Serializable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     * @Assert\Type(type="string")
     * @Assert\NotBlank()
     */
    protected $firstName;

    /**
     * @ORM\Column(type="string")
     * @Assert\Type(type="string")
     * @Assert\NotBlank()
     */
    protected $lastName;

    /**
     * @ORM\Column(type="string", length=30, unique=true)
     * @Assert\Type(type="string")
     * @Assert\Length(
     *     min=5,
     *     max=30,
     *     minMessage = "Your username must be at least {{ limit }} characters long",
     *     maxMessage = "Your username cannot be longer than {{ limit }} characters"
     * )
     * @Assert\NotBlank()
     */
    protected $username;

    /**
     * @ORM\Column(type="string")
     * @JMS\Exclude()
     * @Assert\NotBlank()
     *
     */
    protected $password;

    /**
     * @ORM\Column(type="string", length=60, unique=true)
     * @Assert\Email()
     * @Assert\NotBlank()
     * @Assert\Length(
     *     min=5,
     *     max=60,
     *     minMessage = "Your email must be at least {{ limit }} characters long",
     *     maxMessage = "Your email cannot be longer than {{ limit }} characters"
     * )
     */
    protected $email;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     */
    protected $isActive;

    /**
     * @ORM\Column(name="roles", type="array")
     */
    protected $roles;

    /**
     * @ORM\Column(name="public_ssh_key", type="string", nullable=true)
     */
    protected $sshKey;


    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->isActive = true;
        $this->roles = array();
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return mixed
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * @return mixed
     */
    public function getSshKey()
    {
        return $this->sshKey;
    }

    public function getSalt()
    {
        // you *may* need a real salt depending on your encoder
        // see section on salt below
        return null;
    }

    public function eraseCredentials()
    {
    }

    public function isAccountNonExpired()
    {
        return true;
    }

    public function isAccountNonLocked()
    {
        return true;
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }

    public function isEnabled()
    {
        return $this->isActive;
    }

    /** @see \Serializable::serialize() */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->username,
            $this->email,
            $this->password,
            $this->firstName,
            $this->lastName,
            $this->sshKey,
            $this->isActive
        ));
    }

    /** @see \Serializable::unserialize() */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->username,
            $this->email,
            $this->password,
            $this->firstName,
            $this->lastName,
            $this->sshKey,
            $this->isActive
            ) = unserialize($serialized);
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param mixed $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * @param mixed $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @param mixed $isActive
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
    }

    /**
     * @param mixed $roles
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;
    }

    /**
     * @param mixed $sshKey
     */
    public function setSshKey($sshKey)
    {
        $this->sshKey = $sshKey;
    }
}