<?php

namespace App\Controller;

use App\Entity\Pack;
use App\Entity\SoccerPlayers;
use App\Entity\User;
use App\Entity\UserPackPlayer;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PackController extends AbstractController
{
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
            'Icon' => 2000,   // Prix pour le pack Icon
            'Gold' => 1200,    // Prix pour le pack Gold
            'Silver' => 800,   // Prix pour le pack Silver
            'Bronze' => 400,   // Prix pour le pack Bronze
        ];

        // Vérifier si le type de pack est valide
        if (!isset($packPrices[$type])) {
            return new JsonResponse(['error' => 'Invalid pack type'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Récupérer le prix du pack
        $packPrice = $packPrices[$type];

        // Vérifier si l'utilisateur a suffisamment de crédits
        if ($user->getCredits() < $packPrice) {
            return new JsonResponse(['error' => 'Crédits insuffisants pour ouvrir le pack'], JsonResponse::HTTP_BAD_REQUEST);
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

            /*foreach ($selectedPlayers as $player) {
                $pack->addPlayer($player);
            }*/
            foreach ($selectedPlayers as $player) {
                $pack->addPlayer($player);

                // Créer une nouvelle entrée UserPackPlayer
                $userPackPlayer = new UserPackPlayer();
                $userPackPlayer->setUser($user);
                $userPackPlayer->setPack($pack);
                $userPackPlayer->setPlayer($player);

                $entityManager->persist($userPackPlayer);
            }

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
                'userCredits' => $user->getCredits(),
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