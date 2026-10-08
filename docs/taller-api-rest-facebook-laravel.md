# Creación de una API REST Tipo Facebook con Laravel

> **TALLER PRÁCTICO**
> *Publicaciones · Imágenes · Comentarios · Reacciones*

| | |
|---|---|
| **Docente** | FABIAN ENRIQUE SUAREZ CARVAJAL |
| **Institución** | UNIVERSIDAD AUTONOMA DE BUCARAMANGA |
| **Curso** | ARQUITECTURA Y DESARROLLO BACKEND |

---

## 1. Introducción y Objetivos

En el mundo del desarrollo backend moderno, las **APIs RESTful** son el estándar para comunicar aplicaciones móviles, web y servicios entre sí. En este taller construirás desde cero una API REST simplificada inspirada en Facebook, donde los usuarios podrán crear publicaciones con múltiples imágenes, comentarlas y reaccionar con "Me gusta".

> **Objetivo General**
>
> Desarrollar una API RESTful funcional en Laravel que simule las interacciones básicas de una red social (publicaciones con múltiples fotos, comentarios y reacciones de "Me gusta") y validarla mediante peticiones en Postman.

---

## 2. Requisitos Previos

Antes de iniciar el taller, asegúrate de tener instalado:

| Herramienta | Versión mínima | Verificar con |
|---|---|---|
| PHP | 8.1 o superior | `php -v` |
| Composer | 2.x | `composer -V` |
| Laravel | 10.x o 11.x | `laravel -V` |
| Base de datos | SQLite / MySQL / PostgreSQL | — |
| Postman | Última versión | — |
| Editor de código | VS Code / PHPStorm | — |

> 💡 **Recomendación para principiantes**
>
> Usa **SQLite** para evitar configurar un servidor de base de datos. Laravel lo soporta de fábrica creando un archivo `database/database.sqlite` en la raíz del proyecto.

---

## 3. Especificación de la API REST

La API expondrá los siguientes endpoints bajo el prefijo `/api`:

| Método | Ruta | Content-Type | Descripción | Body / Params | Código |
|---|---|---|---|---|---|
| POST | `/api/posts` | multipart/form-data | Crear publicación con fotos | title, content, images[] | 201 |
| GET | `/api/posts` | application/json | Listar publicaciones con imágenes y comentarios | — | 200 |
| GET | `/api/posts/{id}` | application/json | Ver un post individual | — | 200 |
| POST | `/api/posts/{id}/comments` | application/json | Agregar comentario | author, content | 201 |
| POST | `/api/posts/{id}/like` | application/json | Incrementar "Me gusta" | — | 200 |
| DELETE | `/api/posts/{id}` | application/json | Eliminar un post | — | 204 |

### ¿Qué significa cada Código?

En esta API, cada endpoint no solo indica la ruta que se debe consumir, sino también el tipo de respuesta que debe devolver el servidor. Como estudiante, debes pensar en la API como una conversación entre cliente y servidor: el cliente pide algo y el servidor responde con un código HTTP que describe el resultado.

| Código HTTP | Nombre | ¿Qué significa? | Ejemplo aquí |
|---|---|---|---|
| 200 | OK | La operación fue exitosa y se devuelve información. | Listar posts, ver un post, dar like. |
| 201 | Created | El recurso fue creado correctamente en la base de datos. | Crear un post o un comentario. |
| 204 | No Content | La operación fue exitosa, pero no hay cuerpo de respuesta. | Eliminar un post. |
| 400 | Bad Request | La solicitud enviada es incorrecta o falta información. | Falta el título o el contenido. |
| 404 | Not Found | El recurso solicitado no existe. | Intentar consultar un post inexistente. |
| 422 | Unprocessable Entity | Los datos llegaron, pero no pasan la validación. | Archivo no es imagen o texto vacío. |

La idea central es esta: **200** significa que todo salió bien; **201** que se creó algo nuevo; **204** que se eliminó algo; y **404/422** que hubo un problema con la solicitud o con la información enviada. Por eso, al probar la API en Postman, es importante revisar no solo la respuesta JSON, sino también el código HTTP, porque eso nos dice si la operación fue exitosa o no.

### ¿Qué significa cada método HTTP?

En una API REST, cada método tiene una función específica. Aunque en este taller se usan principalmente **GET**, **POST** y **DELETE**, es importante conocer el propósito general de cada uno:

