<?php

namespace App\Command;

use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:process-queued-messages',
    description: 'Обрабатывает все сообщения со статусом queued.'
)]
class ProcessQueuedMessagesCommand extends Command
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repo = $this->em->getRepository(Message::class);
        $queuedMessages = $repo->findBy(['status' => 'queued']);

        if (empty($queuedMessages)) {
            $output->writeln('<info>Нет сообщений со статусом queued.</info>');
            return Command::SUCCESS;
        }

        foreach ($queuedMessages as $message) {
            $output->writeln("<comment>Обработка сообщения #{$message->getId()}: {$message->getContent()}</comment>");
            $message->setStatus('processing');
            $this->em->flush();
            sleep(2); // Имитация обработки
            $message->setStatus('done');
            $this->em->flush();
            $output->writeln("<info>Сообщение #{$message->getId()} обработано!</info>");
        }

        return Command::SUCCESS;
    }
} 