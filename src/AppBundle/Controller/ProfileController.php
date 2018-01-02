<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Container;
use AppBundle\Entity\Host;
use AppBundle\Entity\Profile;
use AppBundle\Service\LxdApi\ProfileApi;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as OAS;
use Symfony\Component\VarDumper\VarDumper;

class ProfileController extends Controller
{
    /**
     * Get all LXC-Profiles
     *
     * @Route("/profiles", name="profiles_all", methods={"GET"})
     *
     * @OAS\Get(path="/profiles",
     *     tags={"profiles"},
     *      @OAS\Response(
     *          response=200,
     *          description="List of all LXC-Profiles",
     *          @OAS\JsonContent(ref="#/components/schemas/profile"),
     *          @OAS\Schema(
     *              type="array"
     *          ),
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No LXC-Profiles found",
     *      ),
     * )
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
     *
     * @OAS\Get(path="/profiles/{profileId}",
     *  tags={"profiles"},
     *  @OAS\Response(
     *      response=200,
     *      description="Detailed information about a specific LXC-Profile",
     *      @OAS\JsonContent(ref="#/components/schemas/profile"),
     *  ),
     *  @OAS\Response(
     *      description="No LXC-Profile for the provided id found",
     *      response=404
     * ),
     *
     *  @OAS\Parameter(
     *      description="ID of the LXC-Profile",
     *      in="path",
     *      name="profileId",
     *      required=true,
     *      @OAS\Schema(
     *          type="integer"
     *      ),
     *  ),
     *)
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
     *
     * @OAS\Post(path="/profiles",
     * tags={"profiles"},
     * @OAS\Parameter(
     *      description="Parameters for the new LXC-Profile",
     *      name="body",
     *      in="body",
     *      required=true,
     *      @OAS\Schema(
     *      @OAS\Property(
     *          property="name",
     *          type="string",
     *      ),
     *      @OAS\Property(
     *          property="description",
     *          type="string"
     *      ),
     *      @OAS\Property(
     *          property="config",
     *          type="string"
     *      ),
     *      @OAS\Property(
     *          property="devices",
     *          type="string"
     *      ),
     *  ),
     * ),
     * @OAS\Response(
     *  description="The provided values for the LXC-Profile are not valid",
     *  response=400
     * ),
     * @OAS\Response(
     *  description="The LXC-Profile was successfully created",
     *  response=201,
     *  @OAS\JsonContent(ref="#/components/schemas/profile"),
     * ),
     * )
     *
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
     *
     * @OAS\Put(path="/profiles/{profileId}",
     * tags={"profiles"},
     * @OAS\Parameter(
     *      description="Parameters which should be used to update the LXC-Profile",
     *      name="body",
     *      in="body",
     *      required=true,
     *      @OAS\Schema(
     *      @OAS\Property(
     *          property="name",
     *          type="string",
     *      ),
     *      @OAS\Property(
     *          property="description",
     *          type="string"
     *      ),
     *      @OAS\Property(
     *          property="config",
     *          type="string"
     *      ),
     *      @OAS\Property(
     *          property="devices",
     *          type="string"
     *      ),
     *  ),
     * ),
     * @OAS\Parameter(
     *  description="ID of the LXC-Profile",
     *  in="path",
     *  name="profileId",
     *  required=true,
     *  @OAS\Schema(
     *     type="integer"
     *  ),
     * ),
     * @OAS\Response(
     *  description="No LXC-Profile for the provided id found",
     *  response=404
     * ),
     * @OAS\Response(
     *  description="The provided values for the LXC-Profile are not valid",
     *  response=400
     * ),
     * @OAS\Response(
     *  description="The LXC-Profile was successfully updated",
     *  @OAS\JsonContent(ref="#/components/schemas/profile"),
     *  response=201
     * ),
     * )
     *
     * @param $profileId
     * @param Request $request
     * @return Response
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function editProfile($profileId, Request $request){
        $profile = $this->getDoctrine()->getRepository(Profile::class)->find($profileId);

        if (!$profile) {
            throw $this->createNotFoundException(
                'No LXC-Profile for ID '.$profileId.' found'
            );
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
        $oldName = null;
        if($request->request->get('name')) {
            $oldName = $profile->getName();
            $profile->setName($request->request->get('name'));
        }

        if ($errorArray = $this->validation($profile)) {
            return new JsonResponse(['errors' => $errorArray], 400);
        }

        if($profile->linkedToHost()){
            if($oldName != null) {
                $result = $this->renameProfileOnHosts($profile, $oldName);

                $result = $this->updateProfileOnHosts($profile);
                if($result['status'] == 'failure'){
                    return new Response(json_encode($result), Response::HTTP_BAD_REQUEST);
                }
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
     * @OAS\Delete(path="/profiles/{profileId}",
     *  tags={"profiles"},
     *  @OAS\Parameter(
     *      description="ID of the LXC-Profile",
     *      in="path",
     *      name="profileId",
     *      required=true,
     *      @OAS\Schema(
     *          type="integer"
     *      ),
     *  ),
     *  @OAS\Response(
     *      response=204,
     *      description="The LXC-Profile was successfully deleted",
     *  ),
     *  @OAS\Response(
     *      response=400,
     *      description="The LXC-Profile couldn't be deleted, because it is used by at least one Container",
     *  ),
     *  @OAS\Response(
     *      description="No LXC-Profile for the provided id found",
     *      response=404
     * ),
     *)
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
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function enableProfile(Profile $profile, Container $container){
        $profile->addContainer($container);
        $host = $container->getHost();
        if($profile->isHostLinked($host)){
            return;
        }

        //Create Profile via LXD-API
        $profileApi = $this->container->get('lxd.api.profile');
        $profileApi->createProfileOnHost($host, $profile);

        $profile->addHost($host);

        $em = $this->getDoctrine()->getManager();

        $em->persist($profile);
        $em->flush();
    }

    /**
     * Used internally to remove the link from a Profile to a Container and Host, it will also remove the Profile from the Host
     * if this was the last Container using it
     * @param Profile $profile
     * @param Container $container
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function disableProfileForContainer(Profile $profile, Container $container){
        $profile->removeContainer($container);
        $host = $container->getHost();
        //Check if this container was the only one using this profile on the host
        if($profile->numberOfContainersMatchingProfile($host->getContainers()) == 1){
            $profile->removeHost($host);
            //Remove Profile via LXD-API
            $profileApi = $this->container->get('lxd.api.profile');
            $profileApi->deleteProfileOnHost($host, $profile);
            return;
        }
        //LXC-Profile should remain on Host
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
     * @return bool
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    private function removeProfileFromHosts(Profile $profile){
        $hosts = $profile->getHosts();
        if($hosts->isEmpty()){
            return true;
        }
        for($i=0; $i<$hosts->count(); $i++){
            $host = $hosts->get($i);
            //Remove Profile via LXD-API
            $profileApi = $this->container->get('lxd.api.profile');
            $profileApi->deleteProfileOnHost($host, $profile);

            $profile->removeHost($host);

            //TODO Return false for errors
        }

        //Update Profile in the Database
        $em = $this->getDoctrine()->getManager();
        $em->persist($profile);
        $em->flush();
        return true;
    }

    /**
     * Used to update the LXC-Profile an all hosts where it's used
     *
     * @param Profile $profile
     * @return array
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    private function updateProfileOnHosts(Profile $profile) {
        $hosts = $profile->getHosts();
        if($hosts->isEmpty()){
            return ['status' => 'success'];
        }
        $return['status'] = 'failure';
        $failure = false;
        for($i=0; $i<$hosts->count(); $i++){
            $host = $hosts->get($i);
            //Update Profile via LXD-API
            $profileApi = $this->container->get('lxd.api.profile');
            $result = $profileApi->updateProfileOnHost($host, $profile);
            if($result->code != 200){
                $return[$host->getName()] = $result->body;
                $failure = true;
            }
        }
        if($failure){
            return $return;
        }
        return array('status' => 'success');
    }

    /**
     * Used to rename the LXC-Profile on all hosts where it's used
     *
     * @param Profile $profile
     * @param String $oldName
     * @return bool
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    private function renameProfileOnHosts(Profile $profile, String $oldName) : bool {
        $hosts = $profile->getHosts();
        if($hosts->isEmpty()){
            return true;
        }
        for($i=0; $i<$hosts->count(); $i++){
            $host = $hosts->get($i);
            //Update Profile via LXD-API
            $profileApi = $this->container->get('lxd.api.profile');
            $profileApi->renameProfileOnHost($host, $profile, $oldName);

            //TODO Return false for errors
        }
        return true;
    }
}