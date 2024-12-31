<?php
namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthController extends AbstractController
{
    #[Route('/api/register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Création de l'utilisateur
        $user = new User();
        $user->setUsername($data['username']);
        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        $user->setRoles(['ROLE_USER']);
        $user->setApiToken(bin2hex(random_bytes(32))); // Token API généré

        // Validation des données
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return $this->json(['status' => 'error', 'message' => 'Invalid input'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérification si l'utilisateur existe déjà
        $existingUser = $em->getRepository(User::class)->findOneBy(['username' => $data['username']]);
        if ($existingUser) {
            return $this->json(['status' => 'error', 'message' => 'Username already exists'], JsonResponse::HTTP_CONFLICT);
        }

        // Sauvegarder l'utilisateur en base de données
        $em->persist($user);
        $em->flush();

        // Option 1 : Authentifier l'utilisateur et stocker l'ID dans la session
        $session = $request->getSession();
        $session->set('user_id', $user->getId());

        return $this->json([
            'status' => 'success',
            'message' => 'User registered successfully',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'roles' => $user->getRoles(),
                'api_token' => $user->getApiToken(),
                'credit' => $user->getCredits()
            ]
        ]);
    }

    #[Route('/api/login', methods: ['POST'])]
    public function login(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        UserProviderInterface $userProvider
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $username = $data['username'];
        $password = $data['password'];

        $user = $userProvider->loadUserByIdentifier($username);

        if (!$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['status' => 'error', 'message' => 'Invalid password'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Authentifier l'utilisateur et stocker son ID dans la session
        $session = $request->getSession();
        $session->set('user_id', $user->getId());

        // Renvoie les informations de l'utilisateur dans la réponse JSON
        return $this->json([
            'status' => 'success',
            'message' => 'User logged in successfully',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'roles' => $user->getRoles(),
                'credit' => $user->getCredits()
            ]
        ]);
    }


    #[Route('/api/logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $session->invalidate(); // Détruit la session

        return $this->json(['status' => 'success', 'message' => 'User logged out successfully']);
    }
}





