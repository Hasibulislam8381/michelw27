<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Follow;
use App\Models\User;
use App\Traits\ApiResponse;

class FollowController extends Controller
{
    use ApiResponse;

    public function toggleFollow(Request $request)
    {
        $request->validate([
            'following_id' => 'required|integer|exists:users,id',
        ]);

        $user = Auth::user();
        $followingId = $request->following_id;

        if ($user->id == $followingId) {
            return $this->error([], "You cannot follow yourself", 400);
        }

        $follow = Follow::where('follower_id', $user->id)
            ->where('following_id', $followingId)
            ->first();

        if ($follow) {
            // Unfollow
            $follow->delete();
            return $this->success([], "User unfollowed successfully", 200);
        } else {
            // Follow
            Follow::create([
                'follower_id' => $user->id,
                'following_id' => $followingId
            ]);

            return $this->success([], "User followed successfully", 200);
        }
    }


    public function followers()
    {
        $user = Auth::user();

        $followers = Follow::where('following_id', $user->id)
            ->with('follower:id,name,avatar')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->follower->id,
                    'name' => $item->follower->name,
                    'avatar' => $item->follower->avatar,
                ];
            });

        return $this->success($followers, 'Followers list retrieved', 200);
    }
    public function following()
    {
        $user = Auth::user();

        $following = Follow::where('follower_id', $user->id)
            ->with('following:id,name,avatar')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->following->id,
                    'name' => $item->following->name,
                    'avatar' => $item->following->avatar,
                ];
            });
        return $this->success($following, 'Following list retrieved', 200);
    }
}
