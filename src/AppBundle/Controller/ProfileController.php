<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Container;
use AppBundle\Entity\Host;
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
     * @param $profileId
     * @param Request $request
     * @return Response
     */
    public function editProfile($profileId, Request $request){
        $profile = $this->getDoctrine()->getRepository(Profile::class)->find($profileId);

        if (!$profile) {
            throw $this->createNotFoundException(
                'No LXC-Profile for ID '.$profileId.' found'
            );
        }

        if($request->request->get('name')) {
            $profile->setName($request->request->get('name'));
        }
        if($request->request->get('description')) {
            $profile->setDescription($request->request->get('description'));
        }
        if($request->request->get('config')) {
            $profile->setConfig($request->request->get('config'));
        }
        if($request->request->get('devices')) {
            $profile->setDevices($request->request->get('devices'));
        }

        if($profile->linkedToHost()){
            $this->updateProfileOnHosts($profile);
        }

        $em = $this->getDoctrine()->getManager();

        $em->persist($profile);
        $em->flush();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($profile, 'json');
        return new Response($response, Response::HTTP_CREATED);
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
            $this->removeProfileFromHosts($profile);
        }

        //Get updated Profile object
        $profile = $this->getDoctrine()->getRepository(Profile::class)->find($profileId);

        $em = $this->getDoctrine()->getManager();

        $em->remove($profile);
        $em->flush();

        return $this->json([], 204);
    }

    /**
     * Used internally in the container creation process to link the profile to host and container
     * and publish the profile to the host if needed
     * @param Profile $profile
     * @param Container $container
     */
    public function enableProfile(Profile $profile, Container $container){
        $profile->addContainer($container);
        $host = $container->getHost();
        if($profile->isHostLinked($host)){
            return;
        }
        $this->createProfileOnHost($profile, $host);
        $profile->addHost($host);

        $em = $this->getDoctrine()->getManager();

        $em->persist($profile);
        $em->flush();
    }

    public function disableProfileForContainer(Profile $profile, Container $container){
        $profile->removeContainer($container);
        $host = $container->getHost();
        $profile->removeHost($host);
        //TODO Remove LXC-Profile from Host
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

    /**
     * Publishes the LXC-Profile to the specified Host via the LXD-API
     * @param Profile $profile
     * @param Host $host
     */
    private function createProfileOnHost(Profile $profile, Host $host){
        //TODO LXD API Call to create LXC-Profile on the specified Host
    }

    /**
     * Removes the LXC-Profile from the specified Host via the LXD-API
     * @param Profile $profile
     * @param Host $host
     */
    private function removeProfileFromHost(Profile $profile, Host $host){
        //TODO LXD API Call to remove LXC-Profile from the specified Host
    }

    /**
     * Used to remove the Profile from als Hosts via the LXD Api
     *
     * @param Profile $profile
     */
    private function removeProfileFromHosts(Profile $profile){
        $hosts = $profile->getHosts();
        while($hosts->next()){
            $host = $hosts->current();
            $this->removeProfileFromHost($profile, $host);

            $profile->removeHost($host);

            //Get updated list of Hosts
            $hosts = $profile->getHosts();
        }
        //Update Profile in the Database
        $em = $this->getDoctrine()->getManager();
        $em->persist($profile);
        $em->flush();
    }

    /**
     * Used to update the LXC-Profile an all hosts where it's used
     *
     * @param Profile $profile
     */
    private function updateProfileOnHosts(Profile $profile){
        $hosts = $profile->getHosts();
        while($hosts->next()){
            $host = $hosts->current();
            //TODO Add LXD Api call to update profile on Host
        }
    }
}