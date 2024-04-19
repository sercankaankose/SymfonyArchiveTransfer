<?php

namespace App\DataFixtures;

use App\Entity\Role;
use App\Entity\User;
use App\Params\RoleParam;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Mime\Encoder\EncoderInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('yt@mail.com');
        $hashedPassword = $this->passwordHasher->hashPassword($user, '123qwe');

        $user->setPassword($hashedPassword);
        $user->setIsAdmin(true);
        $user->setIsActive(true);
        $user->setIsVerified(true);
        $user->setName('yt');
        $user->setSurname('admin');
        $manager->persist($user);
        $manager->flush();

        //----------------------------------
        $role = new Role();
        $roleAdmin = new Role();
        $roleAdmin->setRoleName('ROLE_ADMIN');
        $roleEditor = new Role();
        $roleEditor->setRoleName('ROLE_EDITOR');
        $roleOperator = new Role();
        $roleOperator->setRoleName('ROLE_OPERATOR');


        $manager->persist($roleAdmin);
        $manager->persist($roleEditor);
        $manager->persist($roleOperator);
        $manager->flush();
    }
}
