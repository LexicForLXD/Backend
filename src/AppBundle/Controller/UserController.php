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
use AppBundle\Exception\WrongInputException;
use AppBundle\Exception\WrongInputExceptionArray;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as OAS;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserController extends BaseController
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
    public function showAction($userId)
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
     * @param UserPasswordEncoderInterface $encoder
     * @return JsonResponse|Response
     * @throws WrongInputExceptionArray
     */
    public function storeAction(Request $request, EntityManagerInterface $em, UserPasswordEncoderInterface $encoder)
    {
        $user = new User();

        $user->setEmail($request->request->get('email'));
        $user->setFirstName($request->request->get('firstName'));
        $user->setLastName($request->request->get('lastName'));
        $user->setUsername($request->request->get('username'));

        if ($request->request->has('password')) {
            $user->setPassword($encoder->encodePassword($user, $request->request->get('password')));
        }

        $this->validation($user);

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
     * @param UserPasswordEncoderInterface $encoder
     * @return JsonResponse|Response
     * @throws ElementNotFoundException
     * @throws WrongInputException
     * @throws WrongInputExceptionArray
     */
    public function updateAction(Request $request, $userId, EntityManagerInterface $em, UserPasswordEncoderInterface $encoder)
    {
        $user = $this->getDoctrine()->getRepository(User::class)->find($userId);

        if (!$user) {
            throw new ElementNotFoundException(
                'No User found'
            );
        }
        $this->checkRequestFields($request, ["firstName", "lastName", "email", "username"]);

        $user->setEmail($request->request->get('email'));
        $user->setFirstName($request->request->get('firstName'));
        $user->setLastName($request->request->get('lastName'));
        $user->setUsername($request->request->get('username'));

        if ($request->request->has("password")) {
            $user->setPassword($encoder->encodePassword($user, $request->request->get('password')));
        }

        if ($request->request->has("isActive")) {
            $user->setIsActive($request->get("isActive"));
        }

        $this->validation($user);

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


    /**
     * Gets current authenticated user.
     *
     * @Route("/user", name="user_current", methods={"GET"})
     *
     * @OAS\Get(path="/user",
     *  tags={"users"},
     *  @OAS\Response(
     *      response=200,
     *      description="current authenticated user",
     *      @OAS\JsonContent(ref="#/components/schemas/user"),
     *  ),
     *
     * )
     *
     * @return Response
     */
    public function currentUserAction()
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($user, 'json');
        return new Response($response);
    }

    /**
     * Checks whether the given fields are in the request
     *
     * @param Request $request
     * @param array $fieldNames
     * @throws WrongInputException
     * @return bool
     */
    private function checkRequestFields(Request $request, array $fieldNames)
    {

        foreach ($fieldNames as $fieldName) {
            if (!$request->request->has($fieldName)) {
                throw new WrongInputException("You have to include the " . $fieldName . " field.");
            }
        }

        return true;
    }


}