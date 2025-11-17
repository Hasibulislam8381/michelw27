<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BlogController;
use App\Http\Controllers\API\CommentReaction\CommentReactionController;
use App\Http\Controllers\Api\FavoriteTeamController;
use App\Http\Controllers\Api\FollowController;
use App\Http\Controllers\API\Football\LiveScoreController;
use App\Http\Controllers\API\Football\TeamController;
use App\Http\Controllers\API\MatchRatingController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\SocialMediaController;
use App\Http\Controllers\API\SystemSettingController;
use App\Http\Controllers\Api\User\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post("register", [AuthController::class, 'register']);
Route::post("login", [AuthController::class, 'login']);

Route::controller(RegisterController::class)->prefix('users/register')->group(function () {
    // User Register
    Route::post('/', 'userRegister');

    // Verify OTP
    Route::post('/otp-verify', 'otpVerify');

    // Resend OTP
    Route::post('/otp-resend', 'otpResend');
    //email exists check
    Route::post('/email-exists', 'emailExists');
});
Route::controller(LoginController::class)->prefix('users/login')->group(function () {

    // User Login
    Route::post('/', 'userLogin');

    // Verify Email
    Route::post('/email-verify', 'emailVerify');

    // Resend OTP
    Route::post('/otp-resend', 'otpResend');

    // Verify OTP
    Route::post('/otp-verify', 'otpVerify');

    //Reset Password
    Route::post('/reset-password', 'resetPassword');
});
Route::get('/fetch-teams', function (Request $request) {
    // Optional: secure with a secret token
    if ($request->query('token') !== env('CRON_SECRET')) {
        abort(403, 'Unauthorized');
    }
    Artisan::call('teams:fetch-and-store');

    return response()->json([
        'status' => 'success',
        'message' => 'Command executed'
    ]);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::controller(SystemSettingController::class)->group(function () {
        Route::get('/site-settings', 'index');
    });

    Route::controller(SocialMediaController::class)->group(function () {
        Route::get('/social-links', 'index');
    });

    Route::prefix('user')->controller(UserController::class)->group(function () {
        Route::get('/', 'userDetails');
        Route::post('/update', 'updateUser');
        Route::post('/update-password', 'updatePassword');
        Route::delete('/delete-account', 'deleteAccount');
        Route::post('/logout', 'logoutUser');
    });

    Route::controller(BlogController::class)->group(function () {
        Route::get('/blogs', 'index');
    });

    Route::post('/users/logout', [LoginController::class, 'logout']);


    Route::prefix('/favorite-team')->controller(FavoriteTeamController::class)->group(function () {
        Route::get('/', 'getFavoriteTeams');
        Route::post('/toggle', 'toggleFavoriteTeam');
    });
    Route::prefix('/notifications')->controller(NotificationController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/mark-read/{id}', 'markAsRead');
        Route::delete('/clear', 'clearAll');
    });
    Route::prefix('/follow')->controller(FollowController::class)->group(function () {
        Route::post('/toggle', 'toggleFollow');
        Route::get('/followers', 'followers');
        Route::get('/following', 'following');
    });
    Route::prefix('/teams')->controller(TeamController::class)->group(function () {
        Route::get('/club', 'getClubTeams');
        Route::get('/national', 'getNationalTeams');
    });
    Route::prefix('/live-scores')->controller(LiveScoreController::class)->group(function () {
        Route::get('/', 'liveFixtures');
        Route::get('/fixtures-lineups', 'getLineUpsbyFixture');
        Route::get('/fixtures-events', 'getEventsByFixture');
        Route::get('/fixtures-statistics', 'getStatisticsByFixture');
        Route::get('/community-user-rating', 'communityUserRating');
        Route::get('/team-leaderboard', 'teamLeaderboard');
    });
    Route::prefix('/match-ratings')->controller(MatchRatingController::class)->group(function () {
        Route::post('/store', 'store');
        Route::get('/feed', 'feed');
        Route::get('/my-rating', 'myRating');
        Route::get('/my-national-rating', 'myNationalRating');
        Route::get('/latest-mathes', 'latestMatches');
    });
    Route::prefix('/comment-reaction')->controller(CommentReactionController::class)->group(function () {
        Route::post('/comment/store', 'storeComment');
        Route::post('/reaction/store', 'storeReaction');
        Route::get('/all-comment', 'allComment');
    });
});
