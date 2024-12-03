<?php

namespace App\Controller;

use App\Entity\SoccerPlayers;
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

        // Name partial match
        if ($name = $request->query->get('name')) {
            $queryBuilder->andWhere('LOWER(p.name) LIKE LOWER(:name)')->setParameter('name', '%'.$name.'%');
        }

        // Club partial match (case-insensitive)
        if ($club = $request->query->get('club')) {
            $queryBuilder->andWhere('LOWER(p.club) LIKE LOWER(:club)')->setParameter('club', '%'.$club.'%');
        }

        // Exact matches for other fields
        $exactFields = ['nation', 'rarity', 'type'];
        foreach ($exactFields as $field) {
            if ($value = $request->query->get($field)) {
                $queryBuilder->andWhere("p.$field = :$field")->setParameter($field, $value);
            }
        }

        // Range queries for numerical fields
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

        // Sorting
        if ($sortBy = $request->query->get('sort_by')) {
            $sortOrder = $request->query->get('sort_order', 'ASC');
            $queryBuilder->orderBy("p.$sortBy", $sortOrder);
        }

        // Pagination
        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, $request->query->getInt('limit', 20));
        $offset = ($page - 1) * $limit;

        $players = $queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $playersArray = array_map(function($player) {
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

        // Count total matching results
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
}