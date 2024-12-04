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
    public function generateRandomPack(string $type,EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // Récupérer les joueurs filtrés et triés depuis le repository
            $players = $entityManager->getRepository(SoccerPlayers::class)->getFilteredPlayersByType($type);

            if (empty($players)) {
                return new JsonResponse(['error' => 'No players match the selected pack type'], JsonResponse::HTTP_BAD_REQUEST);
            }
            
            $pack = new Pack();
            $pack->setName($type);
            // $pack->setPrice(rand(100, 1000));
            $pack->setType($type);

            foreach ($players as $player) {
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
                'type' => $pack->getType(),
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
