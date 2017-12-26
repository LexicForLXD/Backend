<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ImageController extends Controller
{
    /**
     * Get all Images
     *
     * @Route("/images", name="images_all", methods={"GET"})
     */
    public function getAllImages(){

    }

    /**
     * Get all Images on a specific host
     *
     * @Route("/hosts/{hostId}/images", name="all_images_on_host", methods={"GET"})
     */
    public function getAllImagesOnHost($hostId){

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
     */
    public function deleteImage($imageId){

    }

    /**
     * Get a single Image
     *
     * @Route("/images/{imageId}", name="get_single_image", methods={"GET"})
     */
    public function getSingleImage($imageId){

    }
}