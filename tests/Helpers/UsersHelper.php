<?php

namespace App\Tests\Helpers;

use App\Entity\User;
use App\Repository\UserRepository;

trait UsersHelper
{
    /** This trait requires its user to have method getUserRepository */
    abstract public function getUserRepository(): UserRepository;

    private static array $users = [];

    private function getRandomUser(): User
    {
        if (count(static::$users) === 0) {
            static::$users = $this->getUserRepository()->findAll();
        }

        return static::$users[array_rand(self::$users)];
    }
}