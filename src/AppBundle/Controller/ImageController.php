<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Image;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
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
     * Create a new Image on a specific Host
     *
     *
     * @Route("/hosts/{hostId}/images", name="create_image_on_host", methods={"POST"})
     */
    public function createNewImageOnHost($hostId){

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