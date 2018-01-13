<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Container;
use AppBundle\Entity\Host;
use AppBundle\Entity\Image;
use AppBundle\Entity\ImageAlias;
use AppBundle\Event\ImageCreationEvent;
use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Exception\WrongInputException;
use AppBundle\Service\LxdApi\ImageAliasApi;
use AppBundle\Service\LxdApi\ImageApi;
use AppBundle\Service\LxdApi\OperationsRelayApi;
use Httpful\Exception\ConnectionErrorException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Swagger\Annotations as OAS;

class ImageController extends Controller
{
    /**
     * Get all Images
     *
     * @Route("/images", name="images_all", methods={"GET"})
     *
     * @OAS\Get(path="/images",
     *     tags={"images"},
     *      @OAS\Response(
     *          response=200,
     *          description="List of all Images",
     *          @OAS\JsonContent(ref="#/components/schemas/image"),
     *          @OAS\Schema(
     *              type="array"
     *          ),
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No Images found",
     *      ),
     * )
     *
     * @throws ElementNotFoundException
     */
    public function getAllImages(){
        $images = $this->getDoctrine()->getRepository(Image::class)->findAll();

        if (!$images) {
            throw new ElementNotFoundException(
                'No Images found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($images, 'json');
        return new Response($response);
    }

    /**
     * Get all Images on a specific Host
     *
     * @Route("/hosts/{hostId}/images", name="all_images_on_host", methods={"GET"})
     *
     * @OAS\Get(path="/hosts/{hostId}/images",
     *     tags={"images"},
     *     @OAS\Parameter(
     *      description="ID of the Host",
     *      in="path",
     *      name="hostId",
     *      required=true,
     *          @OAS\Schema(
     *              type="integer"
     *          ),
     *      ),
     *      @OAS\Response(
     *          response=200,
     *          description="List of all Images for a specified Host",
     *          @OAS\JsonContent(ref="#/components/schemas/image"),
     *          @OAS\Schema(
     *              type="array"
     *          ),
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No Images on the Host found",
     *      ),
     * )
     *
     * @throws ElementNotFoundException
     */
    public function getAllImagesOnHost($hostId){
        $images = $this->getDoctrine()->getRepository(Image::class)->findBy(array('host' => $hostId));

        if (!$images) {
            throw new ElementNotFoundException(
                'No Images for Host '.$hostId.' found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($images, 'json');
        return new Response($response);
    }

    /**
     * Create a new Remote-Image on a specific Host
     *
     *
     * @Route("/hosts/{hostId}/images/remote", name="create_remote_image_on_host", methods={"POST"})
     *
     * @OAS\Post(path="/hosts/{hostId}/images/remote",
     *     tags={"images"},
     *     @OAS\Parameter(
     *      description="ID of the Host",
     *      in="path",
     *      name="hostId",
     *      required=true,
     *        @OAS\Schema(
     *          type="integer"
     *        ),
     *     ),
     *     @OAS\Parameter(
     *      description="Same body as the LXD Request body to create an Image from remote",
     *      name="body",
     *      in="body",
     *      required=true,
     *      ),
     *      @OAS\Response(
     *          response=200,
     *          description="The placeholder image - some elements will be added after the image was async created - finished will then change to true - if the creation fails, finished stays false and an error attribute displays the error",
     *          @OAS\JsonContent(ref="#/components/schemas/image"),
     *          @OAS\Schema(
     *              type="array"
     *          ),
     *      ),
     *      @OAS\Response(
     *          response=400,
     *          description="Validation failed or the Host is unknown",
     *      ),
     * )
     *
     * @param $hostId
     * @param Request $request
     * @param ImageApi $api
     * @param OperationsRelayApi $relayApi
     * @return Response
     *
     * @throws ConnectionErrorException
     * @throws ElementNotFoundException
     */
    public function createNewRemoteSourceImageOnHost($hostId, Request $request, ImageApi $api, OperationsRelayApi $relayApi){
        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

        if (!$host) {
            throw new ElementNotFoundException(
                'No Host for '.$hostId.' found'
            );
        }

        $image = new Image();
        $image->setHost($host);

        if($request->request->get('filename')) {
            $image->setFilename($request->request->get('filename'));
        }
        if($request->request->get('public')) {
            $image->setPublic($request->request->get('public'));
        }
        if($request->request->get('properties')) {
            $image->setProperties($request->request->get('properties'));
        }

        if ($errorArray = $this->validation($image)) {
            return new JsonResponse(['errors' => $errorArray], 400);
        }

        $em = $this->getDoctrine()->getManager();
        //Create aliases
        if($request->request->get('aliases')) {
            $aliasArray = $request->request->get('aliases');

            for($i=0; $i<sizeof($aliasArray); $i++){
                $alias = new ImageAlias();
                $alias->setName($aliasArray[$i]['name']);
                $alias->setDescription($aliasArray[$i]['description']);
                if ($errorArray = $this->validation($alias)) {
                    return new JsonResponse(['errors' => $errorArray], 400);
                }
                $em->persist($alias);
                $image->addAlias($alias);
            }
        }

        $result = $api->createImage($host, $request->getContent());

        if ($result->code != 202) {
            Return new Response(json_encode($result->body));
        }
        if ($result->body->metadata->status_code == 400) {
            Return new Response(json_encode($result->body));
        }

        $image->setFinished(false);

        $em->persist($image);
        $em->flush();

        $dispatcher = $this->get('sb_event_queue');

        $dispatcher->on(ImageCreationEvent::class, date('Y-m-d H:i:s'), $result->body->metadata->id, $host, $image->getId());

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($image, 'json');
        return new Response($response);
    }

    /**
     * Create an Image from a stopped Container
     * @Route("/hosts/{hostId}/images/container", name="create_image_from_contaniner_on_host", methods={"POST"})
     *
     * @throws ElementNotFoundException
     * @throws ConnectionErrorException
     *
     * @OAS\Post(path="/hosts/{hostId}/images/container",
     *     tags={"images"},
     *     description="TO BE DEFINED"
     * )
     */
    public function createImageFromSourceContainer(int $hostId, Request $request, ImageApi $api){
        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

        if (!$host) {
            throw new ElementNotFoundException(
                'No Host for '.$hostId.' found'
            );
        }

        //Check if container exists on host
        if($request->request->get('source')){
            $source = $request->request->get('source');
        }
        $container = $this->getDoctrine()->getRepository(Container::class)->findBy(['name' => $source['name'], 'host' => $host]);
        if (!$container) {
            throw new ElementNotFoundException(
                'No Container for name '.$source['name'].' with host '.$host->getId().' found'
            );
        }

        $image = new Image();
        $image->setHost($host);

        if($request->request->get('filename')) {
            $image->setFilename($request->request->get('filename'));
        }
        if($request->request->get('public')) {
            $image->setPublic($request->request->get('public'));
        }
        if($request->request->get('properties')) {
            $image->setProperties($request->request->get('properties'));
        }

        if ($errorArray = $this->validation($image)) {
            return new JsonResponse(['errors' => $errorArray], 400);
        }

        $em = $this->getDoctrine()->getManager();
        //Create aliases
        if($request->request->get('aliases')) {
            $aliasArray = $request->request->get('aliases');

            for($i=0; $i<sizeof($aliasArray); $i++){
                $alias = new ImageAlias();
                $alias->setName($aliasArray[$i]['name']);
                $alias->setDescription($aliasArray[$i]['description']);
                if ($errorArray = $this->validation($alias)) {
                    return new JsonResponse(['errors' => $errorArray], 400);
                }
                $em->persist($alias);
                $image->addAlias($alias);
            }
        }

        $result = $api->createImage($host, $request->getContent());

        if ($result->code != 202) {
            Return new Response(json_encode($result->body));
        }
        if ($result->body->metadata->status_code == 400) {
            Return new Response(json_encode($result->body));
        }

        $image->setFinished(false);
        $em->persist($image);
        $em->flush();

        $dispatcher = $this->get('sb_event_queue');
        $dispatcher->on(ImageCreationEvent::class, date('Y-m-d H:i:s'), $result->body->metadata->id, $host, $image->getId());

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($image, 'json');
        return new Response($response);
    }

    /**
     * Delete a single Image
     *
     * @Route("/images/{imageId}", name="delete_image", methods={"DELETE"})
     *
     * @OAS\Delete(path="/images/{imageId}",
     *     tags={"images"},
     *     @OAS\Parameter(
     *      description="ID of the Image",
     *      in="path",
     *      name="imageId",
     *      required=true,
     *          @OAS\Schema(
     *              type="integer"
     *          ),
     *      ),
     *      @OAS\Response(
     *          response=204,
     *          description="Image with the specified ImageId successfully deleted",
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No Image with the ImageId found or the image couldn't be deleted ",
     *      ),
     * )
     *
     * @param $imageId
     * @param ImageApi $api
     * @return JsonResponse
     * @throws ConnectionErrorException
     * @throws ElementNotFoundException
     * @throws WrongInputException
     */
    public function deleteImage($imageId, ImageApi $api, ImageAliasApi $aliasApi){
        $image = $this->getDoctrine()->getRepository(Image::class)->find($imageId);

        if (!$image) {
            throw new ElementNotFoundException(
                'No Image found for id ' . $imageId
            );
        }

        $em = $this->getDoctrine()->getManager();

        //Image is not created on the Host
        if(!$image->isFinished()){
            $aliases = $image->getAliases();
            for($i = 0; $i < $aliases->count(); $i++){
                $em->remove($aliases->get($i));
                //TODO fix removeAlias function
                //$image->removeAlias($aliases->get($i));
                //$em->remove($aliases->get($i));
            }

            $em->remove($image);
            $em->flush();

            return $this->json([], 204);
        }

        $aliases = $image->getAliases();
        for($i = 0; $i < $aliases->count(); $i++){
            $result = $aliasApi->removeAliasByName($image->getHost(), $aliases->get($i)->getName());
            if($result->code != 200){
                throw new WrongInputException("Couldn't delete alias - ".$result->body->error);
            }
            $image->removeAlias($aliases->get($i));
            $em->remove($aliases->get($i));
        }
        $result = $api->removeImageByFingerprint($image->getHost(), $image->getFingerprint());

        $result = $api->getOperationsLinkWithWait($image->getHost(), $result->body->metadata->id);

        if($result->body->metadata->status_code != 200){
            throw new WrongInputException("Couldn't delete image - ".$result->body->metadata->err);
        }

        $em->remove($image);
        $em->flush();

        return $this->json([], 204);
    }

    /**
     * Get a single Image
     *
     * @Route("/images/{imageId}", name="get_single_image", methods={"GET"})
     *
     * @OAS\Get(path="/images/{imageId}",
     *     tags={"images"},
     *     @OAS\Parameter(
     *      description="ID of the Image",
     *      in="path",
     *      name="imageId",
     *      required=true,
     *          @OAS\Schema(
     *              type="integer"
     *          ),
     *      ),
     *      @OAS\Response(
     *          response=200,
     *          description="Image with the specified ImageId",
     *          @OAS\JsonContent(ref="#/components/schemas/image"),
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No Images with the ImageId found",
     *      ),
     * )
     *
     * @throws ElementNotFoundException
     */
    public function getSingleImage($imageId){
        $images = $this->getDoctrine()->getRepository(Image::class)->find($imageId);

        if (!$images) {
            throw new ElementNotFoundException(
                'No Image for ID '.$imageId.' found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($images, 'json');
        return new Response($response);
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