<?php
/**
 * Created by PhpStorm.
 * User: Leon
 * Date: 06.11.2017
 * Time: 19:50
 */

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Exception\ElementNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{

    /**
     * Shows all users
     *
     * @Route("/users", name="users_all", methods={"GET"})
     *
     * @OAS\Get(path="/users",
     *     tags={"users"},
     *      @OAS\Response(
     *          response=200,
     *          description="List of all users",
     *          @OAS\JsonContent(ref="#/components/schemas/user"),
     *          @OAS\Schema(
     *              type="array"
     *          ),
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No users found",
     *      ),
     * )
     *
     * @return Response
     * @throws ElementNotFoundException
     */
    public function indexAction()
    {
        $users = $this->getDoctrine()->getRepository(User::class)->findAll();

        if (!$users) {
            throw new ElementNotFoundException(
                'No Users found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($users, 'json');
        return new Response($response);
    }

    /**
     * Shows single user
     *
     * @Route("/users/{userId}", name="users_single", methods={"GET"})
     *
     * @OAS\Get(path="/users/{userId}",
     *  tags={"users"},
     *  @OAS\Response(
     *      response=200,
     *      description="Single user",
     *      @OAS\JsonContent(ref="#/components/schemas/user"),
     *  ),
     *
     *  @OAS\Parameter(
     *      description="ID of the User",
     *      in="path",
     *      name="userId",
     *      required=true,
     *      @OAS\Schema(
     *          type="integer"
     *      ),
     *  )
     * )
     *
     * @param int $userId
     * @return Response
     * @throws ElementNotFoundException
     */
    public function showAction(integer $userId)
    {
        $user = $this->getDoctrine()->getRepository(User::class)->find($userId);

        if (!$user) {
            throw new ElementNotFoundException(
                'No User found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($user, 'json');
        return new Response($response);
    }

    /**
     * Stores a new User
     *
     * @Route("/users", name="users_store", methods={"POST"})
     *
     * @OAS\POST(path="/users",
     *  tags={"users"},
     *  @OAS\Response(
     *     response=201,
     *     description="gibt den neu gespeicherten User zurück",
     *     @OAS\JsonContent(ref="#/components/schemas/user"),
     *     @OAS\Schema(
     *         type="array"
     *     ),
     *  ),
     *
     *  @OAS\Parameter(
     *      description="Parameters for new User",
     *      name="body",
     *      in="body",
     *      required=true,
     *      @OAS\Schema(
     *          @OAS\Property(
     *              property="firstName",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="lastName",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="username",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="email",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="password",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="roles",
     *              type="array"
     *          ),
     *      ),
     *  ),
     * )
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return JsonResponse|Response
     */
    public function storeAction(Request $request, EntityManagerInterface $em)
    {
        $encoder = $this->container->get('security.password_encoder');

        $user = new User();
        if($request->request->has("email")) {
            $user->setEmail($request->request->get('email'));
        }
        if($request->request->has("firstName")) {
            $user->setFirstName($request->request->get('firstName'));
        }
        if($request->request->has("lastName")) {
            $user->setLastName($request->request->get('lastName'));
        }
        if($request->request->has("password")) {
            $user->setPassword($encoder->encodePassword($user, $request->request->get('password')));
        }
        if($request->request->has("username")) {
            $user->setUsername($request->request->get('username'));
        }


        if ($errorArray = $this->validation($user)) {
            return new JsonResponse(['errors' => $errorArray], 400);
        }

        $em->persist($user);
        $em->flush();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($user, 'json');
        return new Response($response);
    }


    /**
     * Updates existing User
     *
     * @Route("/users/{userId}", name="users_update", methods={"PUT"})
     *
     * @OAS\PUT(path="/users",
     *  tags={"users"},
     *  @OAS\Response(
     *     response=200,
     *     description="gibt den neu gespeicherten User zurück",
     *     @OAS\JsonContent(ref="#/components/schemas/user"),
     *     @OAS\Schema(
     *         type="array"
     *     ),
     *  ),
     *
     *  @OAS\Parameter(
     *     description="ID von upzudatendem User",
     *     in="path",
     *     name="userId",
     *     required=true,
     *     @OAS\Schema(
     *         type="integer"
     *     ),
     *  ),
     *
     *  @OAS\Parameter(
     *      description="Parameters for updated User",
     *      name="body",
     *      in="body",
     *      required=true,
     *      @OAS\Schema(
     *          @OAS\Property(
     *              property="firstName",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="lastName",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="username",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="email",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="password",
     *              type="string"
     *          ),
     *          @OAS\Property(
     *              property="roles",
     *              type="array"
     *          ),
     *      ),
     *  ),
     * )
     *
     * @param Request $request
     * @param $userId
     * @param EntityManagerInterface $em
     * @return JsonResponse|Response
     * @throws ElementNotFoundException
     */
    public function updateAction(Request $request, $userId, EntityManagerInterface $em)
    {
        $user = $this->getDoctrine()->getRepository(User::class)->find($userId);

        if (!$user) {
            throw new ElementNotFoundException(
                'No User found'
            );
        }

        $encoder = $this->container->get('security.password_encoder');

        if($request->request->has("email")) {
            $user->setEmail($request->request->get('email'));
        }
        if($request->request->has("firstName")) {
            $user->setFirstName($request->request->get('firstName'));
        }
        if($request->request->has("lastName")) {
            $user->setLastName($request->request->get('lastName'));
        }
        if($request->request->has("password")) {
            $user->setPassword($encoder->encodePassword($user, $request->request->get('password')));
        }
        if($request->request->has("username")) {
            $user->setUsername($request->request->get('username'));
        }


        if ($errorArray = $this->validation($user)) {
            return new JsonResponse(['errors' => $errorArray], 400);
        }

        $em->flush($user);

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($user, 'json');
        return new Response($response);
    }


    /**
     * Delete a existing User
     *
     * @Route("/users/{userId}", name="users_delete", methods={"DELETE"})
     *
     * @OAS\Delete(path="/users/{userId}",
     *  tags={"users"},
     *  @OAS\Parameter(
     *     description="ID von zu löschendem User",
     *     in="path",
     *     name="userId",
     *     required=true,
     *     @OAS\Schema(
     *         type="integer"
     *     ),
     *  ),
     *
     *  @OAS\Response(
     *     response=204,
     *     description="löscht einen User"
     *  ),
     * )
     *
     * @param $userId
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws ElementNotFoundException
     */
    public function deleteAction($userId, EntityManagerInterface $em)
    {
        $user = $this->getDoctrine()->getRepository(User::class)->find($userId);

        if (!$user) {
            throw new ElementNotFoundException(
                'No User found'
            );
        }

        $em->remove($user);
        $em->flush();

        return $this->json([], 204);
    }
}