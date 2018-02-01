<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Image;
use AppBundle\Entity\ImageAlias;
use AppBundle\Exception\ElementNotFoundException;
use AppBundle\Exception\WrongInputException;
use AppBundle\Service\LxdApi\ImageAliasApi;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;

class ImageAliasController extends Controller
{
    /**
     * Create an ImageAlias for an existing Image
     *
     * @Route("/images/{imageId}/aliases", name="create_alias_for_image", methods={"POST"})
     * @throws ElementNotFoundException
     * @throws WrongInputException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function createAliasForImage($imageId, Request $request, ImageAliasApi $imageAliasApi)
    {
        $image = $this->getDoctrine()->getRepository(Image::class)->find($imageId);

        if (!$image) {
            throw new ElementNotFoundException(
                'No Image for ID ' . $imageId . ' found'
            );
        }

        if(!$image->isFinished()){
            throw new WrongInputException('ImageAlias creation is only supported for Images where the creation process is finished');
        }

        $imageAlias = new ImageAlias();

        if ($request->request->has('name')) {
            $imageAlias->setName($request->request->get('name'));
        }
        if ($request->request->has('description')) {
            $imageAlias->setDescription($request->request->get('description'));
        }

        if ($errorArray = $this->validation($imageAlias)) {
            return new JsonResponse(['errors' => $errorArray], 400);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($imageAlias);

        $image->addAlias($imageAlias);
        $em->persist($image);

        $em->flush();

        $imageAliasApi->createAliasForImageByFingerprint($image->getHost(), $imageAlias, $image->getFingerprint());

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($image, 'json');
        return new Response($response);
    }

    /**
     * Delete a single ImageAlias by its id
     *
     * @Route("/images/aliases/{aliasId}", name="delete_alias_for_image", methods={"DELETE"})
     * @throws ElementNotFoundException
     * @throws WrongInputException
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function deleteImageAlias($aliasId, ImageAliasApi $imageAliasApi){
        $imageAlias = $this->getDoctrine()->getRepository(ImageAlias::class)->find($aliasId);

        if (!$imageAlias) {
            throw new ElementNotFoundException(
                'No ImageAlias for ID ' . $aliasId . ' found'
            );
        }

        $image = $imageAlias->getImage();

        if(!$image->isFinished()){
            throw new WrongInputException('Deleting of the ImageAlias for an Image which is in the creation process is not possible');
        }

        $result = $imageAliasApi->removeAliasByName($image->getHost(), $imageAlias->getName());

        if($result->code != 200 || $result->body->status_code != 200){
            throw new WrongInputException('LXD-Error - '.$result->body->error);
        }

        $image->removeAlias($imageAlias);

        $em = $this->getDoctrine()->getManager();
        $em->persist($image);
        $em->remove($imageAlias);

        $em->flush();

        return new Response('', Response::HTTP_NO_CONTENT);
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
