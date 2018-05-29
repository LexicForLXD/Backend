<?php
/**
 * Created by IntelliJ IDEA.
 * User: leon
 * Date: 28.05.18
 * Time: 02:36
 */

namespace AppBundle\Controller;

use FOS\OAuthServerBundle\Controller\TokenController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;



class LoginController extends BaseController
{

    /**
     * @Route("/login", name="login_proxy", methods={"POST"})
     *
     * @param Request $request
     * @param TokenController $tokenController
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginAction(Request $request, TokenController $tokenController)
    {
        $oauthRequest = Request::create('/oauth/v2/token', 'POST', [
            "grant_type" => "password",
            "client_id" => $this->getParameter('client_id'),
            "client_secret" => $this->getParameter('client_secret'),
            "username" => $request->get("username"),
            "password" => $request->get("password")
        ], $request->cookies->all(), $request->files->all(), $request->server->all(), $request->getContent());

        return $tokenController->tokenAction($oauthRequest);
    }


    /**
     * @Route("/refresh", name="refresh_proxy", methods={"POST"})
     *
     * @param Request $request
     * @param TokenController $tokenController
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function refreshAction(Request $request ,TokenController $tokenController)
    {
        $oauthRequest = Request::create('/oauth/v2/token', 'POST', [
            "grant_type" => "refresh_token",
            "client_id" => $this->getParameter('client_id'),
            "client_secret" => $this->getParameter('client_secret'),
            "refresh_token" => $request->get("refreshToken")
        ], $request->cookies->all(), $request->files->all(), $request->server->all(), $request->getContent());

        return $tokenController->tokenAction($oauthRequest);
    }
}