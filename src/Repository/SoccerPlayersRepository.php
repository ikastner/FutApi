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
//    public function getFilteredPlayersByType(string $type)
//    {
//        // Définir les types autorisés pour chaque type de pack
//        $typeHierarchy = [
//            'Icon' => ['Icon','Gold', 'Silver', 'Bronze'],
//            'Gold' => ['Gold', 'Silver', 'Bronze'],
//            'Silver' => ['Silver', 'Bronze'],
//            'Bronze' => ['Bronze'],
//        ];
//
//        $queryBuilder = $this->createQueryBuilder('p')
//            ->where('p.type IN (:types)')
//            ->setParameter('types', $typeHierarchy[$type] ?? [])
//            ->orderBy('p.rating', 'DESC'); // Trie par rating décroissant
//
//        return $queryBuilder->getQuery()->getResult();
//    }
//
//    public function selectWeightedRandom(array $players, int $count): array
//    {
//        // Calculer la somme totale des probabilités
//        $totalProbability = array_sum(array_map(function ($player) {
//            return $player->getRate();
//        }, $players));
//
//        // Vérifier si la somme des probabilités est valide
//        if ($totalProbability == 0) {
//            throw new \Exception('Total probability is zero, cannot select players.');
//        }
//
//        $selectedPlayers = [];
//        $selectedIndices = [];
//
//        for ($i = 0; $i < $count; $i++) {
//            $random = mt_rand() / mt_getrandmax() * $totalProbability;
//            $sum = 0;
//
//            foreach ($players as $index => $player) {
//                // Ignorer les joueurs déjà sélectionnés
//                if (in_array($index, $selectedIndices)) {
//                    continue;
//                }
//
//                $sum += $player->getRate();
//                if ($random <= $sum) {
//                    $selectedPlayers[] = $player;
//                    $selectedIndices[] = $index;
//                    break;
//                }
//            }
//        }
//
//        // Vérifier si le nombre de joueurs sélectionnés est suffisant
//        if (count($selectedPlayers) < $count) {
//            throw new \Exception('Not enough players available to fill the pack.');
//        }
//
//        // Trier les joueurs sélectionnés par rating décroissant
//        usort($selectedPlayers, function ($a, $b) {
//            return $b->getRating() <=> $a->getRating();
//        });
//
//        return $selectedPlayers;
//    }
    public function selectWeightedRandom(string $type, int $count): array
    {

        // Définir les types autorisés pour chaque type de pack
        $typeHierarchy = [
            'Icon' => ['Icon','Gold','Silver' ],
            'Gold' => ['Gold', 'Silver', 'Bronze'],
            'Silver' => ['Silver', 'Bronze'],
            'Bronze' => ['Bronze'],
        ];

        // Récupérer les joueurs filtrés par type
        $players = $this->createQueryBuilder('p')
            ->where('p.type IN (:types)')
            ->setParameter('types', $typeHierarchy[$type] ?? [])
            ->orderBy('p.rating', 'DESC')
            ->getQuery()
            ->getResult();

        // Calculer la somme totale des probabilités
        $totalProbability = array_sum(array_map(function ($player) {
            return $player->getRate();
        }, $players));

        // Vérifier si la somme des probabilités est valide
        if ($totalProbability == 0) {
            throw new \Exception('Total probability is zero, cannot select players.');
        }

        $selectedPlayers = [];
        $selectedIndices = [];

        for ($i = 0; $i < $count; $i++) {
            $random = mt_rand() / mt_getrandmax() * $totalProbability;
            $sum = 0;

            foreach ($players as $index => $player) {
                // Ignorer les joueurs déjà sélectionnés
//                if (in_array($index, $selectedIndices)) {
//                    continue;
//                }

                $sum += $player->getRate();
                if ($random <= $sum) {
                    $selectedPlayers[] = $player;
                    $selectedIndices[] = $index;
                    break;
                }
            }
        }

        // Vérifier si le nombre de joueurs sélectionnés est suffisant
        if (count($selectedPlayers) < $count) {
            //var_dump($selectedPlayers);
        throw new \Exception('Not enough players available to fill the pack. ' . count($selectedPlayers));        }

        // Trier les joueurs sélectionnés par rating décroissant
        usort($selectedPlayers, function ($a, $b) {
            return $b->getRating() <=> $a->getRating();
        });

        return $selectedPlayers;
    }




}
