<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;

class UserController extends Controller
{
    use ApiResponse;

    public function userDetails(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return $this->error([], 'Unauthenticated User', 401);
        }

        return $this->success($user, 'User Data fetched successfully!', 200);
    }

    public function updateUser(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return $this->error([], 'Unauthenticated', 401);
        }

        $validator = Validator::make($request->all(), [
            'name'           => 'sometimes|string|max:255',
            'avatar'         => 'nullable|image|mimes:jpeg,png,jpg,svg|max:20480',
            'phone'          => 'nullable|string|max:20',
            'whatsapp'       => 'nullable|string|max:20',
            'postal_code'    => 'nullable|string|max:20',
            'street_address' => 'nullable|string|max:255',
            'code'           => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation Error', 422);
        }

        DB::beginTransaction();

        try {
            // 🧩 avatar upload
            if ($request->hasFile('avatar')) {
                // পুরনো avatar মুছে ফেলি (যদি থাকে)
                if ($user->avatar && File::exists(public_path($user->avatar))) {
                    File::delete(public_path($user->avatar));
                }

                // নতুন avatar upload
                $user->avatar = uploadImage($request->file('avatar'), 'User/Avatar');
            }

            // 🧩 অন্যান্য তথ্য update
            $user->fill([
                'name'           => $request->input('name', $user->name),
                'code'           => $request->input('code', $user->code),
                'phone'          => $request->input('phone', $user->phone),
                'whatsapp'       => $request->input('whatsapp', $user->whatsapp),
                'postal_code'    => $request->input('postal_code', $user->postal_code),
                'street_address' => $request->input('street_address', $user->street_address),
            ]);

            $user->save();
            DB::commit();

            return $this->success($user, 'User updated successfully', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error($e);
            return $this->error([], 'Something went wrong', 500);
        }
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return $this->error([], 'Unauthenticated', 401);
        }

        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string|min:8',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation Error', 422);
        }

        if (!Hash::check($request->old_password, $user->password)) {
            return $this->error([], 'Old password is incorrect', 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return $this->success([], 'Password updated successfully', 200);
    }

    public function deleteAccount(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return $this->error([], 'Unauthenticated', 401);
        }

        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation Error', 422);
        }

        if (!Hash::check($request->password, $user->password)) {
            return $this->error([], 'Password is incorrect', 400);
        }

        try {
            // 🧩 পুরনো avatar delete করো
            if ($user->avatar && File::exists(public_path($user->avatar))) {
                File::delete(public_path($user->avatar));
            }

            $user->delete();

            return $this->success([], 'Your account has been deleted successfully', 200);
        } catch (\Exception $e) {
            \Log::error($e);
            return $this->error([], 'Something went wrong while deleting your account', 500);
        }
    }
}
