<?php

namespace App\Controller;

use App\Repository\UserPackPlayerRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class UserController extends AbstractController
{

    #[Route('/api/sync-session', name: 'sync_session', methods: ['POST'])]
    public function syncSession(Request $request, UserRepository $userRepository): JsonResponse
    {
        // Récupérer les données de la requête
        $data = json_decode($request->getContent(), true);
        $userId = $data['userId'] ?? null;

        if (!$userId) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'User ID is missing.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifier si l'utilisateur existe
        $user = $userRepository->find($userId);
        if (!$user) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'User not found.'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Synchroniser avec la session
        $session = $request->getSession();
        $session->set('user_id', $user->getId());

        return new JsonResponse(['status' => 'success'], JsonResponse::HTTP_OK);
    }


    #[Route('/api/user/current', methods: ['GET'])]
    public function getCurrentUser(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Récupérer l'ID de l'utilisateur depuis la session
        $session = $request->getSession();
        $userId = $session->get('user_id');

        if (!$userId) {
            return $this->json(['status' => 'error', 'message' => 'No user logged in'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Trouver l'utilisateur par ID
        $user = $em->getRepository(User::class)->find($userId);

        if (!$user) {
            return $this->json(['status' => 'error', 'message' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'credits' => $user->getCredits(),
            'roles' => $user->getRoles(),
        ], JsonResponse::HTTP_OK);
    }


    #[Route('/api/users/players/advanced-filter', name: 'user_players_advanced_filter', methods: ['GET'])]
    public function advancedFilterUserPlayers(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPackPlayerRepository $repository
    ): JsonResponse {
        // Récupérer l'ID utilisateur depuis les en-têtes
        $userId = $request->headers->get('X-User-Id');
        error_log('User ID received: ' . $userId); // Ajouter un log pour vérifier la valeur

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

        // Construction de la requête pour filtrer les joueurs associés à l'utilisateur
        $queryBuilder = $repository->createQueryBuilder('upp')
            ->innerJoin('upp.player', 'p')
            ->addSelect('p')
            ->where('upp.user = :userId')
            ->setParameter('userId', $userId);

        // 1. Filtrer par nom (partiel, insensible à la casse)
        if ($name = $request->query->get('name')) {
            $queryBuilder->andWhere('LOWER(p.name) LIKE LOWER(:name)')->setParameter('name', '%'.$name.'%');
        }

        // 2. Filtrer par club (partiel, insensible à la casse)
        if ($club = $request->query->get('club')) {
            $queryBuilder->andWhere('LOWER(p.club) LIKE LOWER(:club)')->setParameter('club', '%'.$club.'%');
        }

        // 3. Filtres exacts ou multiples pour certains champs spécifiques
        $multiSelectableFields = ['nation', 'rarity', 'type'];
        foreach ($multiSelectableFields as $field) {
            if ($value = $request->query->get($field)) {
                $values = explode(',', $value);
                if (count($values) > 1) {
                    $queryBuilder->andWhere("p.$field IN (:$field)")->setParameter($field, $values);
                } else {
                    $queryBuilder->andWhere("p.$field = :$field")->setParameter($field, $value);
                }
            }
        }

        // 4. Requêtes de plage pour les champs numériques
        $rangeFields = [
            'rating' => ['min_rating', 'max_rating'],
            'price' => ['min_price', 'max_price'],
            'rate' => ['min_rate', 'max_rate']
        ];

        foreach ($rangeFields as $field => $rangeParams) {
            $minParam = $request->query->get($rangeParams[0]);
            $maxParam = $request->query->get($rangeParams[1]);

            if ($minParam !== null) {
                $queryBuilder->andWhere("p.$field >= :min_$field")->setParameter("min_$field", $minParam);
            }
            if ($maxParam !== null) {
                $queryBuilder->andWhere("p.$field <= :max_$field")->setParameter("max_$field", $maxParam);
            }
        }

        // 5. Tri des résultats
        if ($sortBy = $request->query->get('sort_by')) {
            $sortOrder = $request->query->get('sort_order', 'ASC');
            $queryBuilder->orderBy("p.$sortBy", $sortOrder);
        }

        // 6. Pagination
        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, $request->query->getInt('limit', 20));
        $offset = ($page - 1) * $limit;

        $players = $queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $playersArray = array_map(function($userPackPlayer) {
            $player = $userPackPlayer->getPlayer();
            return [
                'idPackPlayer' => $userPackPlayer->getId(),
                'id' => $player->getId(),
                'name' => $player->getName(),
                'club' => $player->getClub(),
                'nation' => $player->getNation(),
                'rating' => $player->getRating(),
                'rarity' => $player->getRarity(),
                'type' => $player->getType(),
                'price' => $player->getPrice(),
                'rate' => $player->getRate(),
            ];
        }, $players);

        // Comptage du nombre total de résultats
        $countQueryBuilder = clone $queryBuilder;
        $totalResults = $countQueryBuilder
            ->select('COUNT(upp)')
            ->setFirstResult(0)
            ->setMaxResults(null)
            ->getQuery()
            ->getSingleScalarResult();

        return new JsonResponse([
            'players' => $playersArray,
            'page' => $page,
            'limit' => $limit,
            'total' => $totalResults
        ]);
    }


    #[Route('/api/players/sell/{userPackPlayerId}', name: 'sell_player', methods: ['POST'])]
    public function sellPlayer(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository, UserPackPlayerRepository $userPackPlayerRepository, int $userPackPlayerId): JsonResponse
    {
        // Récupérer l'ID utilisateur depuis les en-têtes
        $userId = $request->headers->get('X-User-Id');
        if (!$userId) {
            return new JsonResponse(['error' => 'User ID is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Récupérer l'utilisateur à partir de l'ID
        $user = $userRepository->find($userId);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Récupérer le joueur à partir de l'ID unique de user_pack_player
        $userPackPlayer = $userPackPlayerRepository->find($userPackPlayerId);
        if (!$userPackPlayer || $userPackPlayer->getUser()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Player not found in user pack'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Logique pour vendre le joueur
        $user->setCredits($user->getCredits() + $userPackPlayer->getPlayer()->getPrice());
        $entityManager->remove($userPackPlayer);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Player sold successfully'], JsonResponse::HTTP_OK);
    }



}
