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
            return $this->json($this->prepareResponseArray($th->getMessage(), 'error'));
        }

        return $this->json($this->prepareResponseArray('register success', 'success', [
            'user' => [
                'name' => $user->getName(),
                'email' => $user->getEmail(),
            ],
        ]));
    }

    #[Route('/delete', methods: ['DELETE'])]
    public function delete(Request $request): JsonResponse
    {
        $requestContent = json_decode($request->getContent(), true);

        $author = $this->userRepository->findOneBy(['email' => $requestContent['email']]);

        if ($author && password_verify($requestContent['password'], $author->getPassword())) {

            try {
                $author = $this->userRepository->deleteUser($author->getId());
            } catch (\Throwable $th) {
                return $this->json($this->prepareResponseArray($th->getMessage(), 'error'));
            }

            return $this->json($this->prepareResponseArray('user successfully deleted', 'success'));
        } else {
            return $this->json($this->prepareResponseArray('Unauthorized user', 'error'));
        }
    }

    private function prepareResponseArray($message, $status, $data = [])
    {
        return [
            'message' => $message,
            'status' => $status,
            'data' => $data
        ];
    }
}
