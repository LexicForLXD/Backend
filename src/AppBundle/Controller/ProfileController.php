<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Profile;
use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Exception\WrongInputException;
use AppBundle\Exception\WrongInputExceptionArray;
use AppBundle\Service\LxdApi\ProfileApi;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends BaseController
{
    /**
     * Get all LXC-Profiles
     *
     * @Route("/profiles", name="profiles_all", methods={"GET"})
     * 
     * @throws ElementNotFoundException
     */
    public function getAllProfiles()
    {
        $profiles = $this->getDoctrine()->getRepository(Profile::class)->findAll();

        if (!$profiles) {
            throw new ElementNotFoundException(
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
     *
     * @throws ElementNotFoundException
     */
    public function getSingleProfile($profileId)
    {
        $profiles = $this->getDoctrine()->getRepository(Profile::class)->find($profileId);

        if (!$profiles) {
            throw new ElementNotFoundException(
                'No LXC-Profile for ID ' . $profileId . ' found'
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
     *
     * @param Request $request
     * @return JsonResponse|Response
     * @throws WrongInputExceptionArray
     */
    public function createProfile(Request $request)
    {

        $profile = new Profile();

        if ($request->request->has('name')) {
            $profile->setName($request->request->get('name'));
        }
        if ($request->request->has('description')) {
            $profile->setDescription($request->request->get('description'));
        }
        if ($request->request->has('config')) {
            $profile->setConfig($request->request->get('config'));
        }
        if ($request->request->has('devices')) {
            $profile->setDevices($request->request->get('devices'));
        }

        $this->validation($profile);

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
     *
     * @param $profileId
     * @param Request $request
     * @return Response
     * @throws \Httpful\Exception\ConnectionErrorException
     * @throws ElementNotFoundException
     * @throws WrongInputExceptionArray
     */
    public function editProfile($profileId, Request $request)
    {
        $profile = $this->getDoctrine()->getRepository(Profile::class)->find($profileId);

        if (!$profile) {
            throw new ElementNotFoundException(
                'No LXC-Profile for ID ' . $profileId . ' found'
            );
        }

        $profile->setDescription($request->request->get('description'));

        $profile->setConfig($request->request->get('config'));

        $profile->setDevices($request->request->get('devices'));

        $oldName = null;
        if ($request->request->get('name') != $profile->getName()) {
            $oldName = $profile->getName();
            $profile->setName($request->request->get('name'));
        }

        $this->validation($profile);

        if ($profile->linkedToHost()) {
            if ($oldName != null) {
                $result = $this->renameProfileOnHosts($profile, $oldName);
                if ($result['status'] == 'failure') {
                    throw new WrongInputExceptionArray($result);
                }
            }
            $result = $this->updateProfileOnHosts($profile);
            if ($result['status'] == 'failure') {
                throw new WrongInputExceptionArray($result);
            }
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
     *
     * @throws \Httpful\Exception\ConnectionErrorException
     * @throws ElementNotFoundException
     * @throws WrongInputException
     * @throws WrongInputExceptionArray
     */
    public function deleteProfile($profileId)
    {
        $profile = $this->getDoctrine()->getRepository(Profile::class)->find($profileId);

        if (!$profile) {
            throw new ElementNotFoundException(
                'No LXC-Profile found for id ' . $profileId
            );
        }

        if ($profile->isUsedByContainer()) {
            throw new WrongInputException("The LXC-Profile is used by at least one Container");
        }

        if ($profile->linkedToHost()) {
            $result = $this->removeProfileFromHosts($profile);
            if ($result['status'] == 'failure') {
                throw new WrongInputExceptionArray($result);
            }
        }

        //Get updated Profile object
        $profile = $this->getDoctrine()->getRepository(Profile::class)->find($profileId);

        $em = $this->getDoctrine()->getManager();

        $em->remove($profile);
        $em->flush();

        return $this->json([], 204);
    }


    /**
     * Used to remove the Profile from als Hosts via the LXD Api
     *
     * @param Profile $profile
     * @param ProfileApi $profileApi
     * @return array
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    private function removeProfileFromHosts(Profile $profile, ProfileApi $profileApi) : array
    {
        $hosts = $profile->getHosts();
        if ($hosts->isEmpty()) {
            return ['status' => 'success'];
        }
        $return['status'] = 'failure';
        $failure = false;
        for ($i = 0; $i < $hosts->count(); $i++) {
            $host = $hosts->get($i);
            //Remove Profile via LXD-API
            $result = $profileApi->deleteProfileOnHost($host, $profile);

            if ($result->code != 204) {
                $return[$host->getName()] = $result->body;
                $failure = true;
            } else {
                $profile->removeHost($host);
            }
        }

        //Update Profile in the Database
        $em = $this->getDoctrine()->getManager();
        $em->persist($profile);
        $em->flush();

        if ($failure) {
            return $return;
        }
        return ['status' => 'success'];
    }

    /**
     * Used to update the LXC-Profile an all hosts where it's used
     *
     * @param Profile $profile
     * @param ProfileApi $profileApi
     * @return array
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    private function updateProfileOnHosts(Profile $profile, ProfileApi $profileApi) : array
    {
        $hosts = $profile->getHosts();
        if ($hosts->isEmpty()) {
            return ['status' => 'success'];
        }
        $return['status'] = 'failure';
        $failure = false;
        for ($i = 0; $i < $hosts->count(); $i++) {
            $host = $hosts->get($i);
            //Update Profile via LXD-API
            $result = $profileApi->updateProfileOnHost($host, $profile);
            if ($result->code != 200) {
                $return[$host->getName()] = $result->body;
                $failure = true;
            }
        }
        if ($failure) {
            return $return;
        }
        return ['status' => 'success'];
    }

    /**
     * Used to rename the LXC-Profile on all hosts where it's used
     *
     * @param Profile $profile
     * @param String $oldName
     * @param ProfileApi $profileApi
     * @return array
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    private function renameProfileOnHosts(Profile $profile, String $oldName, ProfileApi $profileApi) : array
    {
        $hosts = $profile->getHosts();
        if ($hosts->isEmpty()) {
            return ['status' => 'success'];
        }
        $return['status'] = 'failure';
        $failure = false;
        for ($i = 0; $i < $hosts->count(); $i++) {
            $host = $hosts->get($i);
            //Update Profile via LXD-API
            $result = $profileApi->renameProfileOnHost($host, $profile, $oldName);
            if ($result->code != 201) {
                $return[$host->getName()] = $result->body;
                $failure = true;
            }
        }
        if ($failure) {
            return $return;
        }
        return ['status' => 'success'];
    }
}