| Método | Acción principal | Cuándo se usa | En este taller |
|---|---|---|---|
| **GET** | Leer o consultar información. | Cuando el cliente quiere ver datos del servidor. | Listar publicaciones y consultar un post. |
| **POST** | Crear un nuevo recurso o enviar datos para procesarlos. | Cuando se desea registrar algo nuevo, como una publicación o un comentario. | Crear un post, comentar y dar like. |
| **DELETE** | Eliminar un recurso. | Cuando se quiere borrar algo existente. | Eliminar un post. |
| **PUT** | Actualizar completamente un recurso. | Cuando se reemplaza toda la información de un registro. | No se usa en este ejercicio. |
| **PATCH** | Actualizar solo algunos campos. | Cuando se modifica parcialmente un recurso. | No se usa en este ejercicio. |

La regla fácil de recordar es esta: **GET** sirve para pedir datos, **POST** para crear datos, y **DELETE** para quitar datos. En una API REST, el método hace parte del significado de la operación, no solo de la ruta. Por eso, la misma URL puede hacer cosas diferentes según el método que se use.

### Arquitectura de una petición

Cada petición sigue el siguiente flujo dentro de Laravel:

```
┌─────────┐    ┌─────────┐    ┌──────────┐    ┌──────────┐    ┌─────────┐
│ Cliente │───►│  Ruta   │───►│Controller│───►│  Modelo  │───►│ Base de │
│ Postman │    │ api.php │    │  (PHP)   │    │ Eloquent │    │  Datos  │
└─────────┘    └─────────┘    └──────────┘    └──────────┘    └─────────┘
                                   │
                                   ▼
                          ┌─────────────────┐
                          │ Respuesta JSON  │
                          │ (200, 201, 404) │
                          └─────────────────┘
```

---

## 4. Modelo de Datos (Diagrama ER)

La base de datos tendrá tres tablas relacionadas de la siguiente forma:

```
┌──────────────────────┐
│        posts         │
├──────────────────────┤
│ id (PK)              │
│ title                │
│ content              │
│ likes_count          │
│ timestamps           │
└──────────────────────┘
           │ 1
     ┌─────┴─────┐
     │ N         │ N
     ▼           ▼
┌─────────────┐ ┌─────────────┐
│ post_images │ │  comments   │
├─────────────┤ ├─────────────┤
│ id (PK)     │ │ id (PK)     │
│ post_id(FK) │ │ post_id(FK) │
│ image_path  │ │ author      │
│ timestamps  │ │ content     │
└─────────────┘ │ timestamps  │
                └─────────────┘
```

### Relaciones Eloquent

- **Post → PostImage:** Un Post tiene muchos (hasMany) PostImage.
- **Post → Comment:** Un Post tiene muchos (hasMany) Comment.
- **PostImage → Post:** Cada PostImage pertenece (belongsTo) a un Post.
- **Comment → Post:** Cada Comment pertenece (belongsTo) a un Post.

---

## 5. Versionamiento con Git · Entregas por commits

Durante el desarrollo del taller, cada estudiante debe usar **Git** como herramienta de control de versiones. La idea no es solo tener el proyecto final, sino demostrar el proceso de construcción paso a paso.

> 📌 **Importante para la nota**
>
> Para esta evaluación, **lo más importante es que el historial de commits quede claro y documentado**. Cada avance debe quedar registrado con un commit descriptivo, como si fuera una entrega parcial del trabajo realizado.

Se recomienda hacer un commit cada vez que se complete un bloque importante del taller, por ejemplo:

1. **Inicialización del proyecto** → commit: `feat: crear proyecto Laravel`
2. **Modelos y migraciones** → commit: `feat: crear modelos y migraciones para posts, comentarios e imágenes`
3. **Relaciones Eloquent** → commit: `feat: configurar relaciones entre Post, Comment y PostImage`
4. **Controladores** → commit: `feat: implementar CRUD y lógica de likes`
5. **Rutas API** → commit: `feat: registrar endpoints de la API REST`
6. **Pruebas en Postman** → commit: `test: validar endpoints en Postman`
7. **Correcciones y ajustes** → commit: `fix: corregir errores de validación y almacenamiento`

La forma correcta de trabajar es la siguiente:

```bash
# Verificar estado del proyecto
git status

# Agregar cambios al staging
git add .

# Crear commit con mensaje claro
git commit -m "feat: implementar creación de publicaciones con imágenes"
```

Es recomendable además crear una rama principal como `main` y trabajar con commits limpios y ordenados. Si el proyecto tiene varios avances, cada entrega parcial puede reflejar una etapa del taller.

> 💡 **Recomendación docente**
>
> El profesor puede revisar el historial de Git para ver cómo se desarrolló la solución, no solo el resultado final. Por eso, la calidad del historial de commits cuenta como evidencia de trabajo progresivo.

