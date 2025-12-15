<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use DateTimeImmutable;

#[AsCommand(
    name: 'app:generate-api-key',
    description: 'Génère une clé API pour un utilisateur',
)]
class GenerateApiKeyCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email de l\'utilisateur')
            ->setHelp('Cette commande génère une clé API pour un utilisateur spécifique');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        // Récupérer l'utilisateur
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error(sprintf('Aucun utilisateur trouvé avec l\'email : %s', $email));
            return Command::FAILURE;
        }

        // Générer la clé API
        try {
            // 1. Générer 32 bytes aléatoires
            $randomBytes = random_bytes(32);

            // 2. Convertir en hexadécimal
            $hexString = bin2hex($randomBytes);

            // 3. Hasher en SHA256
            $apiKeyHash = hash('sha256', $hexString);

            // Extraire le préfixe (16 premiers caractères de la clé hex)
            $apiKeyPrefix = substr($hexString, 0, 16);

            // Enregistrer dans l'entité User
            $user->setApiKeyHash($apiKeyHash);
            $user->setApiKeyPrefix($apiKeyPrefix);
            $user->setApiKeyEnabled(true);
            $user->setApiKeyCreatedAt(new DateTimeImmutable());


            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Affichage des informations
            $io->success('Clé API générée avec succès !');
            $io->section('Informations de la clé API');
            $io->table(
                ['Champ', 'Valeur'],
                [
                    ['Utilisateur', $user->getEmail()],
                    ['Clé API (à communiquer)', $hexString],
                    ['Préfixe (stocké)', $apiKeyPrefix],
                    ['Hash (stocké)', $apiKeyHash],
                    ['Statut', $user->isApiKeyEnabled() ? 'Activée' : 'Désactivée'],
                    ['Date de création', $user->getApiKeyCreatedAt()->format('Y-m-d H:i:s')],
                ]
            );

            $io->warning([
                'IMPORTANT : La clé API complète ci-dessus doit être communiquée au client.',
                'Elle ne pourra plus être récupérée par la suite.',
                'Seul le hash est stocké en base de données pour des raisons de sécurité.'
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de la génération de la clé API : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
