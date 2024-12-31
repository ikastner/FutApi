<?php

namespace App\Controller;

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
        ], JsonResponse::HTTP_OK);
    }








}
