# Facebook API · Taller Backend

API REST simplificada inspirada en Facebook, construida con **Laravel**: publicaciones con varias imágenes, comentarios y reacciones de "Me gusta".

Taller del curso **Arquitectura y Desarrollo Backend**, Universidad Autónoma de Bucaramanga. El enunciado completo está en [`docs/taller-api-rest-facebook-laravel.md`](docs/taller-api-rest-facebook-laravel.md).

## Contexto: de monolito a frontend + backend

En la primera parte del curso hicimos aplicaciones monolíticas en PHP, donde el mismo servidor procesaba los datos y generaba el HTML. En este taller separamos esas responsabilidades. Este proyecto es solo el **backend**: no tiene vistas, recibe peticiones HTTP y responde **JSON** con códigos de estado estándar. Cualquier cliente (una app web, una app móvil o Postman) puede consumirlo sin saber cómo está construido por dentro.

```
Cliente (Postman / Frontend) ──HTTP──► routes/api.php ──► Controller ──► Modelo Eloquent ──► SQLite
                             ◄──JSON (200, 201, 204, 404, 422)──┘
```

## Requisitos

| Herramienta | Versión |
|---|---|
| PHP | 8.3 o superior (probado con 8.5) |
| Composer | 2.x |
| Laravel | 13.x |
| Base de datos | SQLite |
| Postman | Última versión |

> **¿Por qué Laravel 13 y no 10.x/11.x?** El taller pide 10.x u 11.x, pero Composer bloquea la instalación de Laravel 11 porque todas sus versiones tienen avisos de seguridad publicados (ya no tiene soporte). Laravel 13 conserva la misma estructura. Igual que en la 11, `routes/api.php` se crea con `php artisan install:api`.

## Instalación

```bash
git clone <url-del-repo> facebook-api
cd facebook-api

composer install
cp .env.example .env
php artisan key:generate

touch database/database.sqlite   # DB_CONNECTION=sqlite ya viene en .env
php artisan migrate
php artisan storage:link          # crea public/storage → storage/app/public

php artisan serve                 # http://127.0.0.1:8000
```

## Modelo de datos

- `posts`: id, title, content, likes_count, timestamps
- `post_images`: id, post_id (FK, cascade), image_path, timestamps
- `comments`: id, post_id (FK, cascade), author, content, timestamps

Relaciones: `Post` **hasMany** `PostImage` y `Comment`. `PostImage` y `Comment` **belongsTo** `Post`.

## Endpoints

Todas las rutas usan el prefijo `/api`. Se recomienda enviar el header `Accept: application/json`.

| Método | Ruta | Content-Type | Descripción | Body | Código |
|---|---|---|---|---|---|
| POST | `/api/posts` | multipart/form-data | Crear publicación con fotos | `title`, `content`, `images[]` | 201 |
| GET | `/api/posts` | application/json | Listar publicaciones con imágenes y comentarios | — | 200 |
| GET | `/api/posts/{id}` | application/json | Ver un post individual | — | 200 |
| POST | `/api/posts/{id}/comments` | application/json | Agregar comentario | `author`, `content` | 201 |
| GET | `/api/posts/{id}/comments` | application/json | Listar comentarios de un post | — | 200 |
| POST | `/api/posts/{id}/like` | application/json | Incrementar "Me gusta" | — | 200 |
| DELETE | `/api/posts/{id}` | application/json | Eliminar un post y sus imágenes | — | 204 |

Errores:
- **404**: el post no existe. Responde `{"message": "Recurso no encontrado"}`.
- **422**: falla la validación (falta el título o el contenido, el archivo no es una imagen, `images` no es un arreglo o llegó vacío, una imagen pesa más de 2 MB). Responde con el detalle en `errors`.

Cada imagen se guarda en `storage/app/public/posts` y se devuelve con su `image_url` completa, por ejemplo `http://127.0.0.1:8000/storage/posts/abc.jpg`.

## Pruebas

### Automáticas (PHPUnit)

```bash
php artisan test
```

Están en `tests/Feature/PostApiTest.php` y `tests/Feature/CommentApiTest.php`. Usan SQLite en memoria y un disco de almacenamiento falso, así que no tocan los datos reales.

### Postman

1. En Postman: **Import** → `facebook-api-postman-collection.json`.
2. La colección **Facebook API - Taller Backend** trae las variables `base_url` (`http://127.0.0.1:8000`) y `post_id`.
3. En **Crear publicación con imágenes**, selecciona dos archivos en los campos `images[]`.
4. Ejecuta las peticiones en orden: crear → listar → ver → comentar → like → eliminar. La primera guarda el `id` en `post_id` y cada petición trae tests que validan el código HTTP.

Las capturas de las pruebas van en [`docs/postman/`](docs/postman/).

## Checklist del taller

- [x] Proyecto Laravel creado y corriendo en http://127.0.0.1:8000
- [x] Enlace simbólico de storage creado (`public/storage`)
- [x] Base de datos migrada con tablas `posts`, `post_images` y `comments` (`php artisan migrate:status`)
- [x] `POST /api/posts` crea el post y guarda las imágenes (201)
- [x] `GET /api/posts` lista los posts con `image_url` completa (200)
- [x] `POST /api/posts/{id}/comments` agrega un comentario (201)
- [x] `POST /api/posts/{id}/like` incrementa `likes_count` (200)
- [x] `DELETE /api/posts/{id}` elimina el post y los archivos de imagen (204)
- [x] Colección de Postman exportada (`facebook-api-postman-collection.json`)
- [x] Repositorio en GitHub con README.md

## Estructura principal

```
app/Http/Controllers/PostController.php
app/Http/Controllers/CommentController.php
app/Models/{Post,PostImage,Comment}.php
database/migrations/..._create_{posts,post_images,comments}_table.php
routes/api.php
tests/Feature/{PostApiTest,CommentApiTest}.php
facebook-api-postman-collection.json
docs/
```
