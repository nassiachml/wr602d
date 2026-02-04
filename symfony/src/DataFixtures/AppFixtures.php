<?php

namespace App\DataFixtures;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $free = new Subscription();
        $free->setName('Free');
        $free->setDescription('5 PDF / jour');
        $free->setMaxPdfsPerDay(5);
        $free->setPrice(0);
        $free->setIsActive(true);
        $manager->persist($free);

        $premium = new Subscription();
        $premium->setName('Premium');
        $premium->setDescription('50 PDF / jour');
        $premium->setMaxPdfsPerDay(50);
        $premium->setPrice(9.99);
        $premium->setIsActive(true);
        $manager->persist($premium);

        $unlimited = new Subscription();
        $unlimited->setName('Unlimited');
        $unlimited->setDescription('Illimité');
        $unlimited->setMaxPdfsPerDay(0);
        $unlimited->setPrice(19.99);
        $unlimited->setIsActive(true);
        $manager->persist($unlimited);

        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $user->setSubscription($premium);
        $user->setDisplayName('Utilisateur test');
        $manager->persist($user);

        $manager->flush();
    }
}
