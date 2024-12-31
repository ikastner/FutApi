<?php
// src/Repository/UserRepository.php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }


    public function isAuthenticated($request): JsonResponse
    {
        // Vérifier si l'ID utilisateur est présent dans la session
        $session = $request->getSession();
        $userId = $session->get('user_id');

        if (!$userId) {
            // Si l'ID utilisateur n'est pas trouvé dans la session, renvoyer une erreur 401
            return new JsonResponse([
                'status' => 'error',
                'message' => 'User is not authenticated.'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Si l'utilisateur est trouvé dans la session
        return new JsonResponse(['status' => 'success'], JsonResponse::HTTP_OK);
    }
}