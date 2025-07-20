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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
        $channel = $connection->channel();
        $channel->queue_declare('demo_queue', false, false, false, false);

        $output->writeln('<info>Waiting for messages. To exit press CTRL+C</info>');

        $callback = function (AMQPMessage $msg) use ($output) {
            $id = (int)$msg->getBody();
            $message = $this->em->getRepository(Message::class)->find($id);
            if ($message) {
                $output->writeln("<comment>Processing message #$id...</comment>");
                $message->setStatus('processing');
                $message->setUpdatedAt(new \DateTimeImmutable());
                $this->em->flush();
                sleep(5); // Симуляция долгой обработки
                $message->setStatus('done');
                $message->setUpdatedAt(new \DateTimeImmutable());
                $this->em->flush();
                $output->writeln("<info>Message #$id processed!</info>");
            } else {
                $output->writeln("<error>Message with id #$id not found!</error>");
            }
        };

        $channel->basic_consume('demo_queue', '', false, true, false, false, $callback);

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
        return Command::SUCCESS;
    }
} 