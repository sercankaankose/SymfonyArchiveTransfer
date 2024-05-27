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
    name: 'RemoveAdmin',
    description: 'Kullanıcıdan Admin Rolü kaldırıldı',
)]
class AdminRoleDeleteCommand extends Command
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
            ->addArgument('email', InputArgument::REQUIRED, 'Email adresi gereklidir.')
            ->setDescription('Kullanıcıdan admin yetkisi kaldırılır.')
            ->setHelp('Bu komut ile maili girilen kullanıcıdan admin yetkisi kaldırılır.');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');
        $userRepository = $this->entityManager->getRepository(User::class);
        $role = $this->entityManager->getRepository(Role::class)->findOneBy(['role_name' => RoleParam::ROLE_ADMIN]);
        $rolesystem = $this->entityManager->getRepository(Role::class)->findOneBy(['role_name' => RoleParam::ROLE_SYSTEM_OPERATOR]);
        $user = $userRepository->findOneBy(['email' => $email]);

        if ($user) {
            $user->setIsAdmin(false);
            if ($role) {
                $user->removeRole($role);
            }
            if ($rolesystem) {
                $user->removeRole($rolesystem);
            }
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $output->writeln($email .' Kullanıcısından admin yetkisi kaldırıldı');
        } else {
            $output->writeln($email . ' Kullanıcı bulunamadı.');
        }

        return Command::SUCCESS;
    }
}
