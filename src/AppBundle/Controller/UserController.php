<?php
/**
 * Created by PhpStorm.
 * User: Leon
 * Date: 06.11.2017
 * Time: 19:50
 */

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Doctrine\ORM\EntityManagerInterface;

class UserController
{

    /**
     * Gibt eine Liste all
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
     */
    public function indexAction()
    {
        $users = $this->getDoctrine()->getRepository(User::class)->findAll();

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($users, 'json');
        return new Response($response);
    }
}