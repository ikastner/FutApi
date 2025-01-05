<?php

namespace App\Controller;

use App\Entity\SoccerPlayers;
use App\Entity\UserPackPlayer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class SoccerController extends AbstractController
{
    #[Route('/soccerplayers/report', name: 'soccerplayers_report', methods: ['GET'])]
    public function soccerPlayersReport(EntityManagerInterface $entityManager): JsonResponse
    {
        $players = $entityManager->getRepository(SoccerPlayers::class)->findAll();

        $playersArray = [];
        foreach ($players as $player) {
            $playersArray[] = [
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
        }

        return new JsonResponse($playersArray);
    }

    #[Route('/soccerplayers/advanced-filter', name: 'soccerplayers_advanced_filter', methods: ['GET'])]
    public function advancedFilterSoccerPlayers(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $queryBuilder = $entityManager->getRepository(SoccerPlayers::class)->createQueryBuilder('p');

        // 1. Filtrer par nom (partiel, insensible à la casse)
        if ($name = $request->query->get('name')) {
            $queryBuilder->andWhere('LOWER(p.name) LIKE LOWER(:name)')->setParameter('name', '%' . $name . '%');
        }

        // 2. Filtrer par club (partiel, insensible à la casse)
        if ($club = $request->query->get('club')) {
            $queryBuilder->andWhere('LOWER(p.club) LIKE LOWER(:club)')->setParameter('club', '%' . $club . '%');
        }

        // 3. Filtres exacts ou multiples pour certains champs spécifiques
        $multiSelectableFields = ['nation', 'rarity', 'type'];
        foreach ($multiSelectableFields as $field) {
            if ($value = $request->query->get($field)) {
                // Vérifier si plusieurs valeurs sont passées (séparées par des virgules)
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

        $playersArray = array_map(function ($player) {
            return [
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
            ->select('COUNT(p)')
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

    #[Route('/api/user/players', name: 'user_players_filter', methods: ['GET'])]
    public function getUserPlayers(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Récupérer l'ID de l'utilisateur depuis l'en-tête
        $userId = $request->headers->get('X-User-Id');
        if (!$userId) {
            return new JsonResponse(['error' => 'User ID is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Créer le QueryBuilder pour UserPackPlayer
        $queryBuilder = $entityManager->getRepository(UserPackPlayer::class)
            ->createQueryBuilder('upp')
            ->join('upp.player', 'p')
            ->join('upp.user', 'u')
            ->where('u.id = :userId')
            ->setParameter('userId', $userId);

        // Filtres similaires mais adaptés à la nouvelle structure
        if ($name = $request->query->get('name')) {
            $queryBuilder->andWhere('LOWER(p.name) LIKE LOWER(:name)')->setParameter('name', '%' . $name . '%');
        }

        if ($club = $request->query->get('club')) {
            $queryBuilder->andWhere('LOWER(p.club) LIKE LOWER(:club)')->setParameter('club', '%' . $club . '%');
        }

        // Filtres multi-valeurs
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

        // Filtres de plage
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

        // Tri
        if ($sortBy = $request->query->get('sort_by')) {
            $sortOrder = $request->query->get('sort_order', 'ASC');
            if (in_array($sortBy, ['obtainedAt'])) {
                $queryBuilder->orderBy("upp.$sortBy", $sortOrder);
            } else {
                $queryBuilder->orderBy("p.$sortBy", $sortOrder);
            }
        }

        // Pagination
        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, $request->query->getInt('limit', 20));
        $offset = ($page - 1) * $limit;

        $results = $queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // Transformer les résultats
        $playersArray = array_map(function ($userPackPlayer) {
            $player = $userPackPlayer->getPlayer();
            $pack = $userPackPlayer->getPack();
            return [
                'id' => $player->getId(),
                'name' => $player->getName(),
                'club' => $player->getClub(),
                'nation' => $player->getNation(),
                'rating' => $player->getRating(),
                'rarity' => $player->getRarity(),
                'type' => $player->getType(),
                'price' => $player->getPrice(),
                'rate' => $player->getRate(),
                'obtained_at' => $userPackPlayer->getObtainedAt()->format('Y-m-d H:i:s'),
                'pack_type' => $pack->getType(),
                'pack_name' => $pack->getName()
            ];
        }, $results);

        // Comptage total
        $countQueryBuilder = clone $queryBuilder;
        $totalResults = $countQueryBuilder
            ->select('COUNT(upp.id)')
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

    #[Route('/api/soccerplayers/user', name: 'soccerplayers_get', methods: ['GET'])]
    public function getUserPlayer(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $userId = $request->headers->get('X-User-Id');
        if (!$userId) {
            return new JsonResponse(['error' => 'User ID is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Pagination : récupérer page et limite depuis la requête
        $page = (int)$request->query->get('page', 1);  // Valeur par défaut : page 1
        $limit = (int)$request->query->get('limit', 20); // Valeur par défaut : 20 joueurs

        // Calcul des offsets pour la pagination
        $offset = ($page - 1) * $limit;

        // Récupérer les joueurs avec pagination
        $players = $entityManager->getRepository(UserPackPlayer::class)
            ->createQueryBuilder('p')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $totalPlayers = $entityManager->getRepository(UserPackPlayer::class)->count([]);

        $playersArray = [];
        foreach ($players as $player) {
            $playersArray[] = [
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
        }

        return new JsonResponse([
            'players' => $playersArray,
            'total' => $totalPlayers
        ]);
    }

    #[Route('/api/soccerplayers/update/{id}', name: 'soccerplayers_update_one', methods: ['POST'])]
    public function updateSoccerPlayer(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $player = $entityManager->getRepository(SoccerPlayers::class)->find($id);

        if (!$player) {
            return new JsonResponse(['error' => 'Player not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $player->setName($data['name']);
        }
        if (isset($data['club'])) {
            $player->setClub($data['club']);
        }
        if (isset($data['nation'])) {
            $player->setNation($data['nation']);
        }
        if (isset($data['rating'])) {
            $player->setRating($data['rating']);
        }
        if (isset($data['rarity'])) {
            $player->setRarity($data['rarity']);
        }
        if (isset($data['type'])) {
            $player->setType($data['type']);
        }
        if (isset($data['price'])) {
            $player->setPrice($data['price']);
        }
        if (isset($data['rate'])) {
            $player->setRate($data['rate']);
        }

        $entityManager->persist($player);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Player updated successfully']);
    }

    #[Route('/api/soccerplayers/delete/{id}', name: 'soccerplayers_delete_one', methods: ['DELETE'])]
    public function deleteSoccerPlayer(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $player = $entityManager->getRepository(SoccerPlayers::class)->find($id);

        if (!$player) {
            return new JsonResponse(['error' => 'Player not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Delete related entries in UserPackPlayer
        $userPackPlayers = $entityManager->getRepository(UserPackPlayer::class)->findBy(['player' => $player]);
        foreach ($userPackPlayers as $userPackPlayer) {
            $entityManager->remove($userPackPlayer);
        }

        // Delete the player
        $entityManager->remove($player);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Player and related entries deleted successfully']);
    }

        #[Route('/api/soccerplayers/create', name: 'soccerplayers_create', methods: ['POST'])]
        public function createSoccerPlayer(Request $request, EntityManagerInterface $entityManager): JsonResponse
        {
            $data = json_decode($request->getContent(), true);

            $player = new SoccerPlayers();
            if (isset($data['name'])) {
                $player->setName($data['name']);
            }
            if (isset($data['club'])) {
                $player->setClub($data['club']);
            }
            if (isset($data['nation'])) {
                $player->setNation($data['nation']);
            }
            if (isset($data['rating'])) {
                $player->setRating($data['rating']);
            }
            if (isset($data['rarity'])) {
                $player->setRarity($data['rarity']);
            }
            if (isset($data['type'])) {
                $player->setType($data['type']);
            }
            if (isset($data['price'])) {
                $player->setPrice($data['price']);
            }
            if (isset($data['rate'])) {
                $player->setRate($data['rate']);
            }

            $entityManager->persist($player);
            $entityManager->flush();

            return new JsonResponse(['message' => 'Player created successfully'], JsonResponse::HTTP_CREATED);
        }

    #[Route('/api/soccerplayers/check-name', name: 'soccerplayers_check_name', methods: ['GET'])]
    public function checkPlayerName(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $name = $request->query->get('name');
        $player = $entityManager->getRepository(SoccerPlayers::class)->findOneBy(['name' => $name]);

        return new JsonResponse(['exists' => $player !== null]);
    }

}