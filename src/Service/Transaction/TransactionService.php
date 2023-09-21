<?php

declare(strict_types=1);

namespace App\Service\Transaction;

use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Throwable;

class TransactionService
{
    private TransactionRepository $transactionRepository;
    private UserRepository $userRepository;
    private LoggerInterface $logger;

    public function __construct(
        TransactionRepository $transactionRepository,
        UserRepository $userRepository,
        LoggerInterface $logger,
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->userRepository = $userRepository;
        $this->logger = $logger;
    }

    public function createTransaction(?User $srcUser, User $dstUser, float $amount): Transaction
    {
        $transaction = (new Transaction())
            ->setDstUser($dstUser)
            ->setAmount($amount)
            ->setStatus(TransactionStatus::NEW);

        if ($srcUser !== null) {
            $transaction->setSrcUser($srcUser);
        }

        $this->transactionRepository->save($transaction);

        return $transaction;
    }

    public function executeTransaction(Transaction $transaction): void
    {
        $this->transactionRepository->beginDbTransaction();

        try {
            $this->transactionRepository->refresh($transaction);
            if ($transaction->getStatus() !== TransactionStatus::NEW) {
                $this->transactionRepository->rollbackDbTransaction();
                return;
            }

            $srcUser = $transaction->getSrcUser();
            $dstUser = $transaction->getDstUser();

            if ($srcUser !== null) {
                if ($srcUser->getBalance() < $transaction->getAmount()) {
                    $transaction->setStatus(TransactionStatus::FAILED);
                    $this->transactionRepository->save($transaction);
                    $this->transactionRepository->commitDbTransaction();
                    return;
                }
            }

            if ($srcUser !== null) {
                $srcUser->withdrawal($transaction->getAmount());
                $this->userRepository->save($srcUser);
            }

            $dstUser->deposit($transaction->getAmount());
            $this->userRepository->save($dstUser);

            $transaction->setStatus(TransactionStatus::SUCCEEDED);
            $this->transactionRepository->save($transaction);
            $this->transactionRepository->commitDbTransaction();
        } catch (Throwable $e) {
            $this->transactionRepository->rollbackDbTransaction();
            $this->logger->error(sprintf(
                "Error during executing transaction %d. Error: %s, Trace: %s.",
                $transaction->getId(),
                $e->getMessage(),
                $e->getTraceAsString(),
            ));
        }
    }
}