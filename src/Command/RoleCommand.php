<?php

namespace App\Command;

use App\Entity\Role;
use App\Entity\User;
use App\Params\RoleParam;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'Admin',
    description: 'Kullanıcı Admin Rolü eklendi',
)]
class RoleCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'email girmeniz gerekmektedir.')
            ->setDescription('Rol Ataması Yapıldı.')
            ->setHelp('Bu komut maili girilen kullanıcıya admin yetkisi verir.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');
        $userRepository = $this->entityManager->getRepository(User::class);
        $role = $this->entityManager->getRepository(Role::class)->findOneBy(['role_name' => RoleParam::ROLE_ADMIN]);
        $rolesystem = $this->entityManager->getRepository(Role::class)->findOneBy(['role_name' => RoleParam::ROLE_SYSTEM_OPERATOR]);
        $user = $userRepository->findOneBy(['email' => $email]);

        if ($user) {
            $user->setIsAdmin(true);
            $user->addRoles($role);
            $user->addRoles($rolesystem);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $output->writeln($email .' Kullanıcısı Admin yetkisi aldı');
        } else {
            $output->writeln( $email . ' Kullanıcı bulunamadı.');
        }

        return Command::SUCCESS;
    }
}