<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Profile;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ProfileController extends Controller
{
    /**
     * Get all LXC-Profiles
     *
     * @Route("/images", name="images_all", methods={"GET"})
     */
    public function getAllProfiles(){
        $profiles = $this->getDoctrine()->getRepository(Profile::class)->findAll();

        if (!$profiles) {
            throw $this->createNotFoundException(
                'No LXC-Profiles found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($profiles, 'json');
        return new Response($response);
    }

}