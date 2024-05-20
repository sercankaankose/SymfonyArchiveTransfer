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
        $roleNames = [
            RoleParam::ROLE_ADMIN,
            RoleParam::ROLE_EDITOR,
            RoleParam::ROLE_OPERATOR,
            RoleParam::ROLE_SYSTEM_OPERATOR,
        ];

        foreach ($roleNames as $roleName) {
            $existingRole = $this->entityManager->getRepository(Role::class)->findOneBy(['role_name' => $roleName]);

            if (!$existingRole) {
                $newRole = new Role();
                $newRole->setRoleName($roleName);
                $manager->persist($newRole);
            }
        }

        $manager->flush();


        $user = new User();
        $user->setEmail('yt@mail.com');
        $existingUser =  $this->entityManager->getRepository(User::class)->findOneBy(['email'=> $user->getEmail()]);

        $hashedPassword = $this->passwordHasher->hashPassword($user, '123qwe');

        $user->setPassword($hashedPassword);
        $user->setIsAdmin(true);
        $user->setIsActive(true);
        $user->setIsVerified(true);
        $user->setName('yt');
        $user->setSurname('admin');
        $adminRole = $manager->getRepository(Role::class)->findOneBy(['role_name'=> RoleParam::ROLE_ADMIN]);
        $user->addRoles($adminRole);
       if (!$existingUser){
        $manager->persist($user);
        $manager->flush();
    }
        //----------------------------------

    }

}
