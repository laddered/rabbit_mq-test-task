FROM node:20-alpine

# Включаем corepack и активируем yarn
RUN corepack enable && corepack prepare yarn@stable --activate

# Устанавливаем рабочую директорию в проекте (путь внутри контейнера)
WORKDIR /var/www/rabbit_mq-test-task

# Копируем package.json и yarn.lock для установки зависимостей
COPY ./www/rabbit_mq-test-task/package.json ./ 
COPY ./www/rabbit_mq-test-task/yarn.lock ./ 

# Устанавливаем зависимости
RUN yarn install

# Копируем весь исходный код (чтобы yarn encore видел файлы)
COPY ./www/rabbit_mq-test-task ./ 

# Запускаем сборку ассетов (dev, можно изменить на prod)
# CMD ["yarn", "run", "encore", "dev"]
CMD ["sh", "-c", "while true; do sleep 1000; done"]
