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

class ImageController extends BaseController
{
    /**
     * Get all Images
     *
     * @Route("/images", name="images_all", methods={"GET"})
     *
     * @throws ElementNotFoundException
     */
    public function getAllImages()
    {
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
     * @throws ElementNotFoundException
     */
    public function getAllImagesOnHost($hostId)
    {
        $images = $this->getDoctrine()->getRepository(Image::class)->findBy(array('host' => $hostId));

        if (!$images) {
            throw new ElementNotFoundException(
                'No Images for Host ' . $hostId . ' found'
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
     */
    public function createImage(int $hostId, Request $request, ImageWorker $imageWorker, EntityManagerInterface $em)
    {
        $host = $this->getDoctrine()->getRepository(Host::class)->find($hostId);

        if (!$host) {
            throw new ElementNotFoundException(
                'No Host for ' . $hostId . ' found'
            );
        }

        if (!$request->request->has('source')) {
            throw new WrongInputException("Missing source object in json");
        }

        $source = $request->request->get('source');

        switch ($source['type']) {
            case "container":
                //Check if container exists on host
                $container = $this->getDoctrine()->getRepository(Container::class)->findBy(['name' => $source['name'], 'host' => $host]);
                if (!$container) {
                    throw new ElementNotFoundException(
                        'No Container for name ' . $source['name'] . ' with host ' . $host->getId() . ' found'
                    );
                }

                $image = new Image();
                $image->setHost($host);

                if ($request->request->has('filename')) {
                    $image->setFilename($request->request->get('filename'));
                }
                if ($request->request->has('public')) {
                    $image->setPublic($request->request->get('public'));
                }
                if ($request->request->has('properties')) {
                    $image->setProperties($request->request->get('properties'));
                }

                $this->validation($image);

                //Create aliases
                if ($request->request->has('aliases')) {
                    $aliasArray = $request->request->get('aliases');

                    for ($i = 0; $i < sizeof($aliasArray); $i++) {
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

                if ($request->request->has('filename')) {
                    $image->setFilename($request->request->get('filename'));
                }
                if ($request->request->has('public')) {
                    $image->setPublic($request->request->get('public'));
                }
                if ($request->request->has('properties')) {
                    $image->setProperties($request->request->get('properties'));
                }

                $this->validation($image);

                //Create aliases
                if ($request->request->has('aliases')) {
                    $aliasArray = $request->request->get('aliases');

                    for ($i = 0; $i < sizeof($aliasArray); $i++) {
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

                $imageWorker->later(0)->createImage($image->getId(), $request->getContent());

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
     * @param $imageId
     * @param ImageApi $api
     * @param ImageAliasApi $aliasApi
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws ConnectionErrorException
     * @throws ElementNotFoundException
     * @throws WrongInputException
     */
    public function deleteImage($imageId, ImageApi $api, ImageAliasApi $aliasApi, EntityManagerInterface $em)
    {
        $image = $this->getDoctrine()->getRepository(Image::class)->find($imageId);

        if (!$image) {
            throw new ElementNotFoundException(
                'No Image found for id ' . $imageId
            );
        }

        //Image is not created on the Host
        if (!$image->isFinished()) {
            $aliases = $image->getAliases();
            for ($i = 0; $i < $aliases->count(); $i++) {
                $em->remove($aliases->get($i));
            }

            $em->remove($image);
            $em->flush();

            return $this->json([], 204);
        }

        //Image is used by at least one Container
        if ($image->getContainers()->count() > 0) {
            // throw new WrongInputException("The Image is still used by at least one Container");
        }

        $aliases = $image->getAliases();
        for ($i = 0; $i < $aliases->count(); $i++) {
            $result = $aliasApi->removeAliasByName($image->getHost(), $aliases->get($i)->getName());
            if ($result->code != 200 && $result->code != 404) {
                throw new WrongInputException("Couldn't delete alias - " . $result->body->error);
            }
            $imageAlias = $aliases->get($i);
            $image->removeAlias($imageAlias);
            $em->remove($imageAlias);
        }
        $result = $api->removeImageByFingerprint($image->getHost(), $image->getFingerprint());

        $result = $api->getOperationsLinkWithWait($image->getHost(), $result->body->metadata->id);

        if ($result->body->metadata->status_code != 200 && $result->body->metadata->err != "not found") {
            throw new WrongInputException("Couldn't delete image - " . $result->body->metadata->err);
        }

        foreach ($image->getContainers() as $container) {
            $image->removeContainer($container);
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
     * @throws ElementNotFoundException
     */
    public function getSingleImage($imageId)
    {
        $images = $this->getDoctrine()->getRepository(Image::class)->find($imageId);

        if (!$images) {
            throw new ElementNotFoundException(
                'No Image for ID ' . $imageId . ' found'
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
    public function updateImage($imageId, Request $request, ImageApi $api, EntityManagerInterface $em)
    {
        $image = $this->getDoctrine()->getRepository(Image::class)->find($imageId);

        if (!$image) {
            throw new ElementNotFoundException(
                'No Image for ID ' . $imageId . ' found'
            );
        }

        //Image is not created on the Host
        if (!$image->isFinished()) {
            throw new WrongInputException("The Image is not yet created on the Host or there was an error creating it - You can't update the Image in this state");
        }

        $image->setProperties($request->request->get('properties'));

        $image->setPublic($request->request->get('public'));

        $this->validation($image);

        $result = $api->putImageUpdate($image->getHost(), $image->getFingerprint(), $request->getContent());

        if ($result->code != 200) {
            throw new WrongInputException("Couldn't update Image - " . $result->body->error);
        }
        if ($result->body->status_code != 200) {
            throw new WrongInputException("Couldn't update Image - " . $result->body->error);
        }

        //Update Image in DB
        $em->merge($image);
        $em->flush();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($image, 'json');
        return new Response($response);
    }

}