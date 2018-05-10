<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Container;
use AppBundle\Entity\Host;
use AppBundle\Entity\Image;
use AppBundle\Entity\ImageAlias;
use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Exception\WrongInputException;
use AppBundle\Exception\WrongInputExceptionArray;
use AppBundle\Service\LxdApi\ImageAliasApi;
use AppBundle\Service\LxdApi\ImageApi;
use AppBundle\Worker\ImageWorker;
use Doctrine\ORM\EntityManagerInterface;
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
     * Create a new Image
     * @Route("/hosts/{hostId}/images", name="create_image", methods={"POST"})
     *
     * @param int $hostId
     * @param Request $request
     * @param ImageWorker $imageWorker
     * @param EntityManagerInterface $em
     * @return Response
     * @throws ElementNotFoundException
     * @throws WrongInputException
     * @throws WrongInputExceptionArray
     * @OAS\Post(path="/hosts/{hostId}/images",
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
     *      description="Same body as the LXD Request body to create an Image from stopped Container or LXD Request body for source image case",
     *      name="body",
     *      in="body",
     *      required=true,
     *      ),
     *      @OAS\Response(
     *          response=202,
     *          description="The placeholder image - some elements will be added after the image was async created - finished will then change to true - if the creation fails, finished stays false and an error attribute displays the error",
     *          @OAS\JsonContent(ref="#/components/schemas/image"),
     *          @OAS\Schema(
     *              type="array"
     *          ),
     *      ),
     *     @OAS\Response(
     *          response=404,
     *          description="No Host for the provided id found",
     *      ),
     *      @OAS\Response(
     *          response=400,
     *          description="Validation failed or there is a LXD Error",
     *      ),
     * )
     */
    public function createImage(int $hostId, Request $request, ImageWorker $imageWorker, EntityManagerInterface $em){
        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

        if (!$host) {
            throw new ElementNotFoundException(
                'No Host for '.$hostId.' found'
            );
        }

        if(!$request->request->has('source')){
            throw new WrongInputException("Missing source object in json");
        }

        $source = $request->request->get('source');

        switch ($source['type']){
            case "container":
                //Check if container exists on host
                $container = $this->getDoctrine()->getRepository(Container::class)->findBy(['name' => $source['name'], 'host' => $host]);
                if (!$container) {
                    throw new ElementNotFoundException(
                        'No Container for name '.$source['name'].' with host '.$host->getId().' found'
                    );
                }

                $image = new Image();
                $image->setHost($host);

                if($request->request->has('filename')) {
                    $image->setFilename($request->request->get('filename'));
                }
                if($request->request->has('public')) {
                    $image->setPublic($request->request->get('public'));
                }
                if($request->request->has('properties')) {
                    $image->setProperties($request->request->get('properties'));
                }

                $this->validation($image);

                //Create aliases
                if($request->request->has('aliases')) {
                    $aliasArray = $request->request->get('aliases');

                    for($i=0; $i<sizeof($aliasArray); $i++){
                        $alias = new ImageAlias();
                        $alias->setName($aliasArray[$i]['name']);
                        $alias->setDescription($aliasArray[$i]['description']);
                        $this->validation($alias);
                        $em->persist($alias);
                        $image->addAlias($alias);
                    }
                }

                $image->setFinished(false);
                $em->persist($image);
                $em->flush();

                $imageWorker->later()->createImage($image, $request->getContent());

                $serializer = $this->get('jms_serializer');
                $response = $serializer->serialize($image, 'json');
                return new Response($response, Response::HTTP_ACCEPTED);

            case "image":
                $image = new Image();
                $image->setHost($host);

                if($request->request->has('filename')) {
                    $image->setFilename($request->request->get('filename'));
                }
                if($request->request->has('public')) {
                    $image->setPublic($request->request->get('public'));
                }
                if($request->request->has('properties')) {
                    $image->setProperties($request->request->get('properties'));
                }

                $this->validation($image);

                //Create aliases
                if($request->request->has('aliases')) {
                    $aliasArray = $request->request->get('aliases');

                    for($i=0; $i<sizeof($aliasArray); $i++){
                        $alias = new ImageAlias();
                        $alias->setName($aliasArray[$i]['name']);
                        $alias->setDescription($aliasArray[$i]['description']);
                        $this->validation($alias);
                        $em->persist($alias);
                        $image->addAlias($alias);
                    }
                }

                $image->setFinished(false);

                $em->persist($image);
                $em->flush();

                $imageWorker->later()->createImage($image, $request->getContent());

                $serializer = $this->get('jms_serializer');
                $response = $serializer->serialize($image, 'json');
                return new Response($response, Response::HTTP_ACCEPTED);

            default:
                throw new WrongInputException("Please use source image or container body");
        }
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
     *          description="No Image with the ImageId found",
     *      ),
     *     @OAS\Response(
     *          response=400,
     *          description="There was an error deleting the Image, the error contains the message 'Couldn't delete alias - {LXD-Error}' or 'Couldn't delete image - {LXD-Error}'",
     *      ),
     * )
     *
     * @param $imageId
     * @param ImageApi $api
     * @param ImageAliasApi $aliasApi
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws ConnectionErrorException
     * @throws ElementNotFoundException
     * @throws WrongInputException
     */
    public function deleteImage($imageId, ImageApi $api, ImageAliasApi $aliasApi, EntityManagerInterface $em){
        $image = $this->getDoctrine()->getRepository(Image::class)->find($imageId);

        if (!$image) {
            throw new ElementNotFoundException(
                'No Image found for id ' . $imageId
            );
        }

        //Image is not created on the Host
        if(!$image->isFinished()){
            $aliases = $image->getAliases();
            for($i = 0; $i < $aliases->count(); $i++){
                $em->remove($aliases->get($i));
            }

            $em->remove($image);
            $em->flush();

            return $this->json([], 204);
        }

        //Image is used by at least one Container
        if($image->getContainers()->count() > 0){
            throw new WrongInputException("The Image is still used by at least one Container");
        }

        $aliases = $image->getAliases();
        for($i = 0; $i < $aliases->count(); $i++){
            $result = $aliasApi->removeAliasByName($image->getHost(), $aliases->get($i)->getName());
            if($result->code != 200 && $result->code != 404){
                throw new WrongInputException("Couldn't delete alias - ".$result->body->error);
            }
            $imageAlias = $aliases->get($i);
            $image->removeAlias($imageAlias);
            $em->remove($imageAlias);
        }
        $result = $api->removeImageByFingerprint($image->getHost(), $image->getFingerprint());

        $result = $api->getOperationsLinkWithWait($image->getHost(), $result->body->metadata->id);

        if($result->body->metadata->status_code != 200 && $result->body->metadata->err != "not found"){
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

    /**
     * Update an Image by its id
     *
     * @Route("/images/{imageId}", name="update_single_image", methods={"PUT"})
     *
     * @OAS\Put(path="/images/{imageId}",
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
     *      @OAS\Parameter(
     *      description="Same body as the LXD Request body to update an Image via Put",
     *      name="body",
     *      in="body",
     *      required=true,
     *      ),
     *      @OAS\Response(
     *          response=202,
     *          description="The updated Image",
     *          @OAS\JsonContent(ref="#/components/schemas/image"),
     *          @OAS\Schema(
     *              type="array"
     *          ),
     *      ),
     *     @OAS\Response(
     *          response=404,
     *          description="No Image for the provided id found",
     *      ),
     *      @OAS\Response(
     *          response=400,
     *          description="Image update on LXD Api failed, the error message is 'Couldn't update Image - {LXD-Error}'",
     *      ),
     * )
     *
     * @param $imageId
     * @param Request $request
     * @param ImageApi $api
     * @param EntityManagerInterface $em
     * @return Response
     * @throws ConnectionErrorException
     * @throws ElementNotFoundException
     * @throws WrongInputException
     * @throws WrongInputExceptionArray
     */
    public function updateImage($imageId, Request $request, ImageApi $api, EntityManagerInterface $em){
        $image = $this->getDoctrine()->getRepository(Image::class)->find($imageId);

        if (!$image) {
            throw new ElementNotFoundException(
                'No Image for ID '.$imageId.' found'
            );
        }

        //Image is not created on the Host
        if(!$image->isFinished()){
            throw new WrongInputException("The Image is not yet created on the Host or there was an error creating it - You can't update the Image in this state");
        }

        $image->setProperties($request->request->get('properties'));

        $image->setPublic($request->request->get('public'));

        $this->validation($image);

        $result = $api->putImageUpdate($image->getHost(), $image->getFingerprint(), $request->getContent());

        if($result->code != 200){
            throw new WrongInputException("Couldn't update Image - ".$result->body->error);
        }
        if($result->body->status_code != 200){
            throw new WrongInputException("Couldn't update Image - ".$result->body->error);
        }

        //Update Image in DB
        $em->merge($image);
        $em->flush();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($image, 'json');
        return new Response($response);
    }

    /**
     * @param $object
     * @return bool
     * @throws WrongInputExceptionArray
     */
    private function validation($object)
    {
        $validator = $this->get('validator');
        $errors = $validator->validate($object);

        if (count($errors) > 0) {
            $errorArray = array();
            foreach ($errors as $error) {
                $errorArray[$error->getPropertyPath()] = $error->getMessage();
            }
            throw new WrongInputExceptionArray($errorArray);
        }
        return false;
    }
}