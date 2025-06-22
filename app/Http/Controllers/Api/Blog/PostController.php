<?php

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostController extends Controller
{
    /**
     * Вивести список постів з пагінацією.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1);

        $posts = BlogPost::with(['user:id,name', 'category:id,title'])
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        $formatted = $posts->map(fn($post) => [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'is_published' => $post->is_published,
            'published_at' => $post->published_at,
            'user' => ['name' => $post->user->name],
            'category' => ['title' => $post->category->title],
        ]);

        return response()->json([
            'data' => $formatted,
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    /**
     * Зберегти новий пост.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'content_raw' => 'nullable|string',
            'excerpt' => 'nullable|string',
            'category_id' => 'required|exists:blog_categories,id',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $validated['user_id'] = auth()->id(); // або інша логіка автора

        $post = BlogPost::create($validated);

        return response()->json($post, 201);
    }

    /**
     * Показати конкретний пост.
     */
    public function show($id)
    {
        $post = BlogPost::with(['user:id,name', 'category:id,title'])->findOrFail($id);

        return response()->json([
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'content_raw' => $post->content_raw,
            'excerpt' => $post->excerpt,
            'is_published' => $post->is_published,
            'published_at' => $post->published_at,
            'category_id' => $post->category_id,
            'user' => ['name' => $post->user->name],
            'category' => ['title' => $post->category->title, 'id' => $post->category->id],
        ]);
    }

    /**
     * Оновити пост.
     */
    public function update(Request $request, $id)
    {
        $post = BlogPost::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'content_raw' => 'nullable|string',
            'excerpt' => 'nullable|string',
            'category_id' => 'required|exists:blog_categories,id',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        if (isset($validated['is_published']) && $validated['is_published'] && !$post->published_at) {
            $validated['published_at'] = now();
        } elseif (empty($validated['is_published'])) {
            $validated['published_at'] = null;
        }

        $post->update($validated);

        return response()->json($post);
    }

    /**
     * Видалити пост.
     */
    public function destroy($id)
    {
        $post = BlogPost::findOrFail($id);
        $post->delete();

        return response()->json(['message' => 'Пост видалено']);
    }
}