En resumen: **no basta con entregar el proyecto funcionando**; también se debe evidenciar el proceso de construcción mediante commits bien nombrados y organizados. Esto demuestra responsabilidad, orden y comprensión del desarrollo incremental.

---

## 6. Guía de Implementación Paso a Paso

### Paso 1 · Configuración del Proyecto

Crea el proyecto Laravel e inicializa el enlace simbólico del storage:

```bash
composer create-project laravel/laravel facebook-api
cd facebook-api

# Crear el enlace simbólico para acceder a las imágenes
php artisan storage:link
```

> ⚙️ **Configura tu base de datos**
>
> Edita el archivo `.env` y ajusta las variables `DB_CONNECTION`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD`. Para SQLite, crea el archivo `database/database.sqlite` y define `DB_CONNECTION=sqlite`.

### Paso 2 · Modelos y Migraciones

Genera los modelos, migraciones y controladores necesarios:

```bash
php artisan make:model Post -mc
php artisan make:model PostImage -m
php artisan make:model Comment -mc
```

**→ posts**

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('content');
    $table->unsignedInteger('likes_count')->default(0);
    $table->timestamps();
});
```

**→ post_images**

```php
Schema::create('post_images', function (Blueprint $table) {
    $table->id();
    $table->foreignId('post_id')->constrained()->onDelete('cascade');
    $table->string('image_path');
    $table->timestamps();
});
```

**→ comments**

```php
Schema::create('comments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('post_id')->constrained()->onDelete('cascade');
    $table->string('author');
    $table->text('content');
    $table->timestamps();
});
```

Ejecuta las migraciones:

```bash
php artisan migrate
```

### Paso 3 · Relaciones Eloquent

