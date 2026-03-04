# Camagru

A web application that allows users to create photos using their webcam, add fun overlays, and share them with the community.

## Features

- **User Authentication**: Register, login, email verification, password reset
- **Photo Editor**: Capture photos from webcam or upload existing images
- **Overlays**: Apply creative overlays to photos
- **Gallery**: Browse all photos with pagination
- **Social Features**: Like and comment on photos
- **Email Notifications**: Get notified when someone comments on your photos
- **Responsive Design**: Works on desktop and mobile devices

## Technology Stack

- **Backend**: PHP 8.2 (no frameworks, standard library only)
- **Database**: MySQL 8.0
- **Frontend**: HTML5, CSS (Tailwind CDN), Vanilla JavaScript
- **Server**: Nginx
- **Containerization**: Docker + docker-compose
- **Image Processing**: GD Library

## Requirements

- Docker
- Docker Compose

## Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd camagru
```

2. Copy environment file:
```bash
cp .env.example .env
```

3. Edit `.env` with your settings (optional for development)

4. Start the containers:
```bash
docker-compose up -d --build
```

5. Generate overlay images (first time only):
```bash
docker-compose exec php php /var/www/html/scripts/generate_overlays.php
```

6. Access the application:
   - **Сайт**: http://localhost:8080
   - **Почта (Mailpit)**: http://localhost:8025

---

## Docker Commands

### Основные команды

```bash
# Запустить все контейнеры
docker-compose up -d

# Запустить с пересборкой (после изменений в Dockerfile)
docker-compose up -d --build

# Остановить все контейнеры
docker-compose down

# Остановить и удалить volumes (ОСТОРОЖНО: удалит БД!)
docker-compose down -v

# Перезапустить все контейнеры
docker-compose restart

# Перезапустить конкретный контейнер
docker-compose restart php
docker-compose restart nginx
docker-compose restart mysql
```

### Логи

```bash
# Все логи
docker-compose logs

# Логи конкретного сервиса
docker-compose logs php
docker-compose logs nginx
docker-compose logs mysql

# Следить за логами в реальном времени
docker-compose logs -f

# Последние 50 строк логов PHP
docker-compose logs php --tail=50
```

### Статус

```bash
# Статус контейнеров
docker-compose ps

# Использование ресурсов
docker stats
```

### Доступ к контейнерам

```bash
# Зайти в PHP контейнер
docker-compose exec php bash

# Зайти в MySQL контейнер
docker-compose exec mysql bash

# Выполнить PHP команду
docker-compose exec php php -v

# Выполнить SQL запрос
docker-compose exec mysql mysql -u camagru_user -pcamagru_password camagru -e "SHOW TABLES;"
```

### Пересборка

```bash
# Пересобрать PHP контейнер
docker-compose build php

# Пересобрать без кэша
docker-compose build --no-cache php

# Пересобрать и запустить
docker-compose up -d --build php
```

### Очистка

```bash
# Удалить неиспользуемые образы
docker image prune

# Удалить все неиспользуемые данные Docker
docker system prune

# Полная очистка (ОСТОРОЖНО!)
docker system prune -a --volumes
```

---

## Troubleshooting

### 502 Bad Gateway
```bash
# Перезапустить PHP и Nginx
docker-compose restart php nginx
```

### Ошибки подключения к БД
```bash
# Проверить логи MySQL
docker-compose logs mysql

# Пересоздать БД (удалит все данные!)
docker-compose down -v
docker-compose up -d
```

### Изменения в коде не применяются
```bash
# Для PHP - изменения применяются сразу (volume mount)
# Для Dockerfile - нужна пересборка:
docker-compose up -d --build
```

### Проверить, что контейнеры видят друг друга
```bash
docker-compose exec php ping -c 3 mysql
docker-compose exec nginx ping -c 3 php
```

## Project Structure

```
camagru/
├── docker/                 # Docker configuration
│   ├── nginx/             # Nginx config
│   ├── php/               # PHP Dockerfile
│   └── mysql/             # Database init script
├── public/                # Web root
│   ├── index.php          # Entry point
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript files
│   └── uploads/           # User uploaded images
├── src/                   # PHP source code
│   ├── Core/              # Framework core classes
│   ├── Controllers/       # Request handlers
│   ├── Models/            # Database models
│   └── Services/          # Business logic
├── templates/             # View templates
├── assets/                # Static assets
│   └── overlays/          # Overlay PNG images
├── config/                # Configuration
└── scripts/               # Utility scripts
```

## API Endpoints

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/register` - User registration
- `POST /api/auth/logout` - User logout
- `POST /api/auth/forgot-password` - Request password reset
- `POST /api/auth/reset-password` - Reset password

### User
- `GET /api/user/profile` - Get user profile
- `POST /api/user/update` - Update user profile

### Gallery
- `GET /api/gallery` - Get gallery images (paginated)
- `POST /api/gallery/like/{id}` - Toggle like on image
- `GET /api/gallery/comments/{id}` - Get comments for image
- `POST /api/gallery/comment/{id}` - Add comment

### Editor
- `POST /api/editor/capture` - Save webcam capture
- `POST /api/editor/upload` - Upload image
- `DELETE /api/editor/image/{id}` - Delete image
- `GET /api/editor/my-images` - Get user's images

## Security Features

- CSRF protection on all forms
- Password hashing with bcrypt
- Prepared statements for all database queries (SQL injection prevention)
- HTML escaping for output (XSS prevention)
- Secure session management
- File upload validation

## Development

### Adding New Overlays

1. Create a PNG image with transparency (640x480 recommended)
2. Place it in `assets/overlays/`
3. The overlay will automatically appear in the editor

### Database Changes

Edit `docker/mysql/init.sql` and rebuild:
```bash
docker-compose down -v
docker-compose up -d
```

## License

This project is for educational purposes (42 School).
