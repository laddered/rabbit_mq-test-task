<?php

namespace App\Controller;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $content = 'Hello from DashboardController! User: ' . $this->getUser()?->getUserIdentifier();
            $message = new Message();
            $message->setContent($content);
            $message->setStatus('queued');
            $message->setCreatedAt(new \DateTimeImmutable());
            $em->persist($message);
            $em->flush();

            $connection = new \PhpAmqpLib\Connection\AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
            $channel = $connection->channel();
            $channel->queue_declare('demo_queue', false, false, false, false);
            $msg = new \PhpAmqpLib\Message\AMQPMessage((string)$message->getId());
            $channel->basic_publish($msg, '', 'demo_queue');
            $channel->close();
            $connection->close();
            $this->addFlash('success', 'Сообщение отправлено в RabbitMQ!');
            return $this->redirectToRoute('dashboard');
        }
        return $this->render('dashboard/index.html.twig');
    }
} 