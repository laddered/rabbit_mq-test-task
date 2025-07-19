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
            return $this->render('home/guest.html.twig');
        }
        $messageSent = false;
        if ($request->isMethod('POST')) {
            $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
            $channel = $connection->channel();
            $channel->queue_declare('demo_queue', false, false, false, false);
            $msg = new AMQPMessage('Hello from HomeController!');
            $channel->basic_publish($msg, '', 'demo_queue');
            $channel->close();
            $connection->close();
            $messageSent = true;
        }
        return $this->render('home/index.html.twig', [
            'messageSent' => $messageSent
        ]);
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
} 