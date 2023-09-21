<?php

declare(strict_types=1);

namespace App\Controller\V1;

use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

#[Route('/v1/users', name: 'users_')]
class UsersController extends AbstractController
{
    private UserRepository $userRepository;
    private LoggerInterface $logger;

    public function __construct(
        UserRepository $userRepository,
        LoggerInterface $logger,
    ) {
        $this->userRepository = $userRepository;
        $this->logger = $logger;
    }

    #[Route('/{id}', name: 'get', requirements: ['id'=>'\d+'], methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        try {
            $user = $this->userRepository->find($id);

            if ($user === null) {
                return new JsonResponse(null, Response::HTTP_NOT_FOUND);
            }

            return $this->json($user);
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                "Error during getting user data by id %d. Error: %s, Trace: %s.",
                $id,
                $e->getMessage(),
                $e->getTraceAsString(),
            ));
            return new JsonResponse(null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
