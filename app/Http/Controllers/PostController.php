<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with(['images', 'comments'])
            ->latest()
            ->get();

        return response()->json($posts, 200);
    }

    public function show($id)
    {
        $post = Post::with(['images', 'comments'])->findOrFail($id);

        return response()->json($post, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'content'  => 'required|string',
            'images'   => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $storedPaths = [];

        try {
            $post = DB::transaction(function () use ($request, &$storedPaths) {
                $post = Post::create([
                    'title'   => $request->title,
                    'content' => $request->content,
                ]);

                foreach ($request->file('images', []) as $file) {
                    $path = $file->store('posts', 'public');
                    $storedPaths[] = $path;
                    $post->images()->create(['image_path' => $path]);
                }

                return $post;
            });
        } catch (Throwable $e) {
            // Si algo falla, no dejar archivos huérfanos en el storage
            Storage::disk('public')->delete($storedPaths);
            throw $e;
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

        Storage::disk('public')->delete($post->images->pluck('image_path')->all());

        $post->delete();
        return response()->json(null, 204);
    }
}
