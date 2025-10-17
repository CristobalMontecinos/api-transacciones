
# Laravel API - Prueba Técnica

Este proyecto es una **API RESTful** desarrollada con **Laravel 12**, que permite la gestión de usuarios y transacciones.  
Incluye endpoints para registro, autenticación, envío de transacciones y consulta de estadísticas.  
Además, cuenta con documentación generada automáticamente con **Laravel Scribe**.

---

## Requisitos previos

Asegúrate de tener instaladas las siguientes dependencias:

- PHP >= 8.2
- Composer
- MySQL o MariaDB
- Node.js (opcional, si usas el frontend de Laravel)
- Git

---

## Instalación y configuración

Sigue estos pasos para levantar el proyecto desde cero:

### 1. Clonar el repositorio
#En tu terminal

git clone https://github.com/CristobalMontecinos/api-transacciones.git
cd api-transacciones

### 2. Instalar dependencias
#En tu consola:

composer install

### 3. Configurar el entorno
Se adjunta archivo .env con un una base de datos dockerizada
Para base de datos local sin docker, el .env deberia verse mas o menos asi:

APP_NAME="API Transacciones"
APP_ENV=local
APP_KEY=0
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=api_transacciones
DB_USERNAME=root
DB_PASSWORD=**Aqui poner contraseña del usuario de tu base de datos**

QUEUE_CONNECTION=database

**Una vez hecha la conexión, es importante que crees la base de datos 'api_transacciones' utilizando:**
CREATE DATABASE api_transacciones

### 4. Generar la clave de la aplicación
#En tu consola:

php artisan key:generate


### 5. Ejecutar migraciones y seeders
Esto creará las tablas y cargará datos de ejemplo (usuarios y transacciones).
#En tu consola:

php artisan migrate --seed


### 6. Levantar el servidor de desarrollo
#En tu consola:
php artisan serve


El proyecto estará disponible en:  
http://127.0.0.1:8000

---

## Documentación de la API

La documentación se genera automáticamente con **Laravel Scribe**.

### Generar o actualizar la documentación
#En tu consola:

php artisan scribe:generate


### Visualizar la documentación
Accede en tu navegador a:  
http://127.0.0.1:8000/docs

---

## Usuarios iniciales (Seeder)

Al ejecutar `php artisan migrate --seed`, se crearán tres usuarios por defecto:

| Nombre     | Email                  | Contraseña  |
|------------|------------------------|-------------|
| Cristobal  | cristobal@example.com  | 123456      |
| Maria      | maria@example.com      | 123456      |
| Juan       | juan@example.com       | 123456      |

También se generan **10 transacciones aleatorias** entre estos usuarios.

---

## Endpoints principales

Algunos endpoints disponibles (ver todos en `/docs`):

| Método | Ruta                        | Descripción |
|--------|-----------------------------|--------------|
| POST   | `/api/login`                | Inicia sesión y obtiene el token para el resto de peticiones|
| POST   | `/api/register`             | Registra un nuevo usuario |
| GET    | `/api/transactions`         | Lista las transacciones |
| POST   | `/api/transactions/send`    | Envía una transacción |
| GET    | `/api/statistics`           | Muestra estadísticas de usuario |

---

## Tecnologías utilizadas

- **Laravel 12**
- **Sanctum** (autenticación API)
- **Laravel Scribe** (documentación automática)
- **MySQL**
- **Faker** (datos aleatorios para seeder)

---

## Autor

**Cristóbal Montecinos**  
Proyecto de prueba técnica  
cristobal.montecinos04@gmail.com
