<?php

namespace App\Service\Transaction;

enum TransactionStatus: string
{
    case NEW = 'new';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
}
