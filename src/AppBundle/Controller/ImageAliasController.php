<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Image;
use AppBundle\Entity\ImageAlias;
use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Exception\WrongInputException;
use AppBundle\Exception\WrongInputExceptionArray;
use AppBundle\Service\LxdApi\ImageAliasApi;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ImageAliasController extends BaseController
{
    /**
     * Create an ImageAlias for an existing Image
     *
     * @Route("/images/{imageId}/aliases", name="create_alias_for_image", methods={"POST"})
     *
     * @throws ElementNotFoundException
     * @throws WrongInputException
     * @throws \Httpful\Exception\ConnectionErrorException
     * @throws WrongInputExceptionArray
     */
    public function createAliasForImage($imageId, Request $request, ImageAliasApi $imageAliasApi)
    {
        $image = $this->getDoctrine()->getRepository(Image::class)->find($imageId);

        if (!$image) {
            throw new ElementNotFoundException(
                'No Image for ID ' . $imageId . ' found'
            );
        }

        if (!$image->isFinished()) {
            throw new WrongInputException('ImageAlias creation is only supported for Images where the creation process is finished');
        }

        $imageAlias = new ImageAlias();

        if ($request->request->has('name')) {
            $imageAlias->setName($request->request->get('name'));
        }
        if ($request->request->has('description')) {
            $imageAlias->setDescription($request->request->get('description'));
        }

        $this->validation($imageAlias);

        $result = $imageAliasApi->createAliasForImageByFingerprint($image->getHost(), $imageAlias, $image->getFingerprint());

        if ($result->code != 201 || $result->body->status_code != 200) {
            throw new WrongInputException('LXD-Error - ' . $result->body->error);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($imageAlias);

        $image->addAlias($imageAlias);
        $em->persist($image);

        $em->flush();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($image, 'json');
        return new Response($response, Response::HTTP_CREATED);
    }

    /**
     * Delete a single ImageAlias by its id
     *
     * @Route("/images/aliases/{aliasId}", name="delete_alias_for_image", methods={"DELETE"})
     *
     * @throws ElementNotFoundException
     * @throws WrongInputException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function deleteImageAlias($aliasId, ImageAliasApi $imageAliasApi)
    {
        $imageAlias = $this->getDoctrine()->getRepository(ImageAlias::class)->find($aliasId);

        if (!$imageAlias) {
            throw new ElementNotFoundException(
                'No ImageAlias for ID ' . $aliasId . ' found'
            );
        }

        $image = $imageAlias->getImage();

        if (!$image->isFinished()) {
            throw new WrongInputException('Deleting of the ImageAlias for an Image which is in the creation process is not possible');
        }

        $result = $imageAliasApi->removeAliasByName($image->getHost(), $imageAlias->getName());

        if ($result->code != 200 || $result->body->status_code != 200) {
            throw new WrongInputException('LXD-Error - ' . $result->body->error);
        }

        $image->removeAlias($imageAlias);

        $em = $this->getDoctrine()->getManager();
        $em->persist($image);
        $em->remove($imageAlias);

        $em->flush();

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * Change the description or name of an ImageAlias
     *
     * @Route("/images/aliases/{aliasId}", name="edit_alias_for_image", methods={"PATCH"})
     *
     * @param $aliasId
     * @param ImageAliasApi $imageAliasApi
     * @return Response
     * @throws ElementNotFoundException
     * @throws WrongInputException
     * @throws \Httpful\Exception\ConnectionErrorException
     * @throws WrongInputExceptionArray
     */
    public function editImageAlias($aliasId, Request $request, ImageAliasApi $imageAliasApi)
    {
        $imageAlias = $this->getDoctrine()->getRepository(ImageAlias::class)->find($aliasId);

        if (!$imageAlias) {
            throw new ElementNotFoundException(
                'No ImageAlias for ID ' . $aliasId . ' found'
            );
        }

        $image = $imageAlias->getImage();

        if (!$image->isFinished()) {
            throw new WrongInputException('Editing of the ImageAlias for an Image which is in the creation process is not possible');
        }

        $previousName = null;
        if ($request->request->has('name')) {
            $previousName = $imageAlias->getName();
            $imageAlias->setName($request->request->get('name'));
        }

        $previousDescription = null;
        if ($request->request->has('description')) {
            $previousDescription = $imageAlias->getDescription();
            $imageAlias->setDescription($request->request->get('description'));
        }

        //Validation
        if ($errorArray = $this->validation($imageAlias)) {
            throw new WrongInputExceptionArray($errorArray);
        }

        //Check if a name update via LXD is necessary
        if ($previousName != null && $previousName != $imageAlias->getName()) {
            $result = $imageAliasApi->editAliasName($image->getHost(), $imageAlias, $previousName);
            if ($result->code != 201 || $result->body->status_code != 200) {
                throw new WrongInputException('LXD-Error - ' . $result->body->error);
            }
        }

        //Check if a description update via LXD is necessary
        if ($previousDescription != null && $previousDescription != $imageAlias->getDescription()) {
            $result = $imageAliasApi->editAliasDescription($image->getHost(), $imageAlias);
            if ($result->code != 200 || $result->body->status_code != 200) {
                throw new WrongInputException('LXD-Error - ' . $result->body->error);
            }
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($imageAlias);
        $em->flush();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($imageAlias, 'json');
        return new Response($response);
    }

}
