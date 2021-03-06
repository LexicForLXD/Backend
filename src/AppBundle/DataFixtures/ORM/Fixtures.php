<?php
/**
 * Created by PhpStorm.
 * User: Leon
 * Date: 09.11.2017
 * Time: 21:33
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\User;
use AppBundle\Entity\Client;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class Fixtures extends Fixture
{
    private $encoder;
    
    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }
    
    public function load(ObjectManager $manager)
    {
        $client = new Client();
        $client->setAllowedGrantTypes(['password', 'refresh_token']);
        $client->setSecret('4ok2x70rlfokc8g0wws8c8kwcokw80k44sg48goc0ok4w0so0k');
        $client->setRandomId('3bcbxd9e24g0gk4swg0kwgcwg4o8k8g4g888kwc44gcc0gwwk4');
        $manager->persist($client);

        $user = new User();
        $user->setEmail('test@test.de');
        $user->setFirstName('Max');
        $user->setLastName('Mustermann');
        $user->setUsername('mmustermann');
        $user->setPassword($this->encoder->encodePassword($user, 'password'));
        $user->setRoles(['ROLE_ADMIN']);
        $manager->persist($user);

        $manager->flush();
    }
}
