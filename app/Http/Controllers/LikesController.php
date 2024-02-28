<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Likes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LikesController extends Controller
{
    public function add($post_id)
    {
        $existingPost = Post::where('id', $post_id)
            ->first();

        if (!$existingPost) {
            return response()->json([
                'error' => true,
                'message' => 'Nie ma takiego posta'
            ], 400);
        }

        $existingLike = Likes::where('post_id', $post_id)
            ->where('user_id', Auth::user()->id)
            ->first();

        if ($existingLike) {
            return response()->json([
                'error' => true,
                'message' => 'Już polubiłeś ten post'
            ], 400);
        }

        $like = new Likes();
        $like->post_id = $post_id;
        $like->user_id = Auth::user()->id;
        $like->user_name = Auth::user()->name;

        $like->save();
        return response()->json([
            'error' => false,
            'message' => 'Pomyślnie polubiono post'
        ]);
    }

    public function delete($post_id)
    {
        try {
            $existingLike = Likes::where('post_id', $post_id)
                ->where('user_id', Auth::user()->id)
                ->first();

            $existingLike->delete();
            return response()->json([
                'error' => false,
                'message' => 'Pomyślnie usunięto polubienie'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }
}
