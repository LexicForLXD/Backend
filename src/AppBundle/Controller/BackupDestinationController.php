<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Swagger\Annotations as OAS;

use AppBundle\Exception\WrongInputException;
use AppBundle\Exception\WrongInputExceptionArray;

use AppBundle\Entity\BackupDestination;
use AppBundle\Entity\Backup;


class BackupDestinationController extends Controller
{
    /**
     * List all backup destinations
     *
     * @Route("/backupdestinations", name="backup_dest_index", methods="{GET"})
     *
     * @OAS\Get(path="/backupdestinations",
     *      tags={"backupdestinations"},
     *      @OAS\Response(
     *          response=200,
     *          description="Zeigt eine Liste aller backup destinations an",
     *          @OAS\JsonContent(ref="#/components/schemas/backupdest"),
     *          @OAS\Schema(
     *              type="array"
     *          ),
     *      ),
     *     @OAS\Response(
     *          response=404,
     *          description="No backup destinations found",
     *      ),
     * )
     *
     * @return Response
     * @throws ElementNotFoundException
     */
    public function indexBackupDestinationAction()
    {
        $dests = $this->getDoctrine()->getRepository(BackupDestination::class)->findAll();

        if (!$dests) {
            throw new ElementNotFoundException(
                'No backup destinations found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($dests, 'json');
        return new Response($response);
    }


    /**
     * Show one backup destination
     *
     * @Route("/backupdestinations/{destId}", name="backup_dest_show", methods={"GET"})
     *
     * @OAS\Get(path="/backupdestinations/{destId}",
     *  tags={"backupdestinations"},
     *  @OAS\Parameter(
     *     description="ID von anzuzeigender backup destination",
     *     in="path",
     *     name="destId",
     *     required=true,
     *     @OAS\Schema(
     *         type="integer"
     *     ),
     *  ),
     *
     *  @OAS\Response(
     *      response=200,
     *      description="gibt eine backup destination zurück",
     *      @OAS\JsonContent(ref="#/components/schemas/backupdest"),
     *      @OAS\Schema(
     *          type="array"
     *      ),
     *  ),
     *  @OAS\Response(
     *      response=404,
     *      description="No backup destination found",
     *  ),
     * )
     *
     * @param integer $destId
     * @return Response
     * @throws ElementNotFoundException
     */
    public function showBackupDestinationAction(int $destId)
    {
        $dest = $this->getDoctrine()->getRepository(BackupDestination::class)->find($destId);

        if (!$dest) {
            throw new ElementNotFoundException(
                'No backup destination found for id ' . $destId
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($dest, 'json');
        return new Response($response);
    }


    /**
     * Creates a new backup destination
     *
     * @Route("/backupdestinations", name="backup_dest_create", methods={"POST"})
     *
     * @OAS\Post(path="/backupdestinations",
     *  tags={"backupdestinations"},
     *  @OAS\Response(
     *     response=201,
     *     description="gibt die neu gespeicherte backup destination zurück",
     *     @OAS\JsonContent(ref="#/components/schemas/backupdest"),
     *     @OAS\Schema(
     *         type="array"
     *     ),
     *  ),
     *
     *  @OAS\Response(
     *     response=400,
     *     description="wrong input data with array what was wrong",
     *  ),
     *
     *  @OAS\Parameter(
     *      description="Parameters for new backup dest without username and password",
     *      name="body",
     *      in="body",
     *      required=true,
     *      @OAS\Schema(
     *          @OAS\Property(
     *              property="name",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="description",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="hostname",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="protocol",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="path",
     *              type="string"
     *          ),
     *      ),
     *  ),
     *
     *  @OAS\Parameter(
     *      description="Parameters for new backup dest with username and password",
     *      name="body",
     *      in="body",
     *      required=true,
     *      @OAS\Schema(
     *          @OAS\Property(
     *              property="name",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="description",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="hostname",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="protocol",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="path",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="username",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="password",
     *              type="string"
     *          ),
     *      ),
     *  ),
     * )
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response
     * @throws WrongInputException
     */
    public function createBackupDestinationAction(Request $request, EntityManagerInterface $em)
    {
        $dest = new BackupDestination();
        $dest->setName($request->get('name'));
        $dest->setDescription($request->get('description'));
        $dest->setHostname($request->get('hostname'));
        $dest->setProtocol($request->get('protocol'));
        $dest->setPath($request->get('path'));

        if ($request->request->has("username")) {
            $dest->setUsername($request->get('username'));
        }
        if ($request->request->has("password")) {
            $dest->setPassword($request->get('password'));
        }

        $this->validation($dest);

        $em->persist($dest);
        $em->flush();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($dest, 'json');
        return new Response($response, Response::HTTP_CREATED);

    }


    /**
     * Update a single backup destination
     *
     * @Route("/backupdestinations/{destId}", name="backup_dest_update", methods={"PUT"})
     *
     * @OAS\Put(path="/backupdestinations/{destId}",
     *  tags={"backupdestinations"},
     *  @OAS\Response(
     *     response=200,
     *     description="gibt die bearbeitete backup destination zurück",
     *     @OAS\JsonContent(ref="#/components/schemas/backupdest"),
     *     @OAS\Schema(
     *         type="array"
     *     ),
     *  ),
     *
     *  @OAS\Response(
     *     response=400,
     *     description="wrong input data with array what was wrong",
     *  ),
     *
     *  @OAS\Response(
     *      response=404,
     *      description="No backup destination found",
     *  ),
     *
     *  @OAS\Parameter(
     *      description="Parameters for update backup dest without username and password",
     *      name="bodyWithoutUser",
     *      in="body",
     *      required=true,
     *      @OAS\Schema(
     *          @OAS\Property(
     *              property="name",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="description",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="hostname",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="protocol",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="path",
     *              type="string"
     *          ),
     *      ),
     *  ),
     *
     *  @OAS\Parameter(
     *      description="Parameters for update backup dest with username and password",
     *      name="body",
     *      in="body",
     *      required=true,
     *      @OAS\Schema(
     *          @OAS\Property(
     *              property="name",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="description",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="hostname",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="protocol",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="path",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="username",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="password",
     *              type="string"
     *          ),
     *      ),
     *  ),
     * )
     *
     * @param Request $request
     * @param integer $destId
     * @param EntityManagerInterface $em
     * @return Response
     * @throws ElementNotFoundException
     * @throws WrongInputException
     */
    public function updateBackupDestinationAction(Request $request, int $destId, EntityManagerInterface $em)
    {
        $dest = $this->getDoctrine()->getRepository(BackupDestination::class)->find($destId);

        if (!$dest) {
            throw new ElementNotFoundException(
                'No backup destination found for id ' . $destId
            );
        }

        $dest->setName($request->get('name'));
        $dest->setDescription($request->get('description'));
        $dest->setHostname($request->get('hostname'));
        $dest->setProtocol($request->get('protocol'));
        $dest->setPath($request->get('path'));

        if ($request->request->has("username")) {
            $dest->setUsername($request->get('username'));
        }
        if ($request->request->has("password")) {
            $dest->setPassword($request->get('password'));
        }

        $this->validation($dest);

        $em->flush();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($dest, 'json');
        return new Response($response);
    }


    /**
     * Delete one backup destination
     *
     * @Route("/backupdestinations/{destId}", name="backup_dest_delete", methods="DELETE")
     *
     * @OAS\Delete(path="/backupdestinations/{destId}",
     *  tags={"backupdestinations"},
     *  @OAS\Parameter(
     *     description="ID der zu löschenden backup destination",
     *     in="path",
     *     name="destId",
     *     required=true,
     *     @OAS\Schema(
     *         type="integer"
     *     ),
     *  ),
     *
     *  @OAS\Response(
     *     response=204,
     *     description="success message"
     *  ),
     *
     *  @OAS\Response(
     *      response=404,
     *      description="No backup destination found",
     *  ),
     * )
     *
     * @param integer $destId
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws ElementNotFoundException
     */
    public function deleteBackupDestinationAction(int $destId, EntityManagerInterface $em)
    {
        $dest = $this->getDoctrine()->getRepository(BackupDestination::class)->find($destId);

        if (!$dest) {
            throw new ElementNotFoundException(
                'No backup destination found for id ' . $destId
            );
        }

        $em->remove($dest);
        $em->flush();

        return JsonResponse(['message' => 'successful deleted'], 204);
    }



    /**
     * Validates a BackupDestination Object and returns array with errors.
     *
     *
     *
     * @param BackupDestination $object
     * @return array|bool
     * @throws WrongInputException
     */
    private function validation(BackupDestination $object)
    {
        $validator = $this->get('validator');
        $errors = $validator->validate($object);

        if (count($errors) > 0) {
            $errorArray = array();
            foreach ($errors as $error) {
                $errorArray[$error->getPropertyPath()] = $error->getMessage();
            }
            throw new WrongInputExceptionArray($errorArray);
            return $errorArray;

        }
        return false;
    }
}