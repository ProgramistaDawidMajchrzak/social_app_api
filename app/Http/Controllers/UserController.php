<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\FriendRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function getUserInfo($user_id)
    {
        try {
            $user = User::where('id', $user_id)->get()->first();

            return response()->json([
                'error' => false,
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    public function updateInfo(Request $request)
    {
        try {
            $user_id = Auth::user()->id;
            $about = $request->input('about');
            $user = User::findOrFail($user_id);
            $user->about = $about;
            $user->save();
            return response()->json([
                'error' => false,
                'message' => 'User about updated successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }


    public function getPeople()
    {
        try {
            $user_id = Auth::user()->id;
            $people = User::where('id', '!=', $user_id)->get();

            $friendRequests = FriendRequest::where(function ($query) use ($user_id) {
                $query->where('sender_id', $user_id)
                    ->orWhere('recipient_id', $user_id);
            })
                ->get();

            $friendshipIds = [];

            foreach ($people as $person) {
                $friendshipId = null;
                foreach ($friendRequests as $request) {
                    if ($request->sender_id == $user_id && $request->recipient_id == $person->id) {
                        $friendshipId = $request->id;
                        break;
                    } elseif ($request->recipient_id == $user_id && $request->sender_id == $person->id) {
                        $friendshipId = $request->id;
                        break;
                    }
                }

                $friendshipIds[$person->id] = $friendshipId;
            }

            foreach ($people as $person) {
                $person->friendship_id = $friendshipIds[$person->id];
                $person->status = FriendRequest::where('id', $friendshipIds[$person->id])->get();
            }

            return response()->json([
                'error' => false,
                'people' => $people
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
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
                ->with('sender')
                ->get();

            $invitations->transform(function ($invitation) {
                $invitation->sender = $invitation->sender;
                return $invitation;
            });

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
                ->with(['sender', 'recipient'])
                ->get();

            return response()->json([
                'error' => false,
                'friends' => $friends
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getUserFriends($user_id)
    {
        try {
            // $user_id = Auth::user()->id;

            $friends = FriendRequest::where('status', 'accepted')
                ->where(function ($query) use ($user_id) {
                    $query->where('sender_id', $user_id)
                        ->orWhere('recipient_id', $user_id);
                })
                ->with(['sender', 'recipient'])
                ->get();

            return response()->json([
                'error' => false,
                'friends' => $friends
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getSentInvitations()
    {
        try {
            $user_id = Auth::user()->id;

            $invitations = FriendRequest::where('sender_id', $user_id)
                ->where('status', 'pending')
                ->with('sender')
                ->get();

            $invitations->transform(function ($invitation) {
                $invitation->recipient = $invitation->recipient;
                return $invitation;
            });

            return response()->json([
                'error' => false,
                'invitations' => $invitations
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateProfilePhoto(Request $request)
    {
        try {
            $user = User::findOrFail(Auth::user()->id);

            if ($request->hasFile('profile_photo')) {
                $profilePhoto = $request->file('profile_photo');
                $photoPath = $profilePhoto->store('profile_photos', 'public');
                $user->profile_photo = $photoPath;
                $user->save();

                $posts = Post::where('author->id', $user->id)->get();

                foreach ($posts as $post) {
                    //$post->author_name = $user->name;
                    $post->author_profile_photo = $photoPath;
                    $post->save();
                }
                return response()->json(['message' => 'Profile photo updated successfully', 'profile_photo' => asset('storage/' . $photoPath)]);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }


        return response()->json(['error' => 'No profile photo uploaded'], 400);
    }
}
