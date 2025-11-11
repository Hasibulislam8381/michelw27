<?php

namespace App\Http\Controllers\API\CommentReaction;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\MatchRatingCaption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponse;

class CommentReactionController extends Controller
{
    use ApiResponse;
    public function storeComment(Request $request)
    {
        $request->validate([
            'caption_id' => 'required|integer|exists:match_rating_captions,id',
            'comment' => 'required|string|max:500',
            'parent_id' => 'nullable|integer|exists:comments,id',
        ]);

        $comment = Comment::create([
            'caption_id' => $request->caption_id,
            'user_id' => Auth::id(),
            'comment' => $request->comment,
            'parent_id' => $request->parent_id ?? null,
        ]);

        return $this->success($comment, 'Comment stored successfully');
    }
    public function storeReaction(Request $request)
    {
        $request->validate([
            'reactable_type' => 'required|string|in:App\Models\MatchRatingCaption,App\Models\Comment',
            'reactable_id' => 'required|integer',
            'type' => 'required|string', // like/dislike etc.
        ]);

        $userId = Auth::id();

        // Check if reaction already exists
        $reaction = \App\Models\Reaction::where([
            'user_id' => $userId,
            'reactable_type' => $request->reactable_type,
            'reactable_id' => $request->reactable_id,
        ])->first();

        if ($reaction) {
            if ($reaction->type === $request->type) {
                // Same type clicked again → remove reaction (unlike)
                $reaction->delete();
                return $this->success([], 'Reaction removed successfully');
            } else {
                // Different type clicked → update reaction type
                $reaction->update(['type' => $request->type]);
                return $this->success($reaction, 'Reaction updated successfully');
            }
        }

        // No previous reaction → create new
        $newReaction = \App\Models\Reaction::create([
            'reactable_type' => $request->reactable_type,
            'reactable_id' => $request->reactable_id,
            'user_id' => $userId,
            'type' => $request->type,
        ]);

        return $this->success($newReaction, 'Reaction stored successfully');
    }
    public function allComment(Request $request)
    {
        $request->validate([
            'caption_id' => 'required|integer',
        ]);

        $captionId = $request->caption_id;

        // Get all top-level comments for the caption
        $comments = \App\Models\Comment::with([
            'user:id,name,avatar',
            'replies.user:id,name,avatar',
            'replies.reactions', // reactions on replies
            'reactions' // reactions on main comment
        ])->where('caption_id', $captionId)
            ->whereNull('parent_id') // only top-level comments
            ->latest()
            ->get();

        $data = $comments->map(function ($comment) {
            // main comment reactions count
            $likesCount = $comment->reactions->where('type', 'like')->count();
            $dislikesCount = $comment->reactions->where('type', 'dislike')->count();

            // map replies
            $replies = $comment->replies->map(function ($reply) {
                $likesCount = $reply->reactions->where('type', 'like')->count();
                $dislikesCount = $reply->reactions->where('type', 'dislike')->count();

                return [
                    'comment_id' => $reply->id,
                    'user_id' => $reply->user->id ?? null,
                    'user_name' => $reply->user->name ?? 'Unknown',
                    'user_avatar' => $reply->user->avatar ?? null,
                    'comment' => $reply->comment,
                    'likes_count' => $likesCount,
                    'dislikes_count' => $dislikesCount,
                ];
            })->values();

            return [
                'comment_id' => $comment->id,
                'user_id' => $comment->user->id ?? null,
                'user_name' => $comment->user->name ?? 'Unknown',
                'user_avatar' => $comment->user->avatar ?? null,
                'comment' => $comment->comment,
                'likes_count' => $likesCount,
                'dislikes_count' => $dislikesCount,
                'replies' => $replies,
                'replies_count' => $replies->count(),
            ];
        });

        return $this->success($data, 'Comments fetched successfully');
    }
}
