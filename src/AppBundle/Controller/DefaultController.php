<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Swagger\Annotations as SWG;

class DefaultController extends BaseController
{
    /**
     * @Route("/", name="homepage")
     * SWG\Info(
     *     title="Lexic API",
     *     version="1.0"
     * )
     */
    public function indexAction()
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
        ]);
    }
}
