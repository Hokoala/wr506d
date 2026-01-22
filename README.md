# WR506D

## Jean Michel LE TP B - mmi23e10 

API REST et GraphQL pour la gestion de films, acteurs et catégories.

## Stack technique

- **PHP** 8.2+
- **Symfony** 7.3
- **API Platform** 4.2.6
- **Doctrine ORM** 3.5
- **JWT Authentication** (lexik/jwt-authentication-bundle)
- **GraphQL** (webonyx/graphql-php)

## Installation

### Prérequis

- PHP 8.2 ou supérieur
- Composer
- MySQL/MariaDB ou PostgreSQL
- OpenSSL (pour les clés JWT)

### Étapes d'installation

```bash
# 1. Cloner le repository
git clone https://github.com/Hokoala/wr506d.git
cd wr506d

# 2. Installer les dépendances
composer install

# 3. Copier le fichier d'environnement
cp .env .env.local

# 4. Configurer la base de données dans .env.local
# DATABASE_URL="mysql://user:password@127.0.0.1:3306/wr506d?serverVersion=8.0"

# 5. Générer les clés JWT
php bin/console lexik:jwt:generate-keypair

# 6. Créer la base de données et exécuter les migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 7. (Optionnel) Charger les fixtures
php bin/console doctrine:fixtures:load

# 8. Lancer le serveur
symfony server:start
```

### Installation avec Docker

#### 1. Créer le fichier `docker-compose.yml`

```yaml
version: '3.8'
services:
    web:
        image: mmi3docker/symfony-2024
        container_name: symfony-web
        hostname: symfony-web
        restart: always
        ports:
            - 8319:80
        depends_on:
            - db
        volumes:
            - ./www/:/var/www/
            - ./sites/:/etc/apache2/sites-enabled/

    db:
        image: mariadb:10.8
        container_name: symfony-db
        hostname: symfony-db
        restart: always
        volumes:
            - db-volume:/var/lib/mysql
        environment:
            MYSQL_ROOT_PASSWORD: PASSWORD
            MYSQL_DATABASE: symfony
            MYSQL_USER: symfony
            MYSQL_PASSWORD: PASSWORD

    phpmyadmin:
        image: phpmyadmin/phpmyadmin
        container_name: symfony-adminsql
        hostname: symfony-adminsql
        restart: always
        ports:
            - 8080:80
        environment:
            PMA_HOST: db
            MYSQL_ROOT_PASSWORD: PASSWORD
            MYSQL_USER: symfony
            MYSQL_PASSWORD: PASSWORD
            MYSQL_DATABASE: symfony

    maildev:
        image: maildev/maildev
        container_name: symfony-mail
        hostname: symfony-mail
        command: bin/maildev --web 1080 --smtp 1025 --hide-extensions STARTTLS
        restart: always
        ports:
            - 1080:1080

volumes:
    db-volume:
```

#### 2. Lancer les conteneurs

```bash
docker-compose up -d
docker exec -ti symfony-web /root/init.sh
```

#### 3. Configuration Apache (sites/000-default.conf)

```apache
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html/wr506d/public

    <Directory /var/www/html/wr506d/public>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Order allow,deny
        allow from all
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

#### 4. Fichier .htaccess (public/.htaccess)

```apache
DirectoryIndex index.php

<IfModule mod_negotiation.c>
    Options -MultiViews
</IfModule>

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI}::$0 ^(/.+)/(.*)::\2$
    RewriteRule .* - [E=BASE:%1]
    RewriteCond %{HTTP:Authorization} .+
    RewriteRule ^ - [E=HTTP_AUTHORIZATION:%0]
    RewriteCond %{ENV:REDIRECT_STATUS} =""
    RewriteRule ^index\.php(?:/(.*)|$) %{ENV:BASE}/$1 [R=301,L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ %{ENV:BASE}/index.php [L]
</IfModule>

<IfModule !mod_rewrite.c>
    <IfModule mod_alias.c>
        RedirectMatch 307 ^/$ /index.php/
    </IfModule>
</IfModule>
```

#### 5. Accès aux services

| Service | URL |
|---------|-----|
| API | http://localhost:8319 |
| phpMyAdmin | http://localhost:8080 |
| MailDev | http://localhost:1080 |

## Configuration

### Variables d'environnement (.env.local)

```env
# Base de données
DATABASE_URL="mysql://user:password@127.0.0.1:3306/wr506d"

# JWT
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase
```

## Authentification

### JWT (JSON Web Token)

#### Obtenir un token

```http
POST /auth
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password"
}
```

**Réponse :**
```json
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

#### Utiliser le token

```http
GET /api/movies
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
```

### API Key

Alternative au JWT pour l'authentification :

```http
GET /api/movies
X-API-KEY: your_api_key
```

### Authentification à deux facteurs (2FA)

| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/api/2fa/setup` | POST | Génère le secret et QR code |
| `/api/2fa/enable` | POST | Active la 2FA avec un code |
| `/api/2fa/disable` | POST | Désactive la 2FA |
| `/api/2fa/status` | GET | Vérifie le statut 2FA |
| `/api/2fa/verify` | POST | Vérifie un code TOTP |
| `/api/2fa/backup-codes/regenerate` | POST | Régénère les codes de secours |

## Endpoints REST

### Documentation interactive

- **Swagger UI** : `GET /api/docs`
- **JSON-LD** : `GET /api/docs.jsonld`

### Movies (Films)

| Méthode | Endpoint | Rôle requis | Description |
|---------|----------|-------------|-------------|
| GET | `/api/movies` | ROLE_USER | Liste des films |
| GET | `/api/movies/{id}` | ROLE_USER | Détail d'un film |
| POST | `/api/movies` | ROLE_ADMIN | Créer un film |
| PUT | `/api/movies/{id}` | ROLE_ADMIN | Modifier un film |
| PATCH | `/api/movies/{id}` | ROLE_ADMIN | Modifier partiellement |
| DELETE | `/api/movies/{id}` | ROLE_ADMIN | Supprimer un film |

### Actors (Acteurs)

| Méthode | Endpoint | Rôle requis | Description |
|---------|----------|-------------|-------------|
| GET | `/api/actors` | ROLE_USER | Liste des acteurs |
| GET | `/api/actors/{id}` | ROLE_USER | Détail d'un acteur |
| POST | `/api/actors` | ROLE_ADMIN | Créer un acteur |
| PUT | `/api/actors/{id}` | ROLE_ADMIN | Modifier un acteur |
| PATCH | `/api/actors/{id}` | ROLE_ADMIN | Modifier partiellement |
| DELETE | `/api/actors/{id}` | ROLE_ADMIN | Supprimer un acteur |

### Categories (Catégories)

| Méthode | Endpoint | Rôle requis | Description |
|---------|----------|-------------|-------------|
| GET | `/api/categories` | ROLE_USER | Liste des catégories |
| GET | `/api/categories/{id}` | ROLE_USER | Détail d'une catégorie |
| POST | `/api/categories` | ROLE_ADMIN | Créer une catégorie |
| PUT | `/api/categories/{id}` | ROLE_ADMIN | Modifier une catégorie |
| PATCH | `/api/categories/{id}` | ROLE_ADMIN | Modifier partiellement |
| DELETE | `/api/categories/{id}` | ROLE_ADMIN | Supprimer une catégorie |

### Media (Upload de fichiers)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/media_objects` | Upload d'un fichier (multipart/form-data) |
| GET | `/api/media_objects/{id}` | Récupérer les infos d'un média |

**Exemple d'upload :**
```bash
curl -X POST /api/media_objects \
  -H "Authorization: Bearer {token}" \
  -F "file=@image.jpg"
```

### Autres endpoints

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/me` | Informations de l'utilisateur connecté |
| GET | `/api/directors` | Liste des réalisateurs |
| GET | `/api/comments` | Liste des commentaires |

## GraphQL

### Endpoint

```
POST /api/graphql
```

### Exemple de requête

```graphql
query {
  movies {
    edges {
      node {
        id
        title
        releaseDate
        category {
          name
        }
        actors {
          edges {
            node {
              firstName
              lastName
            }
          }
        }
      }
    }
  }
}
```

### Mutation (création)

```graphql
mutation {
  createMovie(input: {
    title: "Mon Film"
    releaseDate: "2024-01-15"
    duration: 120
  }) {
    movie {
      id
      title
    }
  }
}
```

## Rate Limiting

L'API implémente un système de limitation de requêtes :

- **Utilisateurs anonymes** : 500 requêtes/minute (par IP)
- **Utilisateurs authentifiés** : 500 tokens, recharge de 100 tokens/2min

**Headers de réponse :**
```
X-RateLimit-Limit: 500
X-RateLimit-Remaining: 499
X-RateLimit-Reset: 1234567890
```

**En cas de dépassement :** HTTP 429 Too Many Requests

## Rôles et permissions

| Rôle | Permissions |
|------|-------------|
| ROLE_USER | Lecture seule (GET) |
| ROLE_ADMIN | Lecture + Écriture (GET, POST, PUT, PATCH, DELETE) |

## Tests et qualité de code

```bash
# Tests unitaires
php bin/phpunit

# Vérification PSR-2
vendor/bin/phpcs --standard=PSR2 src/

# Analyse statique
vendor/bin/phpstan analyse src/ --memory-limit=512M

# PHP Mess Detector
vendor/bin/phpmd src/ text phpmd.xml
```

## CI/CD

Le projet utilise GitHub Actions pour l'intégration continue :

- Exécution des tests PHPUnit
- Vérification du standard PSR-2 (phpcs)
- Analyse statique (PHPStan)
- Détection de code problématique (PHPMD)

Voir `.github/workflows/testing.yml`

## Structure du projet

```
src/
├── Controller/          # Contrôleurs personnalisés (2FA, Me)
├── Entity/              # Entités Doctrine (Movie, Actor, Category...)
├── Repository/          # Repositories Doctrine
├── Security/            # Authenticators (JWT, API Key)
├── Service/             # Services métier (TwoFactorService)
├── State/               # Processors API Platform
├── EventSubscriber/     # Listeners (Rate Limiter)
└── Serializer/          # Normalizers personnalisés
```

## Auteurs

- **Hokoala** - Développement initial

## Licence

Projet sous licence propriétaire.