**`App\Models\Post.php`**

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = ['title', 'content', 'likes_count'];

    public function images()
    {
        return $this->hasMany(PostImage::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
```

**`App\Models\PostImage.php`**

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostImage extends Model
{
    protected $fillable = ['post_id', 'image_path'];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
```

**`App\Models\Comment.php`**

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = ['post_id', 'author', 'content'];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
```

### Paso 4 · Lógica de los Controladores

**`App\Http\Controllers\PostController.php`**

```php
<?php
namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with(['images', 'comments'])
            ->latest()
            ->get()
            ->map(function ($post) {
                $post->images->transform(function ($img) {
                    $img->image_url = asset('storage/' . $img->image_path);
                    return $img;
                });
                return $post;
            });

        return response()->json($posts, 200);
    }

    public function show($id)
    {
        $post = Post::with(['images', 'comments'])->findOrFail($id);
        $post->images->transform(function ($img) {
            $img->image_url = asset('storage/' . $img->image_path);
            return $img;
        });
        return response()->json($post, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'content'  => 'required|string',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $post = Post::create([
            'title'   => $request->title,
            'content' => $request->content,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('posts', 'public');
                PostImage::create([
                    'post_id'    => $post->id,
                    'image_path' => $path,
                ]);
            }
        }

        return response()->json($post->load('images'), 201);
    }

    public function like($id)
    {
        $post = Post::findOrFail($id);
        $post->increment('likes_count');

        return response()->json([
            'message'     => 'Me gusta añadido correctamente',
            'likes_count' => $post->likes_count,
        ], 200);
    }

    public function destroy($id)
    {
        $post = Post::findOrFail($id);

        foreach ($post->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $post->delete();
        return response()->json(null, 204);
    }
}
```

**`App\Http\Controllers\CommentController.php`**

```php
<?php
namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index($postId)
    {
        $post = Post::findOrFail($postId);
        return response()->json($post->comments()->latest()->get(), 200);
    }

    public function store(Request $request, $postId)
    {
        $request->validate([
            'author'  => 'required|string|max:100',
            'content' => 'required|string',
        ]);

        $post = Post::findOrFail($postId);

        $comment = $post->comments()->create([
            'author'  => $request->author,
            'content' => $request->content,
        ]);

        return response()->json($comment, 201);
    }
}
```

### Paso 5 · Registro de Rutas API

Edita `routes/api.php`:

```php
<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;

Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{id}', [PostController::class, 'show']);
Route::post('/posts', [PostController::class, 'store']);
Route::delete('/posts/{id}', [PostController::class, 'destroy']);
Route::post('/posts/{id}/like', [PostController::class, 'like']);

Route::get('/posts/{id}/comments', [CommentController::class, 'index']);
Route::post('/posts/{id}/comments', [CommentController::class, 'store']);
```

### Paso 6 · Levantar el Servidor

```bash
php artisan serve
# Servidor corriendo en http://127.0.0.1:8000
```

---

## 7. Guía de Pruebas en Postman

> 📬 **Cómo crear la colección en Postman**
>
> En lugar de importar un archivo externo, vamos a crear la colección manualmente para aprender a organizar las peticiones de la API. Esto ayuda a que el estudiante entienda cómo funciona un entorno de pruebas real en Postman.

### Paso 1 · Crear la colección

1. Abre **Postman**.
2. En el panel izquierdo, haz clic en **Collections**.
3. Presiona el botón **New Collection**.
4. Escribe el nombre: **Facebook API - Taller Backend**.
5. Haz clic en **Create**.

Con esto ya tienes una carpeta donde guardarás todas las peticiones relacionadas con la API. La colección funciona como un contenedor para organizar tus pruebas por funcionalidad.

### Paso 2 · Crear variables de entorno

Para evitar escribir la misma URL muchas veces, crea una variable llamada `{{base_url}}` con este valor:

```
http://127.0.0.1:8000
```

Luego en cada request puedes usar la URL completa como:

```
{{base_url}}/api/posts
```

Esto hace más fácil reusar la colección y cambiar la dirección si luego se despliega el proyecto en otro servidor.

### Paso 3 · Crear la primera petición

Dentro de la colección, haz clic en **Add Request** y crea esta petición:

| Campo | Valor |
|---|---|
| **Nombre** | Crear publicación con imágenes |
| **Método** | POST |
| **URL** | `{{base_url}}/api/posts` |
| **Headers** | `Accept: application/json` |
| **Body** | form-data |
| **title (Text)** | Mi primera publicación |
| **content (Text)** | Hola a todos, comparto fotos de mi viaje. |
| **images[] (File)** | Selecciona foto 1 |
| **images[] (File)** | Selecciona foto 2 |
| **Resultado esperado** | 201 Created |

Explicación: esta petición crea un nuevo post y guarda archivos en el storage del proyecto. Como se envía con `multipart/form-data`, Postman permite subir archivos reales y también datos de texto.

### Prueba 1 · Crear Publicación con Imágenes

| Campo | Valor |
|---|---|
| **Método** | POST |
| **URL** | `{{base_url}}/api/posts` |
| **Headers** | `Accept: application/json` |
| **Body** | form-data |
| **title (Text)** | Mi primera publicación |
| **content (Text)** | Hola a todos, comparto fotos de mi viaje. |
| **images[] (File)** | Selecciona foto 1 |
| **images[] (File)** | Selecciona foto 2 |
| **Resultado esperado** | 201 Created |

### Prueba 2 · Listar Publicaciones

Dentro de la misma colección, agrega otra petición:

| Campo | Valor |
|---|---|
| **Nombre** | Listar publicaciones |
| **Método** | GET |
| **URL** | `{{base_url}}/api/posts` |
| **Headers** | `Accept: application/json` |
| **Resultado esperado** | 200 OK con array de posts |

Explicación: esta petición solicita todos los posts almacenados. El servidor responde con un JSON que incluye el contenido del post, sus comentarios e imágenes.

### Prueba 3 · Agregar Comentario

| Campo | Valor |
|---|---|
| **Nombre** | Agregar comentario |
| **Método** | POST |
| **URL** | `{{base_url}}/api/posts/1/comments` |
| **Headers** | `Accept: application/json`, `Content-Type: application/json` |
| **Body (raw JSON)** | `{"author": "Carlos Gómez", "content": "¡Qué buenas fotos!"}` |
| **Resultado esperado** | 201 Created |

Explicación: se usa **POST** porque se está creando un nuevo comentario asociado a un post existente. El servidor valida que tanto el nombre del autor como el contenido estén completos.

### Prueba 4 · Dar Me Gusta

| Campo | Valor |
|---|---|
| **Nombre** | Dar like a publicación |
| **Método** | POST |
| **URL** | `{{base_url}}/api/posts/1/like` |
| **Body** | No requiere |
| **Resultado esperado** | 200 OK con likes_count incrementado |

Explicación: aunque no se envían datos en el cuerpo, el servidor realiza una operación de actualización: incrementa el contador de likes del post.

### Prueba 5 · Eliminar Publicación

| Campo | Valor |
|---|---|
| **Nombre** | Eliminar publicación |
| **Método** | DELETE |
| **URL** | `{{base_url}}/api/posts/1` |
| **Resultado esperado** | 204 No Content |

Explicación: esta solicitud borra el post y también elimina sus imágenes asociadas del storage. El servidor responde con **204** porque la operación fue exitosa pero no devuelve contenido en el cuerpo.

> 💡 **Recomendación para el estudiante**
>
> Crea cada petición dentro de la misma colección y prueba una por una. No intentes ejecutar todo al mismo tiempo. Primero crea un post, luego lista, luego comenta, luego da like y finalmente elimina. Así aprendes el flujo real de una API REST.

### Exportar la colección y guardarla en Git

Una vez termines de probar todas las peticiones, debes exportar la colección creada en Postman para dejar evidencia del trabajo realizado.

1. En Postman, abre la colección **Facebook API - Taller Backend**.
2. Haz clic en el botón **Export**.
3. Selecciona el formato **Collection v2.1**.
4. Guarda el archivo con un nombre claro, por ejemplo: `facebook-api-postman-collection.json`.
5. En tu repositorio local, añade el archivo al proyecto.
6. Haz un commit exclusivo para la colección exportada.

```bash
# ejemplo de commit para la colección exportada
git add facebook-api-postman-collection.json
git commit -m "test: exportar colección de Postman"
```

Este commit es importante porque demuestra que el estudiante no solo construyó la API, sino que también validó cada endpoint con pruebas reales en Postman. En la nota, eso cuenta como evidencia de verificación funcional.

### Organización final recomendada en Postman

- **Facebook API - Taller Backend**
  - Crear publicación con imágenes
  - Listar publicaciones
  - Agregar comentario
  - Dar like a publicación
  - Eliminar publicación

---

## 8. Checklist · "Dejarlo Funcionando"

Al terminar el taller, verifica que cada uno de los siguientes puntos esté cumplido:

| ✔ | Verificación | Evidencia |
|---|---|---|
| ☐ | Proyecto Laravel creado y corriendo en http://127.0.0.1:8000 | Captura de `php artisan serve` |
| ☐ | Enlace simbólico de storage creado | `public/storage` existe |
| ☐ | Base de datos migrada con tablas posts, post_images, comments | `php artisan migrate:status` |
| ☐ | POST /api/posts crea post y guarda imágenes correctamente | Respuesta 201 con JSON |
| ☐ | GET /api/posts lista los posts con image_url completa | Captura Postman |
| ☐ | POST /api/posts/{id}/comments agrega comentario | Respuesta 201 |
| ☐ | POST /api/posts/{id}/like incrementa likes_count | Respuesta 200 |
| ☐ | DELETE /api/posts/{id} elimina post e imágenes físicas | Respuesta 204 |
| ☐ | Colección Postman completa y funcional | .json exportado |
| ☐ | Repositorio en GitHub con README.md | URL del repo |

---

## 9. Glosario de Términos

| Término | Definición |
|---|---|
| **API REST** | Interfaz de programación que sigue los principios REST para comunicar sistemas vía HTTP. |
| **Endpoint** | URL específica de la API que realiza una función determinada. |
| **Eloquent** | ORM (Object-Relational Mapping) de Laravel para interactuar con la base de datos usando modelos. |
| **Migración** | Archivo PHP que define la estructura de una tabla en la base de datos. |
| **Storage** | Sistema de archivos de Laravel para guardar imágenes, PDFs, etc. |
| **multipart/form-data** | Formato HTTP usado para enviar archivos junto con otros datos. |
| **Postman** | Herramienta gráfica para probar APIs HTTP. |
| **Middleware** | Capa intermedia que procesa peticiones antes de llegar al controlador. |
| **CSRF** | Cross-Site Request Forgery, mecanismo de seguridad que Laravel activa por defecto en rutas web. |

---

## 10. Recursos Adicionales

- Documentación oficial de Laravel: <https://laravel.com/docs>
- Laravel Eloquent Relationships: <https://laravel.com/docs/eloquent-relationships>
- File Storage en Laravel: <https://laravel.com/docs/filesystem>
- Postman Learning Center: <https://learning.postman.com>
- REST API Tutorial: <https://restfulapi.net>

---

## 11. Anexos

### Anexo A · Estructura de carpetas del proyecto

```
facebook-api/
├── app/
│   ├── Http/Controllers/
│   │   ├── PostController.php
│   │   └── CommentController.php
│   └── Models/
│       ├── Post.php
│       ├── PostImage.php
│       └── Comment.php
├── database/
│   ├── migrations/
│   │   ├── ..._create_posts_table.php
│   │   ├── ..._create_post_images_table.php
│   │   └── ..._create_comments_table.php
│   └── database.sqlite
├── routes/
│   └── api.php
├── storage/
│   └── app/public/posts/   ← imágenes guardadas
└── public/
    └── storage → storage/app/public  (enlace simbólico)
```

---

*🎓 ¡Éxito en el taller! La mejor forma de aprender es romper cosas y arreglarlas.*
