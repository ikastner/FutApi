<?php

namespace App\Repository;

use App\Entity\SoccerPlayers;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SoccerPlayers>
 */
class SoccerPlayersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SoccerPlayers::class);
    }

    /**
     * Récupère les joueurs filtrés par type et triés par rating.
     * 
     * @param string $type Le type de pack (Golder, Silver, Bronze)
     * 
     * @return SoccerPlayers[] Tableau des joueurs filtrés et triés.
     */
    public function getFilteredPlayersByType(string $type)
    {
        // Définir les types autorisés pour chaque type de pack
        $typeHierarchy = [
            'Icon' => ['Icon','Gold', 'Silver', 'Bronze'],
            'Gold' => ['Gold', 'Silver', 'Bronze'],
            'Silver' => ['Silver', 'Bronze'],
            'Bronze' => ['Bronze'],
        ];

        $queryBuilder = $this->createQueryBuilder('p')
            ->where('p.type IN (:types)')
            ->setParameter('types', $typeHierarchy[$type] ?? [])
            ->orderBy('p.rating', 'DESC'); // Trie par rating décroissant

        return $queryBuilder->getQuery()->getResult();
    }
}
