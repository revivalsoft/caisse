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
    public function findCommandesBetween(\DateTimeImmutable $start = null, \DateTimeImmutable $end = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.lignes', 'l') // si tu passes sur CommandeLigne
            ->addSelect('l');

        if ($start && $end) {
            $qb->where('c.date BETWEEN :start AND :end')
                ->setParameter('start', $start)
                ->setParameter('end', $end);
        }

        $qb->orderBy('c.date', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
