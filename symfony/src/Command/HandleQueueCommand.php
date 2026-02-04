<?php

namespace App\Command;

use App\Service\PdfQueueProcessor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:handle-queue', description: 'Traite les tâches PDF en attente')]
final class HandleQueueCommand extends Command
{
    public function __construct(
        private readonly PdfQueueProcessor $processor,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $processed = $this->processor->processPending(20);
        $io->success(sprintf('%d tâche(s) traitée(s).', count($processed)));
        return Command::SUCCESS;
    }
}
