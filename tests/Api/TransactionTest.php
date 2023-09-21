<?php

namespace App\Tests\Api;

use App\Repository\UserRepository;
use App\Service\Transaction\TransactionStatus;
use App\Tests\Helpers\UsersHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class TransactionTest extends WebTestCase
{
    use UsersHelper;
    private const URL = '/v1/transactions';

    public function testTransactionCreate_WrongMethod(): void
    {
        $client = static::createClient();

        $user = $this->getRandomUser();
        $amount = $this->getRandomAmount();

        $response = $client->request(
            'PUT',
            self::URL,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'amount' => $amount,
                'dstUserId' => $user->getId(),
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testTransactionCreate_EmptyDestinationUser(): void
    {
        $client = static::createClient();

        $user = $this->getRandomUser();
        $amount = $this->getRandomAmount();

        $response = $client->request(
            'POST',
            self::URL,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'amount' => $amount,
                'srcUserId' => $user->getId(),
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testTransactionCreate_IncorrectAmount(): void
    {
        $client = static::createClient();

        $user = $this->getRandomUser();
        $amount = $this->getRandomAmount();

        $response = $client->request(
            'POST',
            self::URL,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'amount' => -1 * $amount,
                'dstUserId' => $user->getId(),
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testTransactionCreate_UserNotExists(): void
    {
        $client = static::createClient();

        $amount = $this->getRandomAmount();

        $response = $client->request(
            'POST',
            self::URL,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'amount' => $amount,
                'dstUserId' => 1000000000,
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testTransactionCreate_SourceUserHasNotMoney(): void
    {
        $client = static::createClient();

        $user1 = $this->getRandomUser();
        $user2 = $this->getRandomUser();

        $response = $client->request(
            'POST',
            self::URL,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'srcUserId' => $user1->getId(),
                'amount' => $user1->getBalance() + 0.01,
                'dstUserId' => $user2->getId(),
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testTransactionCreate_Deposit(): void
    {
        $client = static::createClient();

        $user = $this->getRandomUser();
        $amount = $this->getRandomAmount();

        $client->request(
            'POST',
            self::URL,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'amount' => $amount,
                'dstUserId' => $user->getId(),
            ]),
        );
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertEquals(TransactionStatus::SUCCEEDED->value, $data['status']);

        $userAfter = $this->getUserRepository()->find($user->getId());

        $this->assertEquals($user->getBalance() + $amount, $userAfter->getBalance());
    }

    public function testTransactionCreate_TransferBetweenUsers(): void
    {
        $client = static::createClient();

        $user1 = $this->getRandomUser();
        $user2 = $this->getRandomUser();
        $amount = $user1->getBalance() / 2;

        $client->request(
            'POST',
            self::URL,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode([
                'srcUserId' => $user1->getId(),
                'amount' => $amount,
                'dstUserId' => $user2->getId(),
            ]),
        );
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertEquals(TransactionStatus::SUCCEEDED->value, $data['status']);

        $user1After = $this->getUserRepository()->find($user1->getId());
        $user2After = $this->getUserRepository()->find($user2->getId());

        $this->assertEquals($user1->getBalance() - $amount, $user1After->getBalance());
        $this->assertEquals($user2->getBalance() + $amount, $user2After->getBalance());
    }

    public function getUserRepository(): UserRepository
    {
        /** @var UserRepository $userRepo */
        return static::getContainer()->get(UserRepository::class);
    }

    private function getRandomAmount(): float
    {
        $randomFloat = mt_rand(0, 10000) / 100;
        return number_format($randomFloat, 2);
    }
}
