<?php
// src/Repository/OperationRepository.php

namespace App\Repository;

use App\Entity\Operation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DateTime;
use PhpParser\Node\Expr\Empty_;

class OperationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Operation::class);
    }

    /**
     * Cette méthode calcule la somme des montants des opérations d'un expéditeur dans le mois
     * et retourne cette somme.
     */
    public function getMontantTotalDuMois(string $numero_cni_expediteur)
    {
        $dateDebutMois = new DateTime('first day of this month');
        $dateFinMois = new DateTime('now');

        $queryBuilder = $this->createQueryBuilder('o')
            ->select('SUM(o.montant) as totalMontant')
            ->where('o.numero_cni_expediteur = :numero_cni_expediteur')
            ->andWhere('o.created_at >= :dateDebutMois')
            ->andWhere('o.created_at <= :dateFinMois')
            ->setParameter('numero_cni_expediteur', $numero_cni_expediteur)
            ->setParameter('dateDebutMois', $dateDebutMois)
            ->setParameter('dateFinMois', $dateFinMois);

        $result = $queryBuilder->getQuery()->getSingleScalarResult();

        // Retourne la somme des montants, ou 0 si aucune opération n'a été trouvée
        if (empty($result)) {
            # code...
            return 0;
        }
        return $result ;
    }
}
