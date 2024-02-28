<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function updateInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:50',
            'profile_photo' => 'image|mimes:jpeg,png,jpg|max:2048',
            'about' => 'string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), JsonResponse::HTTP_BAD_REQUEST);
        }
        try {
            $user = User::findOrFail(Auth::user()->id);

            if ($request->has('name')) {
                $user->name = $request->input('name');
            }
            if ($request->has('about')) {
                $user->about = $request->input('about');
            }

            if ($request->hasFile('profile_photo')) {
                $photo = $request->file('profile_photo');
                $path = $photo->store('public/');
                $user->profile_photo = $path;
            }
            $user->save();
            return response()->json([
                'error' => false,
                'message' => 'Pomyślnie edytowano dane'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }
}
