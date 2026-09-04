FROM php:8.4-cli-alpine
WORKDIR /app
COPY . .
ENV PHP_CLI_SERVER_WORKERS=4
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "router.php"]
