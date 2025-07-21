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
            $connection = new \PhpAmqpLib\Connection\AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
            $channel = $connection->channel();
            $channel->queue_declare('demo_queue', false, false, false, false);
            $msg = new \PhpAmqpLib\Message\AMQPMessage('Message from DashboardController!');
            $channel->basic_publish($msg, '', 'demo_queue');
            $channel->close();
            $connection->close();
            $this->addFlash('success', 'Сообщение отправлено в RabbitMQ!');
            return $this->redirectToRoute('home');
        }
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $repo = $em->getRepository(Message::class);
        $messages = $repo->findBy([], ['createdAt' => 'DESC'], $limit, $offset);
        $total = $repo->count([]);
        $totalPages = (int)ceil($total / $limit);
        return $this->render('dashboard/index.html.twig', [
            'messages' => $messages,
            'page' => $page,
            'total_pages' => $totalPages
        ]);
    }
} 