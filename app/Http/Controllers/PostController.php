<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;

class PostController extends Controller
{
    public function add(Request $request)
    {
        $post = new Post();
        $post->author = auth()->user();
        $post->author_id = auth()->user()->id;
        $post->author_name = auth()->user()->name;
        $post->author_profile_photo = auth()->user()->profile_photo;
        $post->title = $request->title;
        $post->description = $request->description;
        $post->save();

        return response()->json(['message' => 'Pomyślnie dodano post']);
    }

    public function getAll(Request $request)
    {
        try {
            $page = $request->query('page', 1);
            $perPage = 10;
            $allPosts = Post::with('author')->get()->reverse();

            $currentPageItems = $allPosts->slice(($page - 1) * $perPage, $perPage)->values();
            $posts = new LengthAwarePaginator(
                $currentPageItems,
                $allPosts->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );


            $posts->getCollection()->transform(function ($post) {
                // $post->author_id = $post->author_id;
                // $post->author_name = $post->author_name;
                // $post->author_profile_photo = $post->author_profile_photo;
                $likesCount = $post->likes()->count();
                $likesPeople = $post->likes()->take(3)->get();
                $commentsCount = $post->comments()->count();
                $firstComment = $post->comments()->latest()->first();
                if ($firstComment) {
                    $firstCommentUser = User::find($firstComment->user_id);
                    $post->first_comment_user_profile_photo = $firstCommentUser->profile_photo;
                } else {
                    $post->first_comment_user_profile_photo = null;
                }

                $isLikedByMe = $post->likes()->where('user_id', Auth::user()->id)->exists();

                $firstThreeNames = $likesPeople->map(function ($like) {
                    return $like->user_name;
                });

                $post->likes_count = $likesCount;
                $post->first_three_names = $firstThreeNames;
                $post->comments_count = $commentsCount;
                $post->firstcomment = $firstComment;
                $post->is_liked_by_me = $isLikedByMe;
                return $post;
            });

            //$posts = $posts->reverse();

            return response()->json($posts);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    public function getAllPostsByUser(Request $request, $user_id)
    {
        try {
            $page = $request->query('page', 1);
            $posts = Post::where('author->id', $user_id)->paginate(10, ['*'], 'page', $page);

            $posts->getCollection()->transform(function ($post) {
                // $post->author_id = $post->author_id;
                // $post->author_name = $post->author_name;
                // $post->author_profile_photo = $post->author_profile_photo;
                $likesCount = $post->likes()->count();
                $likesPeople = $post->likes()->take(3)->get();
                $commentsCount = $post->comments()->count();
                $firstComment = $post->comments()->latest()->first();
                if ($firstComment) {
                    $firstCommentUser = User::find($firstComment->user_id);
                    $post->first_comment_user_profile_photo = $firstCommentUser->profile_photo;
                } else {
                    $post->first_comment_user_profile_photo = null;
                }

                $isLikedByMe = $post->likes()->where('user_id', Auth::user()->id)->exists();

                $firstThreeNames = $likesPeople->map(function ($like) {
                    return $like->user_name;
                });

                $post->likes_count = $likesCount;
                $post->first_three_names = $firstThreeNames;
                $post->comments_count = $commentsCount;
                $post->firstcomment = $firstComment;
                $post->is_liked_by_me = $isLikedByMe;
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
