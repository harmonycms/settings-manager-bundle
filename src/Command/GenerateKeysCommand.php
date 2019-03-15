<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Command;

use ParagonIE\Paseto\Protocol\Version2;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateKeysCommand extends Command
{
    protected static $defaultName = 'harmony:settings:generate-keys';

    protected function configure(): void
    {
        $this
            ->addArgument(
                'private_key_path',
                InputArgument::REQUIRED,
                'Private key path to store'
            )
            ->addArgument(
                'public_key_path',
                InputArgument::REQUIRED,
                'Public key path to store'
            )
            ->setDescription('Generate asymmetric private and public keys.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Settings generate keys');

        $asymmetricKey = Version2::generateAsymmetricSecretKey();
        $rawPrivate = $asymmetricKey->raw();
        $rawPublic = $asymmetricKey->getPublicKey()->raw();

        $writePrivateResult = file_put_contents($input->getArgument('private_key_path'), $rawPrivate);
        if (false === $writePrivateResult) {
            $io->error('Could not write to private key path');
            return;
        }

        $writePublicResult = file_put_contents($input->getArgument('public_key_path'), $rawPublic);
        if (false === $writePublicResult) {
            $io->error('Could not write to public key path');
            return;
        }

        $io->success('Saved generated keys');
    }
}
