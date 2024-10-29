# Fintech API

This is the backend API for the Fintech platform, built with Laravel. It provides endpoints for user authentication, bank account management, transactions, and more.

## Table of Contents

1. [API Documentation](#api-documentation)
2. [Requirements](#requirements)
3. [Installation](#installation)
4. [Configuration](#configuration)
5. [Running the Application](#running-the-application)
6. [Testing](#testing)
7. [Deployment](#deployment)
8. [Contributing](#contributing)
9. [License](#license)

## API Documentation

Comprehensive API documentation is available at:

[https://fintech.softmaxtech.com.ng/public/docs/api](https://fintech.softmaxtech.com.ng/public/docs/api)

### API Base URLs

- Live: `https://fintech.softmaxtech.com.ng/public/api`
- Production: `https://scramble.dedoc.co/api`

### Authentication

This API uses Bearer Token authentication. Include your bearer token in the Authorization header when making requests to protected resources:

```
Authorization: Bearer <your-token-here>
```

## Requirements

- PHP 8.1+
- Composer
- MySQL 5.7+ or PostgreSQL 9.6+
- Redis (for caching and queue)

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/Haeyzed/fintech-api.git
   cd fintech-api
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Copy the example environment file:
   ```bash
   cp .env.example .env
   ```

4. Generate an application key:
   ```bash
   php artisan key:generate
   ```

## Configuration

1. Open the `.env` file and update the database and Redis configurations:

   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database_name
   DB_USERNAME=your_database_username
   DB_PASSWORD=your_database_password

   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```

2. Configure any additional services (e.g., payment gateways, email providers) in the `.env` file.

## Running the Application

1. Run database migrations:
   ```bash
   php artisan migrate
   ```

2. Start the development server:
   ```bash
   php artisan serve
   ```

The API will be available at `http://localhost:8000`.

## Testing

Run the test suite with:

```bash
php artisan test
```

## Deployment

For deployment instructions, please refer to the [Laravel Deployment Documentation](https://laravel.com/docs/deployment).

## Contributing

Please read our [Contributing Guide](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

## License

This project is licensed under the [MIT License](LICENSE).
