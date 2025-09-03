<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    /**
     * Récupère les commandes entre deux dates
     */
    public function findCommandesBetween(\DateTimeInterface $start = null, \DateTimeInterface $end = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.produits', 'p')
            ->addSelect('p')
            ->join('p.tauxTva', 't')
            ->addSelect('t');

        if ($start && $end) {
            $qb->where('c.date BETWEEN :start AND :end')
                ->setParameter('start', $start)
                ->setParameter('end', $end);
        }

        $qb->orderBy('c.date', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
