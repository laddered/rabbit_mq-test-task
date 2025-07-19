FROM postgres:latest

# Устанавливаем необходимые пакеты
RUN apt-get update && \
    apt-get install -y postgresql-client && \
    rm -rf /var/lib/apt/lists/*