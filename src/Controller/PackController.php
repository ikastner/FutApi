<?php

namespace App\Controller;

use App\Entity\Pack;
use App\Entity\SoccerPlayers;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PackController extends AbstractController
{


//    #[Route('/pack', name: 'create_pack', methods: ['POST'])]
//    public function createPack(Request $request, EntityManagerInterface $entityManager): JsonResponse
//    {
//        $data = json_decode($request->getContent(), true);
//
//        $pack = new Pack();
//        $pack->setName($data['name']);
//        $pack->setPrice($data['price']);
//        $pack->setPrice($data['type']);
//
//        // Ajouter les joueurs au pack si fournis
//        if (isset($data['players'])) {
//            foreach ($data['players'] as $playerId) {
//                $player = $entityManager->getRepository(SoccerPlayers::class)->find($playerId);
//                if ($player) {
//                    $pack->addPlayer($player);
//                }
//            }
//        }
//
//        $entityManager->persist($pack);
//        $entityManager->flush();
//
//        return new JsonResponse(['message' => 'Pack created successfully', 'pack_id' => $pack->getId()], JsonResponse::HTTP_CREATED);
//    }

//    #[Route('/pack/{id}', name: 'get_pack', requirements: ["id"=>"\d+"], methods: ['GET'])]
//    public function getPack(int $id, EntityManagerInterface $entityManager): JsonResponse
//    {
//        $pack = $entityManager->getRepository(Pack::class)->find($id);
//
//        if (!$pack) {
//            return new JsonResponse(['error' => 'Pack not found'], JsonResponse::HTTP_NOT_FOUND);
//        }
//
//        $playersArray = [];
//        foreach ($pack->getPlayers() as $player) {
//            $playersArray[] = [
//                'id' => $player->getId(),
//                'name' => $player->getName(),
//                'club' => $player->getClub(),
//                'nation' => $player->getNation(),
//                'rating' => $player->getRating(),
//                'rarity' => $player->getRarity(),
//                'type' => $player->getType(),
//                'price' => $player->getPrice(),
//            ];
//        }
//
//        $packData = [
//            'id' => $pack->getId(),
//            'name' => $pack->getName(),
//            'price' => $pack->getPrice(),
//            'type' => $pack->getType(),
//            'players' => $playersArray,
//        ];
//
//        return new JsonResponse($packData);
//    }
    // src/Controller/PackController.php



    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }


    #[Route('/api/pack/random/{type}', name: 'generate_random_pack', methods: ['POST', 'GET'])]
    public function generateRandomPack(
        string $type,
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): JsonResponse {
        // Récupérer l'ID de l'utilisateur depuis l'en-tête de la requête
        $userId = $request->headers->get('X-User-Id');
        error_log('User ID received: ' . $userId);  // Ajouter un log pour vérifier la valeur

        // Vérifier que l'ID utilisateur est présent et valide
        if (!$userId) {
            return new JsonResponse(['error' => 'User ID is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Récupérer l'utilisateur à partir de l'ID
        $user = $entityManager->getRepository(User::class)->find($userId);

        // Vérifier si l'utilisateur existe
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Vérifier si l'utilisateur a suffisamment de crédits
        $packPrices = [
            'Icon' => 15000,   // Prix pour le pack Icon
            'Gold' => 500,    // Prix pour le pack Gold
            'Silver' => 50,   // Prix pour le pack Silver
            'Bronze' => 20,   // Prix pour le pack Bronze
        ];

        // Vérifier si le type de pack est valide
        if (!isset($packPrices[$type])) {
            return new JsonResponse(['error' => 'Invalid pack type'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Récupérer le prix du pack
        $packPrice = $packPrices[$type];

        // Vérifier si l'utilisateur a suffisamment de crédits
        if ($user->getCredits() < $packPrice) {
            return new JsonResponse(['error' => 'Insufficient credits to open the pack'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Logique pour générer le pack, mettre à jour les crédits, etc.
        try {
            $validTypes = ['Icon', 'Gold', 'Silver', 'Bronze'];
            if (!in_array($type, $validTypes)) {
                return new JsonResponse(['error' => 'Invalid pack type'], JsonResponse::HTTP_BAD_REQUEST);
            }

            // Soustraire les crédits du pack
            $user->setCredits($user->getCredits() - $packPrice);
            $entityManager->persist($user);
            $entityManager->flush();

            // Sélectionner des joueurs aléatoires
            $selectedPlayers = $entityManager->getRepository(SoccerPlayers::class)->selectWeightedRandom($type, 5);
            if (empty($selectedPlayers)) {
                return new JsonResponse(['error' => 'No players match the selected pack type'], JsonResponse::HTTP_BAD_REQUEST);
            }

            // Création du pack avec les joueurs sélectionnés
            $pack = new Pack();
            $pack->setName($type);
            $pack->setType($type);

            foreach ($selectedPlayers as $player) {
                $pack->addPlayer($player);

                // Créer une entrée dans la table PackSoccerPlayer pour associer le joueur, le pack, et l'utilisateur
                $packSoccerPlayer = new PackSoccerPlayer();
                $packSoccerPlayer->setUser($user);          // Lier l'utilisateur
                $packSoccerPlayer->setPack($pack);          // Lier le pack
                $packSoccerPlayer->setSoccerPlayer($player); // Lier le joueur de football

                $entityManager->persist($packSoccerPlayer);  // Persister l'association
            }

            // Persister le pack après avoir ajouté tous les joueurs
            $entityManager->persist($pack);
            $entityManager->flush();

            // Récupérer les données des joueurs pour la réponse
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
            // Log the error message for debugging purposes
            error_log('An error occurred: ' . $e->getMessage());
            return new JsonResponse([
                'error' => 'An internal error occurred.',
                'message' => $e->getMessage() // Ajouter le message d'erreur pour le débogage
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }




}