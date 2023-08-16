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

        return $this->json('', 'success', [
            'blogs' => $blogsArr
        ]);
    }

    #[Route('/blogs/create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $requestContent = json_decode($request->getContent(), true);
        $title =  $requestContent['title'];
        $body =  $requestContent['body'];

        $validationArray = [
            $this->validateInput('title', $title, 'varchar'),
            $this->validateInput('title', $title, 'required'),
            $this->validateInput('body', $body, 'text'),
            $this->validateInput('body', $body, 'required'),
        ];

        foreach ($validationArray as $validation) {
            if ($validation !== true)
                return $this->json($this->prepareResponseArray($validation, 'error'));
        }

        try {
            $author = $this->validateUser($requestContent['email'], $requestContent['password']);
            // return $this->json($this->prepareResponseArray($author, 'error'));

            if (!$author) {
                return $this->json($this->prepareResponseArray('Unauthorized user', 'error'));
            }

            $blog = $this->blogRepository->saveBlog(
                $title,
                $body,
                $author
            );
        } catch (\Throwable $th) {
            return $this->json($this->prepareResponseArray($th->getMessage(), 'error'));
        }

        return $this->json($this->prepareResponseArray('blog successfully posted', 'success', [
            'blog' => [
                'title' => $blog->getTitle(),
                'body' => $blog->getBody(),
            ],
        ]));
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

        return $this->json('', 'success', [
            'blog' => $blogArr,
        ]);
    }

    #[Route('/blogs/{id}', methods: ['PUT'])]
    public function update(Request $request, $id): JsonResponse
    {
        $requestContent = json_decode($request->getContent(), true);
        $title =  $requestContent['title'];
        $body =  $requestContent['body'];

        $validationArray = [
            $this->validateInput('title', $title, 'varchar'),
            $this->validateInput('title', $title, 'required'),
            $this->validateInput('body', $body, 'text'),
            $this->validateInput('body', $body, 'required'),
        ];

        foreach ($validationArray as $validation) {
            if ($validation !== true)
                return $this->json($this->prepareResponseArray($validation, 'error'));
        }

        try {
            $author = $this->validateUser($requestContent['email'], $requestContent['password']);

            if (!$author) {
                return $this->json('Unauthorized user', 'error');
            }

            $blog = $this->blogRepository->find(['id' => $id]);

            if (is_null($blog) || ($blog->getAuthorId() != $author->getId())) {
                return $this->json('Unauthorized user or wrong data', 'error');
            }

            $blog = $this->blogRepository->updateBlog(
                $id,
                $$title,
                $$body
            );
        } catch (\Throwable $th) {
            return $this->json($this->prepareResponseArray($th->getMessage(), 'error'));
        }

        return $this->json('blog successfully updated', 'success', [
            'blog' => $blog,
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
                return $this->json('Unauthorized user or wrong data', 'error');
            }

            $blog = $this->blogRepository->deleteBlog($id);
        } catch (\Throwable $th) {
            return $this->json($this->prepareResponseArray($th->getMessage(), 'error'));
        }

        return $this->json('blog successfully deleted', 'success');
    }

    private function validateUser($email, $password)
    {
        $author = $this->userRepository->findOneBy(['email' => $email]);
        // return !empty($author);
        if (
            $author && password_verify($password, $author->getPassword()) &&
            $author->getDeletedAt() == null
        ) {
            return $author;
        } else {
            return false;
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

    private function validateInput($inputName, $input, $rule)
    {
        switch ($rule) {
            case 'required':
                return !empty($input) ? true : 'Input ' . $inputName . ' is required';
                break;
            case 'text':
                return is_string($input) ? true : 'Input ' . $inputName . ' must be a string';
                break;
            case 'varchar':
                return (is_string($input) && strlen($input) <= 255) ? true : 'Input ' . $inputName . ' must be a string with max length of 255 characters';
                break;

            default:
                return true;
        }
    }
}
