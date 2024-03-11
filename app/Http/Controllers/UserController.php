<?php

namespace App\Http\Controllers;

use App\Models\FriendRequest;
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

    public function sendFriendRequest($friend_id)
    {
        try {
            $user = User::findOrFail(Auth::user()->id);
            $friend = User::findOrFail($friend_id);

            if ($user->hasSentFriendRequestTo($friend)) {
                return response()->json(['error' => 'Invitation has alredy been send'], JsonResponse::HTTP_BAD_REQUEST);
            }

            // $user->sentFriendRequests($friend);
            $invitation = new FriendRequest();
            $invitation->sender_id = Auth::user()->id;
            $invitation->recipient_id = $friend_id;
            $invitation->status = 'pending';

            $invitation->save();

            return response()->json([
                'error' => false,
                'message' => 'Invitation sended'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function cancelFriendRequest($invitation_id)
    {
        try {
            $user_id = Auth::user()->id;

            $existingInvitation = FriendRequest::where('id', $invitation_id)
                ->where(function ($query) use ($user_id) {
                    $query->where('sender_id', $user_id)
                        ->orWhere('recipient_id', $user_id);
                })
                ->first();

            if (!$existingInvitation) {
                return response()->json([
                    'error' => true,
                    'message' => 'Invitation not found or you are not authorized to cancel it'
                ], 404);
            }

            $existingInvitation->delete();

            return response()->json([
                'error' => false,
                'message' => 'Invitation canceled'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function acceptFriendRequest($invitation_id)
    {
        try {
            $user_id = Auth::user()->id;

            $existingInvitation = FriendRequest::where('id', $invitation_id)
                ->where('recipient_id', $user_id)
                ->where('status', 'pending')
                ->first();

            if (!$existingInvitation) {
                return response()->json([
                    'error' => true,
                    'message' => 'Invitation not found or you are not authorized to accept it'
                ], 404);
            }

            $existingInvitation->status = 'accepted';

            $existingInvitation->save();

            return response()->json([
                'error' => false,
                'message' => 'Invitation accepted'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getMyInvitations()
    {
        try {
            $user_id = Auth::user()->id;

            $invitations = FriendRequest::where('recipient_id', $user_id)
                ->where('status', 'pending')
                ->get();

            return response()->json([
                'error' => false,
                'invitations' => $invitations
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getMyFriends()
    {
        try {
            $user_id = Auth::user()->id;

            $friends = FriendRequest::where('status', 'accepted')
                ->where(function ($query) use ($user_id) {
                    $query->where('sender_id', $user_id)
                        ->orWhere('recipient_id', $user_id);
                })
                ->get();

            return response()->json([
                'error' => false,
                'friends' => $friends
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
