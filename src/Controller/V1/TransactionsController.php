<?php

declare(strict_types=1);

namespace App\Controller\V1;

use App\Controller\User;
use App\Repository\UserRepository;
use App\Service\Transaction\TransactionService;
use App\Validation\CreateTransactionRequest;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/v1/transactions', name: 'transactions_')]
class TransactionsController extends AbstractController
{
    private UserRepository $userRepository;
    private TransactionService $transactionService;
    private ValidatorInterface $validator;
    private LoggerInterface $logger;

    public function __construct(
        UserRepository $userRepository,
        TransactionService $transactionService,
        ValidatorInterface $validator,
        LoggerInterface $logger,
    ) {
        $this->userRepository = $userRepository;
        $this->transactionService = $transactionService;
        $this->validator = $validator;
        $this->logger = $logger;
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $createTransactionRequest = new CreateTransactionRequest($request->toArray());
            $errors = $this->validator->validate($createTransactionRequest);

            if (count($errors) > 0) {
                return $this->json($errors, Response::HTTP_BAD_REQUEST);
            }

            $dstUser = $this->userRepository->find($createTransactionRequest->getDstUserId());
            if ($dstUser === null) {
                return new JsonResponse(null, Response::HTTP_NOT_FOUND);
            }

            $srcUser = $createTransactionRequest->getSrcUserId() === null
                ? null
                : $this->userRepository->find($createTransactionRequest->getSrcUserId());
            if ($srcUser !== null && $srcUser->getBalance() < $createTransactionRequest->getAmount()) {
                return new JsonResponse(null, Response::HTTP_BAD_REQUEST);
            }

            $transaction = $this->transactionService->createTransaction(
                $srcUser,
                $dstUser,
                $createTransactionRequest->getAmount(),
            );

            $this->transactionService->executeTransaction($transaction);

            return $this->json($transaction);
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                "Error during creating new transaction. Error: %s, Trace: %s.",
                $e->getMessage(),
                $e->getTraceAsString(),
            ));
            return new JsonResponse(null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
