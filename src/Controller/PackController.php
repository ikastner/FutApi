<?php

namespace App\Controller;

use App\Entity\Pack;
use App\Entity\SoccerPlayers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PackController extends AbstractController
{
    #[Route('/pack', name: 'create_pack', methods: ['POST'])]
    public function createPack(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $pack = new Pack();
        $pack->setName($data['name']);
        $pack->setPrice($data['price']);
        $pack->setPrice($data['type']);

//        $pack->setName($data['Argent']);
//        //$pack->setPrice($data['price']);
//        $pack->setType($data['Argent']);

        // Ajouter les joueurs au pack si fournis
        if (isset($data['players'])) {
            foreach ($data['players'] as $playerId) {
                $player = $entityManager->getRepository(SoccerPlayers::class)->find($playerId);
                if ($player) {
                    $pack->addPlayer($player);
                }
            }
        }

        $entityManager->persist($pack);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Pack created successfully', 'pack_id' => $pack->getId()], JsonResponse::HTTP_CREATED);
    }

    #[Route('/pack/{id}', name: 'get_pack', requirements: ["id"=>"\d+"], methods: ['GET'])]
    public function getPack(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $pack = $entityManager->getRepository(Pack::class)->find($id);

        if (!$pack) {
            return new JsonResponse(['error' => 'Pack not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $playersArray = [];
        foreach ($pack->getPlayers() as $player) {
            $playersArray[] = [
                'id' => $player->getId(),
                'name' => $player->getName(),
                'club' => $player->getClub(),
                'nation' => $player->getNation(),
                'rating' => $player->getRating(),
                'rarity' => $player->getRarity(),
                'type' => $player->getType(),
                'price' => $player->getPrice(),
            ];
        }
 
        $packData = [
            'id' => $pack->getId(),
            'name' => $pack->getName(),
            'price' => $pack->getPrice(),
            'type' => $pack->getType(),
            'players' => $playersArray,
        ];

        return new JsonResponse($packData);
    }
    
    #[Route('/pack/random/{type}', name: 'generate_random_pack', methods: ['POST', 'GET'])]
    public function generateRandomPack(string $type, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // Récupérer tous les joueurs
            $players = $entityManager->getRepository(SoccerPlayers::class)->findAll();

            if (empty($players)) {
                return new JsonResponse(['error' => 'No players available'], JsonResponse::HTTP_BAD_REQUEST);
            }

            // Filtrer les joueurs en fonction du type de pack
            $typeHierarchy = [
                'Icon' => ['Icon', 'Gold', 'Silver', 'Bronze'],
                'Gold' => ['Gold', 'Silver', 'Bronze'],
                'Silver' => ['Silver', 'Bronze'],
                'Bronze' => ['Bronze'],
            ];

            $filteredPlayers = array_filter($players, function ($player) use ($type, $typeHierarchy) {
                return in_array($player->getType(), $typeHierarchy[$type] ?? []);
            });

            if (empty($filteredPlayers)) {
                return new JsonResponse(['error' => 'No players match the selected pack type'], JsonResponse::HTTP_BAD_REQUEST);
            }

            //Étape 1 : Calcul des probabilités basées sur la colonne `rate`
            $totalRate = array_sum(array_map(fn($player) => $player->getRate(), $filteredPlayers));
            $playersWithProbabilities = array_map(function ($player) use ($totalRate) {
                return [
                    'player' => $player,
                    'probability' => $player->getRate() / $totalRate, // Probabilité normalisée
                ];
            }, $filteredPlayers);

//

            // Étape 3 : Sélection aléatoire pondérée (5 joueurs par pack)
            $selectedPlayers = $this->selectWeightedRandom($playersWithProbabilities, 5);

            // Créer le pack
            $pack = new Pack();
            $pack->setName($type);
            $pack->setType($type);

            foreach ($selectedPlayers as $player) {
                $pack->addPlayer($player['player']);
            }

            $entityManager->persist($pack);
            $entityManager->flush();

            // Préparer les données du pack
            $playersArray = array_map(function ($playerData) {
                $player = $playerData['player'];
                return [
                    'id' => $player->getId(),
                    'name' => $player->getName(),
                    'club' => $player->getClub(),
                    'nation' => $player->getNation(),
                    'rating' => $player->getRating(),
                    'rarity' => $player->getRarity(),
                    'type' => $player->getType(),
                    'price' => $player->getPrice(),
                ];
            }, $selectedPlayers);

            $packData = [
                'id' => $pack->getId(),
                'name' => $pack->getName(),
                'price' => $pack->getPrice(),
                'type' => $pack->getType(),
                'players' => $playersArray, // Sortie triée
            ];

            return new JsonResponse($packData, JsonResponse::HTTP_CREATED);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'An error occurred: ' . $e->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Sélection aléatoire pondérée.
     */
    private function selectWeightedRandom(array $playersWithProbabilities, int $count): array
    {
        $selected = [];
        $totalProbability = array_sum(array_column($playersWithProbabilities, 'probability'));

        while (count($selected) < $count && !empty($playersWithProbabilities)) {
            $randomValue = mt_rand() / mt_getrandmax() * $totalProbability;
            $cumulative = 0;

            foreach ($playersWithProbabilities as $index => $playerData) {
                $cumulative += $playerData['probability'];
                if ($cumulative >= $randomValue) {
                    $selected[] = $playerData;
                    $totalProbability -= $playerData['probability'];
                    unset($playersWithProbabilities[$index]);
                    $playersWithProbabilities = array_values($playersWithProbabilities);
                    break;
                }
            }
        }

        return $selected;
    }
}
