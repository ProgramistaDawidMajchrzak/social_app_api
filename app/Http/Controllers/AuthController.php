<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\FriendRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|required',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|confirmed|min:8'
        ]);

        if ($validator->fails()) {
            //return response()->json($validator->errors()->toJson(), 400);
            return response()->json($validator->errors(), JsonResponse::HTTP_BAD_REQUEST);
        }
        $user = User::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)]
        ));
        return response()->json([
            'message' => 'User created',
            'user' => $user
        ], JsonResponse::HTTP_CREATED);
    }

    // public function login(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'email' => 'required|email',
    //         'password' => 'required|string|min:8'
    //     ]);

    //     if ($validator->fails()) {
    //         //return response()->json($validator->errors()->toJson(), 400);
    //         return response()->json($validator->errors(), JsonResponse::HTTP_BAD_REQUEST);
    //     }
    //     if (!$token = auth()->attempt($validator->validated())) {
    //         return response()->json(['error' => 'Unauthorized'], 401);
    //     }
    //     return $this->createNewToken($token);
    // }


    // public function createNewToken($token)
    // {
    //     return response()->json([
    //         'access_token' => $token,
    //         'user' => auth()->user()
    //     ]);
    // }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:8'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!$token = auth()->attempt($validator->validated())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth()->user();
        $user_id = $user->id;

        $postsCount = Post::where('author->id', $user_id)->count();

        $friendsCount = FriendRequest::where('status', 'accepted')->where(function ($query) use ($user_id) {
            $query->where('sender_id', $user_id)
                ->orWhere('recipient_id', $user_id);
        })
            ->with(['sender', 'recipient'])
            ->count();

        return $this->createNewToken($token, $postsCount, $friendsCount);
    }

    public function createNewToken($token, $postsCount, $friendsCount)
    {
        return response()->json([
            'access_token' => $token,
            'user' => auth()->user(),
            'posts_count' => $postsCount,
            'friends_count' => $friendsCount
        ]);
    }

    public function profile()
    {
        return response()->json(auth()->user());
    }

    public function logout()
    {
        auth()->logout();
        return response()->json([
            'message' => 'Logged out'
        ]);
    }
}
