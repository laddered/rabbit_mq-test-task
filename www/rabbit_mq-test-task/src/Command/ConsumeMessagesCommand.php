<?php

namespace App\Command;

use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'app:consume-messages',
    description: 'Consume messages from RabbitMQ and process them.'
)]
class ConsumeMessagesCommand extends Command
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure(): void
    {
        parent::configure();
        $this->addOption(
            'daemon',
            'd',
            InputOption::VALUE_NONE,
            'Run as a daemon (keep listening for messages)'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isDaemon = $input->getOption('daemon');
        try {
            $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
            $channel = $connection->channel();
            $channel->queue_declare('demo_queue', false, false, false, false);

            $output->writeln('<info>Waiting for messages. To exit press CTRL+C</info>');

            $callback = function (AMQPMessage $msg) use ($output) {
                try {
                    $content = $msg->getBody();
                    $output->writeln("<comment>Получено сообщение: $content</comment>");
                    $message = new Message();
                    $message->setContent($content);
                    $message->setStatus('queued');
                    $message->setCreatedAt(new \DateTimeImmutable());
                    $this->em->persist($message);
                    $this->em->flush();
                    $output->writeln("<info>Message сохранён в базе с ID: {$message->getId()}</info>");
                } catch (\Throwable $e) {
                    $output->writeln('<error>Ошибка обработки сообщения: ' . $e->getMessage() . '</error>');
                }
            };

            $channel->basic_consume('demo_queue', '', false, true, false, false, $callback);

            if ($isDaemon) {
                // Режим демона: бесконечно ждём сообщения, не падаем при таймауте
                while (count($channel->callbacks)) {
                    try {
                        $channel->wait(null, false, 5); // 5 секунд таймаут
                    } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
                        // Просто продолжаем ждать
                    }
                }
            } else {
                // Обычный режим: ждём только одно сообщение
                $channel->wait();
            }

            $channel->close();
            $connection->close();
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('<error>Ошибка RabbitMQ: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
} 