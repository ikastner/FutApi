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

    #[Route('/pack/{id}', name: 'get_pack', methods: ['GET'], requirements: ["id"=>"\d+"])]
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
            'players' => $playersArray,
        ];

        return new JsonResponse($packData);
    }
    #[Route('/pack/random', name: 'generate_random_pack', methods: ['POST', 'GET'])]
    public function generateRandomPack(EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // Vérifier s'il y a des joueurs disponibles
            $players = $entityManager->getRepository(SoccerPlayers::class)->findAll();

            if (empty($players)) {
                return new JsonResponse(['error' => 'No players available'], JsonResponse::HTTP_BAD_REQUEST);
            }

            $pack = new Pack();
            $pack->setName('Random Pack');
            $pack->setPrice(rand(100, 1000));

            // Sélection aléatoire de joueurs
            shuffle($players);
            $randomPlayers = array_slice($players, 0, rand(1, min(5, count($players))));

            foreach ($randomPlayers as $player) {
                $pack->addPlayer($player);
            }

            $entityManager->persist($pack);
            $entityManager->flush();

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
                'players' => $playersArray,
            ];

            return new JsonResponse($packData, JsonResponse::HTTP_CREATED);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'An error occurred: ' . $e->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
