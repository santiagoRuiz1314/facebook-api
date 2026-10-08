<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function crearPostConImagenes(): array
    {
        return $this->postJson('/api/posts', [
            'title'   => 'Mi primera publicación',
            'content' => 'Hola a todos, comparto fotos de mi viaje.',
            'images'  => [
                UploadedFile::fake()->image('foto1.jpg'),
                UploadedFile::fake()->image('foto2.png'),
            ],
        ])->assertStatus(201)->json();
    }

    public function test_crear_publicacion_con_imagenes_devuelve_201(): void
    {
        $post = $this->crearPostConImagenes();

        $this->assertSame('Mi primera publicación', $post['title']);
        $this->assertCount(2, $post['images']);
        $this->assertDatabaseCount('post_images', 2);

        foreach ($post['images'] as $image) {
            Storage::disk('public')->assertExists($image['image_path']);
        }
    }

    public function test_crear_publicacion_sin_titulo_ni_contenido_devuelve_422(): void
    {
        $this->postJson('/api/posts', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'content']);
    }

    public function test_crear_publicacion_con_archivo_que_no_es_imagen_devuelve_422(): void
    {
        $this->postJson('/api/posts', [
            'title'   => 'Post',
            'content' => 'Contenido',
            'images'  => [UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf')],
        ])->assertStatus(422)->assertJsonValidationErrors(['images.0']);
    }

    public function test_listar_publicaciones_incluye_imagenes_con_url_y_comentarios(): void
    {
        $post = $this->crearPostConImagenes();
        Post::find($post['id'])->comments()->create(['author' => 'Ana', 'content' => 'Hola']);

        $this->getJson('/api/posts')
            ->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonCount(2, '0.images')
            ->assertJsonCount(1, '0.comments')
            ->assertJsonPath('0.images.0.image_url', asset('storage/' . $post['images'][0]['image_path']));
    }

    public function test_ver_publicacion_individual_devuelve_200(): void
    {
        $post = $this->crearPostConImagenes();

        $this->getJson("/api/posts/{$post['id']}")
            ->assertStatus(200)
            ->assertJsonPath('id', $post['id'])
            ->assertJsonStructure(['id', 'title', 'content', 'likes_count', 'images' => [['image_url']], 'comments']);
    }

    public function test_ver_publicacion_inexistente_devuelve_404(): void
    {
        $this->getJson('/api/posts/999')->assertStatus(404);
    }

    public function test_dar_like_incrementa_likes_count(): void
    {
        $post = Post::create(['title' => 'Post', 'content' => 'Contenido']);

        $this->postJson("/api/posts/{$post->id}/like")
            ->assertStatus(200)
            ->assertJsonPath('likes_count', 1);

        $this->postJson("/api/posts/{$post->id}/like")
            ->assertJsonPath('likes_count', 2);

        $this->assertSame(2, $post->fresh()->likes_count);
    }

    public function test_dar_like_a_publicacion_inexistente_devuelve_404(): void
    {
        $this->postJson('/api/posts/999/like')->assertStatus(404);
    }

    public function test_eliminar_publicacion_borra_imagenes_y_comentarios(): void
    {
        $post = $this->crearPostConImagenes();
        Post::find($post['id'])->comments()->create(['author' => 'Ana', 'content' => 'Hola']);

        $this->deleteJson("/api/posts/{$post['id']}")
            ->assertStatus(204)
            ->assertNoContent();

        $this->assertDatabaseMissing('posts', ['id' => $post['id']]);
        $this->assertDatabaseCount('post_images', 0);
        $this->assertDatabaseCount('comments', 0);

        foreach ($post['images'] as $image) {
            Storage::disk('public')->assertMissing($image['image_path']);
        }
    }

    public function test_eliminar_publicacion_inexistente_devuelve_404(): void
    {
        $this->deleteJson('/api/posts/999')->assertStatus(404);
    }

    public function test_crear_publicacion_devuelve_image_url_completa(): void
    {
        $post = $this->crearPostConImagenes();

        foreach ($post['images'] as $image) {
            $this->assertSame(asset('storage/' . $image['image_path']), $image['image_url']);
        }
    }

    public function test_crear_publicacion_con_images_que_no_es_arreglo_devuelve_422(): void
    {
        $this->postJson('/api/posts', [
            'title'   => 'Post',
            'content' => 'Contenido',
            'images'  => 'no-es-un-arreglo',
        ])->assertStatus(422)->assertJsonValidationErrors(['images']);
    }

    public function test_publicacion_inexistente_devuelve_404_json_sin_header_accept(): void
    {
        $this->get('/api/posts/999')
            ->assertStatus(404)
            ->assertExactJson(['message' => 'Publicación no encontrada']);
    }

    public function test_ruta_inexistente_devuelve_404_indicando_la_ruta(): void
    {
        $this->postJson('/api/post', ['title' => 'Post', 'content' => 'Contenido'])
            ->assertStatus(404)
            ->assertExactJson(['message' => 'La ruta POST /api/post no existe']);
    }

    public function test_crear_publicacion_con_images_vacio_devuelve_422(): void
    {
        $this->postJson('/api/posts', [
            'title'   => 'Post',
            'content' => 'Contenido',
            'images'  => [],
        ])->assertStatus(422)->assertJsonValidationErrors(['images']);

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_crear_publicacion_con_campo_images_sin_archivo_devuelve_422(): void
    {
        // Así llega a PHP un campo images[] de form-data sin archivo seleccionado
        $_FILES['images'] = [
            'name'     => [''],
            'type'     => [''],
            'tmp_name' => [''],
            'error'    => [UPLOAD_ERR_NO_FILE],
            'size'     => [0],
        ];

        try {
            $this->postJson('/api/posts', [
                'title'   => 'Post',
                'content' => 'Contenido',
            ])->assertStatus(422)->assertJsonValidationErrors(['images']);
        } finally {
            unset($_FILES['images']);
        }

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_crear_publicacion_sin_campo_images_devuelve_201(): void
    {
        $this->postJson('/api/posts', [
            'title'   => 'Post sin fotos',
            'content' => 'Contenido',
        ])->assertStatus(201)->assertJsonCount(0, 'images');
    }
}
