<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function add(Request $request)
    {
        $post = new Post();
        $post->author = auth()->user();
        $post->title = $request->title;
        $post->description = $request->description;
        $post->save();

        return response()->json(['message' => 'Pomyślnie dodano post']);
    }

    public function getAll()
    {
        try {
            $posts = Post::all();
            $posts->transform(function ($post) {
                $post->author = json_decode($post->author, true);
                $likesCount = $post->likes()->count();
                $likesPeople = $post->likes()->take(3)->get();
                $commentsCount = $post->comments()->count();
                $firstComment = $post->comments()->first();

                $firstThreeNames = $likesPeople->map(function ($like) {
                    return $like->user_name;
                });

                $post->likes_count = $likesCount;
                $post->first_three_names = $firstThreeNames;
                $post->comments_count = $commentsCount;
                $post->firstcomment = $firstComment;
                return $post;
            });

            return response()->json($posts);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    public function show($id)
    {
        try {
            $post = Post::findOrFail($id);
            $post->author = json_decode($post->author, true);
            $likesCount = $post->likes()->count();
            $likesPeople = $post->likes()->take(3)->get();

            $firstThreeNames = $likesPeople->map(function ($like) {
                return $like->user_name;
            });

            $post->likes_count = $likesCount;
            $post->first_three_names = $firstThreeNames;
            return response()->json($post);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    public function delete($id)
    {
        try {
            $post = Post::findOrFail($id);
            $postAuthor = json_decode($post->author);
            if (Auth::user()->id !== $postAuthor->id) {
                return response()->json(['error' => 'Nie masz uprawnień do usunięcia tego posta.'], 403);
            }
            $post->delete();
            return response()->json(['message' => 'Pomyślnie usunięto post']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    public function edit(Request $request, $id)
    {
        try {
            $post = Post::findOrFail($id);
            $postAuthor = json_decode($post->author);
            if (Auth::user()->id !== $postAuthor->id) {
                return response()->json(['error' => 'Nie masz uprawnień do edycji tego posta.'], 403);
            }

            if ($request->has('title')) {
                $post->title = $request->input('title');
            }
            if ($request->has('description')) {
                $post->description = $request->input('description');
            }
            $post->save();
            return response()->json(['message' => 'Pomyślnie edytowano post']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }
}
