<?php

namespace App\Tests\Entity;

use App\Entity\Subscription;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testGetterAndSetter(): void
    {
        $user = new User();
        $email = 'test@test.com';
        $password = 'hashed';
        $displayName = 'Test User';

        $user->setEmail($email);
        $user->setPassword($password);
        $user->setDisplayName($displayName);

        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($email, $user->getUserIdentifier());
        $this->assertEquals($password, $user->getPassword());
        $this->assertEquals($displayName, $user->getDisplayName());
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testResetToken(): void
    {
        $user = new User();
        $token = 'abc123';
        $expiresAt = new \DateTimeImmutable('+1 hour');
        $user->setResetToken($token);
        $user->setResetTokenExpiresAt($expiresAt);
        $this->assertEquals($token, $user->getResetToken());
        $this->assertSame($expiresAt, $user->getResetTokenExpiresAt());
    }
}
