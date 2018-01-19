<?php
/**
 * Created by PhpStorm.
 * User: Leon
 * Date: 09.11.2017
 * Time: 21:07
 */

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use AppBundle\Entity\Container;

class ContainerRepository extends EntityRepository
{
    /**
     * returns one container associated with the host
     *
     * @param [int] $containerId
     * @return Container | null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByIdJoinedToHost($containerId)
    {
        $query = $this->getEntityManager()
            ->createQuery(
            'SELECT c, h FROM AppBundle:Container c
            JOIN c.host h
            WHERE c.id = :id'
        )->setParameter('id', $containerId);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * returns all Containers with host associated
     *
     * @return void
     */
    public function findAllJoinedToHost()
    {
        $query = $this->getEntityManager()
            ->createQuery(
            'SELECT c, h FROM AppBundle:Container c
            JOIN c.host h');

        try {
            return $query->getArrayResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    public function findAllByHostJoinedToHost($hostId)
    {
        $query = $this->getEntityManager()
        ->createQuery(
        'SELECT c, h FROM AppBundle:Container c
        JOIN c.host h
        WHERE h.id = :id'
        )->setParameter('id', $hostId);

        try {
            return $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}