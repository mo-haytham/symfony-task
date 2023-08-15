<?php

namespace App\Controller;

use App\Repository\BlogRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class BlogController extends AbstractController
{
    private $blogRepository;
    private $userRepository;

    public function __construct(BlogRepository $blogRepository, UserRepository $userRepository)
    {
        $this->blogRepository = $blogRepository;
        $this->userRepository = $userRepository;
    }

    #[Route('/blogs', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $blogs = $this->blogRepository->findBy(['deletedAt' => null]);

        $blogsArr = [];
        foreach ($blogs as $blog) {
            array_push($blogsArr, [
                'id' => $blog->getId(),
                'title' => $blog->getTitle(),
                'body' => $blog->getBody(),
                'author' => $blog->getAuthorId()->getName()
            ]);
        }

        return $this->json([
            'message' => '',
            'status' => 'success',
            'data' => [
                'blogs' => $blogsArr
            ],
        ]);
    }

    #[Route('/blogs/create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $requestContent = json_decode($request->getContent(), true);

        try {
            $author = $this->validateUser($requestContent['email'], $requestContent['password']);

            if (!$author) {
                return $this->json([
                    'message' => 'Unauthorized user',
                    'status' => 'error',
                    'data' => [],
                ]);
            }

            $blog = $this->blogRepository->saveBlog(
                $requestContent['title'],
                $requestContent['body'],
                $author
            );
        } catch (\Throwable $th) {
            return $this->json([
                'message' => $th->getMessage(),
                'status' => 'error',
                'data' => [],
            ]);
        }

        return $this->json([
            'message' => 'blog successfully posted',
            'status' => 'success',
            'data' => [
                'blog' => [
                    'title' => $blog->getTitle(),
                    'body' => $blog->getBody(),
                ],
            ],
        ]);
    }

    #[Route('/blogs/{id}', methods: ['GET'])]
    public function show($id): JsonResponse
    {
        $blog = $this->blogRepository->findOneBy(['id' => $id, 'deletedAt' => null]);

        $blogArr = [];
        if ($blog) {
            $blogArr = [
                'id' => $blog->getId(),
                'title' => $blog->getTitle(),
                'body' => $blog->getBody(),
                'author' => $blog->getAuthorId()->getName(),
            ];
        }

        return $this->json([
            'message' => '',
            'status' => 'success',
            'data' => [
                'blog' => $blogArr,
            ],
        ]);
    }

    #[Route('/blogs/{id}', methods: ['PUT'])]
    public function update(Request $request, $id): JsonResponse
    {
        $requestContent = json_decode($request->getContent(), true);

        try {
            $author = $this->validateUser($requestContent['email'], $requestContent['password']);
            $blog = $this->blogRepository->find(['id' => $id]);

            if ((is_null($blog) || !$author) && $blog->getAuthorId() != $author->getId()) {
                return $this->json([
                    'message' => 'Unauthorized user or wrong data',
                    'status' => 'error',
                    'data' => [],
                ]);
            }

            $blog = $this->blogRepository->updateBlog(
                $id,
                $requestContent['title'],
                $requestContent['body']
            );
        } catch (\Throwable $th) {
            return $this->json([
                'message' => $th->getMessage(),
                'status' => 'error',
                'data' => [],
            ]);
        }

        return $this->json([
            'message' => 'blog successfully updated',
            'status' => 'success',
            'data' => [
                'blog' => $blog,
            ],
        ]);
    }

    #[Route('/blogs/{id}', methods: ['DELETE'])]
    public function delete(Request $request, $id): JsonResponse
    {
        $requestContent = json_decode($request->getContent(), true);

        try {
            $author = $this->validateUser($requestContent['email'], $requestContent['password']);
            $blog = $this->blogRepository->find(['id' => $id]);

            if ((is_null($blog) || !$author) && $blog->getAuthorId() != $author->getId()) {
                return $this->json([
                    'message' => 'Unauthorized user or wrong data',
                    'status' => 'error',
                    'data' => [],
                ]);
            }

            $blog = $this->blogRepository->deleteBlog($id);
        } catch (\Throwable $th) {
            return $this->json([
                'message' => $th->getMessage(),
                'status' => 'error',
                'data' => [],
            ]);
        }

        return $this->json([
            'message' => 'blog successfully deleted',
            'status' => 'success',
            'data' => [],
        ]);
    }

    private function validateUser($email, $password)
    {
        $author = $this->userRepository->findOneBy(['email' => $email]);
        if ($author && password_verify($password, $author->getPassword())) {
            return $author;
        } else {
            return false;
        }
    }
}
