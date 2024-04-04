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
        $this->entitymanager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('sercan@gmail.com');
        $hashedPassword = $this->passwordHasher->hashPassword($user, '123qwe');

        $user->setPassword($hashedPassword);
        $user->setIsAdmin(true);
        $user->setIsActive(true);
        $user->setName('sercan');
        $user->setSurname('köse');
        $role = $manager->getRepository(Role::class)->find(RoleParam::ROLE_ADMIN_ID);
        dd($hashedPassword, $role);
        if ($role !== null) {
            $user->addRoles($role);
        } else {
            throw new \Exception('Role not found.');
        }
        $user->addRoles($role);
        $this->entitymanager->persist($user);
        $manager->flush();
    }
}
