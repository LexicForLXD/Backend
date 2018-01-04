<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Host;
use AppBundle\Entity\Image;
use AppBundle\Entity\ImageAlias;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Swagger\Annotations as OAS;
use Symfony\Component\VarDumper\VarDumper;

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
     */
    public function getAllImages(){
        $images = $this->getDoctrine()->getRepository(Image::class)->findAll();

        if (!$images) {
            throw $this->createNotFoundException(
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
     */
    public function getAllImagesOnHost($hostId){
        $images = $this->getDoctrine()->getRepository(Image::class)->findBy(array('host' => $hostId));

        if (!$images) {
            throw $this->createNotFoundException(
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
     * @OAS\Post(path="/hosts/{hostId}/images",
     *     tags={"images"},
     *     description="TO BE DEFINED"
     * )
     */
    public function createNewRemoteSourceImageOnHost($hostId, Request $request){
        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

        if (!$host) {
            throw $this->createNotFoundException(
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

        $em = $this->getDoctrine()->getManager();
        //Create aliases
        if($request->request->get('aliases')) {
            VarDumper::dump($request->request->get('aliases'));
            $aliasArray = $request->request->get('aliases');

            for($i=0; $i<sizeof($aliasArray); $i++){
                $alias = new ImageAlias();
                //TODO Validate if all necessary parameters were provided
                $alias->setName($aliasArray[$i]['name']);
                $alias->setDescription($aliasArray[$i]['description']);
                $em->persist($alias);
                $image->addAlias($alias);
            }
        }

        //TODO add LXD API call based on source data
        //Will be set with the value provided by the lxd api response

        $image->setFingerprint("JSKAJLSJASJ21344A3A3S1A2S34S5A6S");
        $image->setArchitecture("amd64");
        $image->setSize(12346);

        $em->persist($image);
        $em->flush();

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
     *          response=200,
     *          description="Image with the specified ImageId successfully deleted",
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No Image with the ImageId found or the image couldn't be deleted ",
     *      ),
     * )
     */
    public function deleteImage($imageId){
        $image = $this->getDoctrine()->getRepository(Image::class)->find($imageId);

        if (!$image) {
            throw $this->createNotFoundException(
                'No Image found for id ' . $imageId
            );
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($image);
        $em->flush();

        //TODO Delete Image from Host

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
     */
    public function getSingleImage($imageId){
        $images = $this->getDoctrine()->getRepository(Image::class)->find($imageId);

        if (!$images) {
            throw $this->createNotFoundException(
                'No Image for ID '.$imageId.' found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($images, 'json');
        return new Response($response);
    }
}