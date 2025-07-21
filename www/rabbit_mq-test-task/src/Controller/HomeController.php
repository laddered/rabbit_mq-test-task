<?php

namespace App\Controller;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        if ($this->getUser() === null) {
            if ($request->isMethod('POST')) {
                $connection = new \PhpAmqpLib\Connection\AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
                $channel = $connection->channel();
                $channel->queue_declare('demo_queue', false, false, false, false);
                $msg = new \PhpAmqpLib\Message\AMQPMessage('Message from HomeController!');
                $channel->basic_publish($msg, '', 'demo_queue');
                $channel->close();
                $connection->close();
                $this->addFlash('success', 'Сообщение отправлено в RabbitMQ!');
                return $this->redirectToRoute('home');
            }
            return $this->render('home/guest.html.twig');
        }
        // Для авторизованных пользователей делаем редирект на дашборд
        return $this->redirectToRoute('dashboard');
    }

    #[Route('/login', name: 'login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render('home/login.html.twig', [
            'error' => $error,
            'last_username' => $lastUsername
        ]);
    }

    #[Route('/register', name: 'register', methods: ['GET', 'POST'])]
    public function register(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, UserAuthenticatorInterface $userAuthenticator, LoginFormAuthenticator $authenticator): Response
    {
        $error = null;
        $last_username = '';
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $passwordConfirm = $request->request->get('password_confirm');
            $last_username = $email;
            if (!$email || !$password || $password !== $passwordConfirm) {
                $error = 'Проверьте правильность заполнения полей.';
            } else {
                // Проверка на существование пользователя
                $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
                if ($existing) {
                    $error = 'Пользователь с таким email уже существует.';
                } else {
                    $user = new User();
                    $user->setEmail($email);
                    $user->setPassword($passwordHasher->hashPassword($user, $password));
                    $em->persist($user);
                    $em->flush();
                    // Логиним пользователя
                    return $userAuthenticator->authenticateUser($user, $authenticator, $request);
                }
            }
        }
        return $this->render('home/register.html.twig', [
            'error' => $error,
            'last_username' => $last_username
        ]);
    }

    #[Route('/send-rabbitmq', name: 'send_rabbitmq', methods: ['POST'])]
    public function sendRabbitMQ(Request $request): Response
    {
        // Можно добавить проверку авторизации, если нужно
        $message = $request->request->get('message', 'Message from JS!');
        try {
            $connection = new \PhpAmqpLib\Connection\AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
            $channel = $connection->channel();
            $channel->queue_declare('demo_queue', false, false, false, false);
            $msg = new \PhpAmqpLib\Message\AMQPMessage($message);
            $channel->basic_publish($msg, '', 'demo_queue');
            $channel->close();
            $connection->close();
            return $this->json(['success' => true, 'message' => 'Сообщение отправлено в RabbitMQ!']);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()], 500);
        }
    }
} 