<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Backup;
use AppBundle\Exception\ElementNotFoundException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Swagger\Annotations as OAS;
use Symfony\Component\HttpFoundation\Response;

class BackupController extends Controller
{
    /**
     * Get all successful Backups
     *
     * @Route("/backups", name="backups_all", methods={"GET"})
     *
     * @return Response
     * @throws ElementNotFoundException
     *
     * @OAS\Get(path="/backups",
     *     tags={"backups"},
     *      @OAS\Response(
     *          response=200,
     *          description="List of all successful Backups",
     *          @OAS\JsonContent(ref="#/components/schemas/backup"),
     *          @OAS\Schema(
     *              type="array"
     *          ),
     *      ),
     *      @OAS\Response(
     *          response=404,
     *          description="No Backups found",
     *      ),
     * )
     */
    public function getAllBackups()
    {
        $backups = $this->getDoctrine()->getRepository(Backup::class)->findAll();

        if (!$backups) {
            throw new ElementNotFoundException(
                'No Backups found'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($backups, 'json');
        return new Response($response);
    }
}
