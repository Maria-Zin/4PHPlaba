<?php

namespace App\Command;

use App\Entity\Role;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:promote-admin',
    description: 'Assigns ROLE_ADMIN to an existing user by email.',
)]
final class PromoteUserToAdminCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private RoleRepository $roleRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'User email');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = trim((string) $input->getArgument('email'));

        if ($email === '') {
            $io->error('Email must not be empty.');

            return Command::INVALID;
        }

        $this->logger->info('Promote user to admin command started.', [
            'email' => $email,
        ]);

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if ($user === null) {
            $this->logger->warning('User for admin promotion was not found.', [
                'email' => $email,
            ]);
            $io->error(sprintf('User with email "%s" was not found.', $email));

            return Command::FAILURE;
        }

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            $this->logger->info('User already has ROLE_ADMIN.', [
                'email' => $email,
                'user_id' => $user->getId(),
            ]);
            $io->success(sprintf('User "%s" already has ROLE_ADMIN.', $email));

            return Command::SUCCESS;
        }

        $adminRole = $this->roleRepository->findOneBy(['name' => 'ROLE_ADMIN']);
        if ($adminRole === null) {
            $adminRole = (new Role())->setName('ROLE_ADMIN');
            $this->entityManager->persist($adminRole);

            $this->logger->info('ROLE_ADMIN entity was created.', [
                'email' => $email,
            ]);
        }

        $user->addRole($adminRole);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->logger->info('ROLE_ADMIN assigned successfully.', [
            'email' => $email,
            'user_id' => $user->getId(),
        ]);

        $io->success(sprintf('ROLE_ADMIN was assigned to "%s".', $email));

        return Command::SUCCESS;
    }
}