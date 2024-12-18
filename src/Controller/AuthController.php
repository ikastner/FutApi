<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class AuthController extends AbstractController
{
    private $passwordEncoder;
    private $jwtManager;

    // public function __construct(UserPasswordEncoderInterface  $passwordEncoder, JWTTokenManagerInterface $jwtManager)
    // {
    //     $this->passwordEncoder = $passwordEncoder;
    //     $this->jwtManager = $jwtManager;
    // }

    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(Request $request, UserRepository $userRepository): Response
    {
        $data = json_decode($request->getContent(), true);
        $user = new User();
        $user->setUsername($data['username']);
        $user->setPassword($this->passwordEncoder->encodePassword($user, $data['password']));

        $userRepository->save($user);

        return new Response('Création user avec success', Response::HTTP_CREATED);
    }


    #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function login(): Response
    {
        // La méthode login() est gérée automatiquement par LexikJWTAuthenticationBundle
        return new Response();
    }
}
