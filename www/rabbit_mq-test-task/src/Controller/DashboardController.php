<?php

namespace App\Controller;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(Request $request): Response
    {
        $messageSent = false;
        if ($request->isMethod('POST')) {
            $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
            $channel = $connection->channel();
            $channel->queue_declare('demo_queue', false, false, false, false);
            $msg = new AMQPMessage('Hello from DashboardController! User: ' . $this->getUser()?->getUserIdentifier());
            $channel->basic_publish($msg, '', 'demo_queue');
            $channel->close();
            $connection->close();
            $messageSent = true;
        }
        return $this->render('dashboard/index.html.twig', [
            'messageSent' => $messageSent
        ]);
    }
} 