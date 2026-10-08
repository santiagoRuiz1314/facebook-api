<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_agregar_comentario_devuelve_201(): void
    {
        $post = Post::create(['title' => 'Post', 'content' => 'Contenido']);

        $this->postJson("/api/posts/{$post->id}/comments", [
            'author'  => 'Carlos Gómez',
            'content' => '¡Qué buenas fotos!',
        ])
            ->assertStatus(201)
            ->assertJsonPath('author', 'Carlos Gómez')
            ->assertJsonPath('post_id', $post->id);

        $this->assertDatabaseHas('comments', ['post_id' => $post->id, 'content' => '¡Qué buenas fotos!']);
    }

    public function test_agregar_comentario_sin_datos_devuelve_422(): void
    {
        $post = Post::create(['title' => 'Post', 'content' => 'Contenido']);

        $this->postJson("/api/posts/{$post->id}/comments", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['author', 'content']);
    }

    public function test_agregar_comentario_a_publicacion_inexistente_devuelve_404(): void
    {
        $this->postJson('/api/posts/999/comments', [
            'author'  => 'Carlos Gómez',
            'content' => 'Hola',
        ])->assertStatus(404);
    }

    public function test_listar_comentarios_de_una_publicacion(): void
    {
        $post = Post::create(['title' => 'Post', 'content' => 'Contenido']);
        $post->comments()->create(['author' => 'Ana', 'content' => 'Primero']);
        $post->comments()->create(['author' => 'Luis', 'content' => 'Segundo']);

        $this->getJson("/api/posts/{$post->id}/comments")
            ->assertStatus(200)
            ->assertJsonCount(2);
    }
}
