<?php

namespace App\Validation;

use Symfony\Component\Validator\Constraints as Assert;

class CreateTransactionRequest
{
    #[Assert\NotBlank]
    #[Assert\Type('float')]
    #[Assert\GreaterThan(0)]
    private float $amount;

    #[Assert\NotBlank]
    #[Assert\Type('int')]
    #[Assert\GreaterThan(0)]
    private int $dstUserId;

    #[Assert\Type('int')]
    #[Assert\GreaterThan(0)]
    private ?int $srcUserId;

    public function __construct(array $requestParams)
    {
        $this->amount = isset($requestParams['amount']) ? floatval($requestParams['amount']) : 0;
        $this->dstUserId = isset($requestParams['dstUserId']) ? intval($requestParams['dstUserId']) : 0;
        $this->srcUserId = isset($requestParams['srcUserId']) ? intval($requestParams['srcUserId']) : null;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getDstUserId(): int
    {
        return $this->dstUserId;
    }

    public function getSrcUserId(): ?int
    {
        return $this->srcUserId;
    }
}
