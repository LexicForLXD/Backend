<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Profile;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends Controller
{
    /**
     * Get all LXC-Profiles
     *
     * @Route("/profiles", name="profiles_all", methods={"GET"})
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

    /**
     * Get a single LXC-Profile by its id
     *
     * @Route("/profiles/{profileId}", name="profile_single", methods={"GET"})
     */
    public function getSingleProfile($profileId){
        $profiles = $this->getDoctrine()->getRepository(Profile::class)->find($profileId);

        if (!$profiles) {
            throw $this->createNotFoundException(
                'No LXC-Profile for ID '.$profileId.' found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($profiles, 'json');
        return new Response($response);
    }

    /**
     * Create a LXC-Profile
     *
     * @Route("/profiles", name="create_profile", methods={"POST"})
     */
    public function createProfile(){

    }

    /**
     * Edit a existing LXC-Profile
     *
     * @Route("/profiles/{profileId}", name="edit_profile", methods={"PUT"})
     */
    public function editProfile($profileId){

    }

    /**
     * Delete a existing LXC-Profile
     *
     * @Route("/profiles/{profileId}", name="delete_profile", methods={"DELETE"})
     */
    public function deleteProfile($profileId){

    }

}