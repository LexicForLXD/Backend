<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Container;
use AppBundle\Entity\Profile;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
     * @param Request $request
     * @return JsonResponse|Response
     */
    public function createProfile(Request $request){

        $profile = new Profile();

        $profile->setName($request->request->get('name'));
        $profile->setDescription($request->request->get('description'));
        $profile->setConfig($request->request->get('config'));
        $profile->setDevices($request->request->get('devices'));

        if ($errorArray = $this->validation($profile)) {
            return new JsonResponse(['errors' => $errorArray], 400);
        }

        $em = $this->getDoctrine()->getManager();

        $em->persist($profile);
        $em->flush();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($profile, 'json');
        return new Response($response, Response::HTTP_CREATED);
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
        $profile = $this->getDoctrine()->getRepository(Profile::class)->find($profileId);

        if (!$profile) {
            throw $this->createNotFoundException(
                'No LXC-Profile found for id ' . $profileId
            );
        }

        if($profile->isUsedByContainer()){
            return new JsonResponse(['errors' => 'The LXC-Profile is used by at least one Container'], Response::HTTP_BAD_REQUEST);
        }

        if($profile->linkedToHost()){
            // TODO add logic to delete LXC-Profile from all hosts then delete Profile from Database
            return new JsonResponse(['errors' => 'This variation is not implemented'], Response::HTTP_NOT_IMPLEMENTED);
        }

        $em = $this->getDoctrine()->getManager();

        $em->remove($profile);
        $em->flush();

        return $this->json([], 204);
    }

    /**
     * Used internally in the container creation process to link the profile to host and container
     * and publish the container to the host if needed
     * @param Profile $profile
     * @param Container $container
     */
    public function useProfile(Profile $profile, Container $container){

    }

    private function validation($object)
    {
        $validator = $this->get('validator');
        $errors = $validator->validate($object);

        if (count($errors) > 0) {
            $errorArray = array();
            foreach ($errors as $error) {
                $errorArray[$error->getPropertyPath()] = $error->getMessage();
            }
            return $errorArray;
        }
        return false;
    }
}