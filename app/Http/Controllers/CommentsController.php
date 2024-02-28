<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Comments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentsController extends Controller
{
    public function getAll($postId)
    {
        try {
            $post = Post::findOrFail($postId);
            $comments = $post->comments()->get();

            return response()->json($comments);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    public function add(Request $request, $post_id)
    {
        $existingPost = Post::where('id', $post_id)
            ->first();

        if (!$existingPost) {
            return response()->json([
                'error' => true,
                'message' => 'Nie ma takiego posta'
            ], 400);
        }

        $comment = new Comments();
        $comment->post_id = $post_id;
        $comment->comment = $request->comment;
        $comment->user_id = Auth::user()->id;
        $comment->user_name = Auth::user()->name;

        $comment->save();
        return response()->json([
            'error' => false,
            'message' => 'Pomyślnie dodano komentarz'
        ]);
    }

    public function delete($comment_id)
    {
        try {
            $existingComment = Comments::where('id', $comment_id)
                ->where('user_id', Auth::user()->id)
                ->first();

            $existingComment->delete();
            return response()->json([
                'error' => false,
                'message' => 'Pomyślnie usunięto komentarz'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }
}
