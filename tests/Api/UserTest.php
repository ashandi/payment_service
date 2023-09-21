<?php

namespace App\Tests\Api;

use App\Repository\UserRepository;
use App\Tests\Helpers\UsersHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserTest extends WebTestCase
{
    use UsersHelper;
    private const URL = '/v1/users/';

    public function testUserGet_WrongMethod(): void
    {
        $client = static::createClient();

        $user = $this->getRandomUser();
        $response = $client->request('PUT', self::URL . $user->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testUserGet_NotIntegerId(): void
    {
        $client = static::createClient();

        $response = $client->request('GET', self::URL . 'test');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUserGet_NotExistingId(): void
    {
        $client = static::createClient();

        $response = $client->request('GET', self::URL . 1000000000000000);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUserGet(): void
    {
        $client = static::createClient();

        $user = $this->getRandomUser();
        $client->request('GET', self::URL . $user->getId());
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertArrayHasKey('balance', $data);
        $this->assertEquals($user->getBalance(), $data['balance']);
    }

    public function getUserRepository(): UserRepository
    {
        /** @var UserRepository $userRepo */
        return static::getContainer()->get(UserRepository::class);
    }
}
