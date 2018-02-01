<?php

namespace Tests\AppBundle\Entity;

use AppBundle\Entity\ContainerStatus;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Config\Definition\Exception\Exception;

class ContainerStatusEntityTest extends WebTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager()
        ;

    }

    /**
     * Check if all setters allow wrong values to allow validation
     */
    public function testWrongAttributes()
    {
        $exception = false;
        try {
            $containerStatus = new ContainerStatus();
            $containerStatus->setNagiosEnabled("TRUE");
            $containerStatus->setNagiosUrl(true);
            $containerStatus->setSourceNumber("2");
            $containerStatus->setCheckName(1);
            $containerStatus->setNagiosName(2);
        }catch (Exception $e){
            $exception = true;
        }

        $this->assertTrue(!$exception); // Should be false = no exception triggered
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->em->close();
    }
}
