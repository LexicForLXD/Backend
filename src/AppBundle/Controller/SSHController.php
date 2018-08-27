<?php

namespace AppBundle\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Swagger\Annotations as OAS;
use AppBundle\Exception\WrongInputException;


class SSHController extends BaseController
{
    /**
     * Returns the public SSH key for copying.
     *
     * @Route("/ssh/pub", name="show_ssh_pub", methods={"GET"})
     * @return JsonResponse
     */
    public function showSSHPub()
    {
        if (!$container->hasParameter('ssh_location')) {
            throw new WrongInputException("No SSH pub key set.");
        }
        $sshLoc = $container->getParameter('ssh_location');

        if (!$sshPub = file_get_contents($sshLoc)) {
            throw new WrongInputException("Couldn't read the SSH pub key.");
        }

        return new JsonResponse([
            "sshPubKey" => $sshPub
        ]);
    }
}