<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    #[Route('/register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $requestContent = json_decode($request->getContent(), true);

        try {
            $user = $this->userRepository->saveUser(
                $requestContent['name'],
                $requestContent['email'],
                $requestContent['password'],
            );
        } catch (\Throwable $th) {
            return $this->json([
                'message' => $th->getMessage(),
                'status' => 'error',
                'data' => [],
            ]);
        }

        return $this->json([
            'message' => 'register success',
            'status' => 'success',
            'data' => [
                'user' => [
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                ],
            ],
        ]);
    }
}
