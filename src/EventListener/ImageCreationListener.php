<?php

namespace EventListener;


use AppBundle\Event\ImageCreationEvent;
use Doctrine\ORM\EntityManager;

class ImageCreationListener
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function onCreationEvent(ImageCreationEvent $event){

    }

}