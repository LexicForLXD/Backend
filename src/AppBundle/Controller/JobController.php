<?php
namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Exception\ElementNotFoundException;
use Dtc\QueueBundle\Entity\Job;
use Dtc\QueueBundle\Entity\JobArchive;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as OAS;
use Doctrine\ORM\EntityManagerInterface;


class JobController extends BaseController
{


    /**
     * Returns all container jobs depending on the type.
     *
     * @Route("/jobs/containers", name="container_job_fetch", methods={"GET"})
     *
     * @OAS\Get(path="/jobs/containers",
     *     tags={"job"},
     *     @OAS\Parameter(
     *          description="Whether to show running or archived jobs. Default is running.",
     *          in="query",
     *          name="type",
     *          @OAS\Schema(
     *              type="string"
     *          ),
     *     ),
     *     @OAS\Response(
     *          response=202,
     *          description="all container jobs"
     *     ),
     * )
     * @param Request $request
     * @return Response
     * @throws ElementNotFoundException
     */
    public function indexContainerAction(Request $request)
    {

        $type = $request->query->get('type');

        switch ($type) {
            case "archived":
                $jobs = $this->getDoctrine()->getRepository(JobArchive::class)->findBy(["workerName" => "container"]);
                break;
            default:
                $jobs = $this->getDoctrine()->getRepository(Job::class)->findBy(["workerName" => "container"]);
                break;

        }

        if (!$jobs) {
            throw new ElementNotFoundException(
                'No jobs found.'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($jobs, 'json');
        return new Response($response);
    }


    /**
     * Returns all container state jobs depending on the type.
     *
     * @Route("/jobs/containers/state", name="container_state_job_fetch", methods={"GET"})
     *
     * @OAS\Get(path="/jobs/containers/state",
     *     tags={"job"},
     *     @OAS\Parameter(
     *          description="Whether to show running or archived jobs. Default is running.",
     *          in="query",
     *          name="type",
     *          @OAS\Schema(
     *              type="string"
     *          ),
     *     ),
     *     @OAS\Response(
     *          response=202,
     *          description="all container state jobs"
     *     ),
     * )
     * @param Request $request
     * @return Response
     * @throws ElementNotFoundException
     */
    public function indexContainerStateAction(Request $request)
    {

        $type = $request->query->get('type');

        switch ($type) {
            case "archived":
                $jobs = $this->getDoctrine()->getRepository(JobArchive::class)->findBy(["workerName" => "containerState"]);
                break;
            default:
                $jobs = $this->getDoctrine()->getRepository(Job::class)->findBy(["workerName" => "containerState"]);
                break;

        }

        if (!$jobs) {
            throw new ElementNotFoundException(
                'No jobs found.'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($jobs, 'json');
        return new Response($response);
    }

    /**
     * Returns all import jobs depending on the type.
     *
     * @Route("/jobs/import", name="import_fetch", methods={"GET"})
     *
     * @OAS\Get(path="/jobs/import",
     *     tags={"job"},
     *     @OAS\Parameter(
     *          description="Whether to show running or archived jobs. Default is running.",
     *          in="query",
     *          name="type",
     *          @OAS\Schema(
     *              type="string"
     *          ),
     *     ),
     *     @OAS\Response(
     *          response=202,
     *          description="all import jobs"
     *     ),
     * )
     * @param Request $request
     * @return Response
     * @throws ElementNotFoundException
     */
    public function indexImportAction(Request $request)
    {

        $type = $request->query->get('type');

        switch ($type) {
            case "archived":
                $jobs = $this->getDoctrine()->getRepository(JobArchive::class)->findBy(["workerName" => "import"]);
                break;
            default:
                $jobs = $this->getDoctrine()->getRepository(Job::class)->findBy(["workerName" => "import"]);
                break;

        }

        if (!$jobs) {
            throw new ElementNotFoundException(
                'No jobs found.'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($jobs, 'json');
        return new Response($response);
    }


    /**
     * Returns all backup jobs depending on the type.
     *
     * @Route("/jobs/backup", name="backup_job_fetch", methods={"GET"})
     *
     * @OAS\Get(path="/jobs/backup",
     *     tags={"job"},
     *     @OAS\Parameter(
     *          description="Whether to show running or archived jobs. Default is running.",
     *          in="query",
     *          name="type",
     *          @OAS\Schema(
     *              type="string"
     *          ),
     *     ),
     *     @OAS\Response(
     *          response=202,
     *          description="all backup jobs"
     *     ),
     * )
     * @param Request $request
     * @return Response
     * @throws ElementNotFoundException
     */
    public function indexBackupAction(Request $request)
    {

        $type = $request->query->get('type');

        switch ($type) {
            case "archived":
                $jobs = $this->getDoctrine()->getRepository(JobArchive::class)->findBy(["workerName" => "backup"]);
                break;
            default:
                $jobs = $this->getDoctrine()->getRepository(Job::class)->findBy(["workerName" => "backup"]);
                break;

        }

        if (!$jobs) {
            throw new ElementNotFoundException(
                'No jobs found.'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($jobs, 'json');
        return new Response($response);
    }


    /**
     * Returns all image jobs depending on the type.
     *
     * @Route("/jobs/images", name="image_job_fetch", methods={"GET"})
     *
     * @OAS\Get(path="/jobs/images",
     *     tags={"job"},
     *     @OAS\Parameter(
     *          description="Whether to show running or archived jobs. Default is running.",
     *          in="query",
     *          name="type",
     *          @OAS\Schema(
     *              type="string"
     *          ),
     *     ),
     *     @OAS\Response(
     *          response=202,
     *          description="all image jobs"
     *     ),
     * )
     * @param Request $request
     * @return Response
     * @throws ElementNotFoundException
     */
    public function indexImageAction(Request $request)
    {

        $type = $request->query->get('type');

        switch ($type) {
            case "archived":
                $jobs = $this->getDoctrine()->getRepository(JobArchive::class)->findBy(["workerName" => "image"]);
                break;
            default:
                $jobs = $this->getDoctrine()->getRepository(Job::class)->findBy(["workerName" => "image"]);
                break;

        }

        if (!$jobs) {
            throw new ElementNotFoundException(
                'No jobs found.'
            );
        }

        $serializer = $this->get('jms_serializer');
        $response = $serializer->serialize($jobs, 'json');
        return new Response($response);
    }


    /**
     * Delete a job
     *
     * @Route("/jobs/{jobId}", name="job_delete", methods={"DELETE"})
     * 
     * @param Request $request
     * @param int $jobId
     * @param EntityManagerInterface $em
     * @return json
     *
     * 
     * @OAS\Delete(path="/jobs/{jobId}",
     *  tags={"jobs"},
     * @OAS\Parameter(
     *     description="ID von zu löschendem job",
     *     in="path",
     *     name="jobId",
     *     required=true,
     *     @OAS\Schema(
     *         type="integer"
     *     ),
     *  ),
     * @OAS\Parameter(
     *          description="Whether to delete a running or archived job. Default is running.",
     *          in="query",
     *          name="type",
     *          @OAS\Schema(
     *              type="string"
     *          ),
     *     ),
     *
     * @OAS\Response(
     *     response=204,
     *     description="delete completed"
     *  ),
     * )
     */
    public function deleteJob(Request $request, int $jobId, EntityManagerInterface $em)
    {

        $type = $request->query->get('type');

        switch ($type) {
            case "archived":
                $job = $this->getDoctrine()->getRepository(JobArchive::class)->find($jobId);
                break;
            default:
                $job = $this->getDoctrine()->getRepository(Job::class)->find($jobId);
                break;

        }


        if (!$job) {
            throw new ElementNotFoundException(
                'Job not found.'
            );
        }

        $em->remove($job);
        $em->flush();

        return $this->json([], 204);
    }
}