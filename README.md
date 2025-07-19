# rabbit_mq-test-task

Тестовый проект на Symfony с использованием PostgreSQL и RabbitMQ для обработки заявок через очередь. Весь проект запускается через Docker.

## 📦 Технологии

- Symfony 6
- PHP 8.3
- PostgreSQL
- RabbitMQ
- Docker + Docker Compose
- Doctrine ORM
- Symfony Messenger

## 🚀 Быстрый старт

1. Клонируйте репозиторий:

```bash
git clone https://github.com/yourusername/rabbit_mq-test-task.git
cd rabbit_mq-test-task
```
2. Поднимите контейнеры:
```bash
docker-compose up -d --build
```