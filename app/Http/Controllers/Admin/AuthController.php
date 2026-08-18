<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Display Login Page
     */
    public function login()
    {
        return view('admin.auth.login');
    }

    /**
     * Display Forgot-Password Page
     */
    public function forgotPassword()
    {
        return view('admin.auth.forgot_password');
    }

    /**
     * Display Set New Password Page
     */
    public function setNewPassword(Request $request)
    {
        return view('admin.auth.set_new_password');
    }

    /**
     * Login
     */
    public function loginPost(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $event = Event::latest()->first();
            if ($event) {
                $request->session()->put('active_event_id', $event->id);
            } else {
                $request->session()->forget('active_event_id');
            }

            return redirect()->intended('admin')->with('success', 'Welcome back!');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput($request->only('email'));
    }

    /**
     * Logout
     */
    public function logout()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Send Reset Link
     */
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        // Generate token
        $token = Str::random(64);

        // Store token with timestamp
        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            [
                'email' => $request->email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]
        );

        // Create reset link (expires in 3 minutes)
        $link = route('set.new.password', ['token' => $token, 'email' => $request->email]);

        // Send email
        Mail::raw("Click here to reset your password (expires in 3 minutes): $link", function ($message) use ($request) {
            $message->to($request->email)
                ->subject('Password Reset');
        });

        return back()->with('success', 'We have emailed your password reset link!');
    }

    /**
     * Reset Password / Set New Password
     */
    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|confirmed|min:6',
            'token' => 'required'
        ]);

        // Get record
        $reset = DB::table('password_resets')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$reset) {
            return back()->withErrors(['error' => 'Invalid reset token.']);
        }

        // Check token expiry (3 minutes)
        if (Carbon::parse($reset->created_at)->addMinutes(3)->isPast()) {
            return back()->withErrors(['error' => 'Token expired. Please request a new link.']);
        }

        // Update password
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete reset record
        DB::table('password_resets')->where('email', $request->email)->delete();

        return redirect()->route('login')->with('success', 'Password reset successfully!');
    }

    /**
     * Profile
     */
    public function profile()
    {
        $user = Auth::user();
        return view('admin.auth.profile', compact('user'));
    }

    /**
     * Edit Profile
     */
    public function editProfile()
    {
        $user = Auth::user();
        return view('admin.auth.edit_profile', compact('user'));
    }

    /**
     * Update profile
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        // Clean prefix and mobile similar to UserController
        $prefix = trim((string) $request->input('mobile_number_prefix'));
        $prefix = preg_replace('/^\((\+\d{1,4})\)$/', '$1', $prefix);
        $mobileNumber = preg_replace('/\s+/', '', (string) $request->input('mobile_number'));

        $request->merge([
            'mobile_number_prefix' => $prefix ?: null,
            'mobile_number' => $mobileNumber ?: null,
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'mobile_number_prefix' => ['nullable', 'required_with:mobile_number', 'regex:/^\+\d{1,4}$/'],
            'mobile_number' => ['nullable', 'digits_between:1,12'],
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'mobile_number.digits_between' => 'Contact number must contain 1 to 12 digits.',
            'profile_picture.max' => 'Profile image must not be greater than 2 MB.',
        ]);

        if ($request->hasFile('profile_picture')) {
            // Delete old image if it exists
            if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            // Store new image
            $path = $request->file('profile_picture')->store('users/profile_pictures', 'public');
            $validated['profile_picture'] = $path;
        }

        // Only include prefix if user provided mobile, otherwise clear both
        if (empty($validated['mobile_number'])) {
            $validated['mobile_number_prefix'] = null;
            $validated['mobile_number'] = null;
        }

        $user->update($validated);

        return back()->with('success', 'Profile updated successfully!');
    }

    /**
     * Change Password
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string',
            'new_password' => 'required|string|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        // Check if old password matches
        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'success' => false,
                'errors' => ['old_password' => ['Old password is incorrect.']],
            ]);
        }

        // Prevent same password reuse
        if (Hash::check($request->new_password, $user->password)) {
            return response()->json([
                'success' => false,
                'errors' => ['new_password' => ['New password cannot be same as old password.']],
            ]);
        }

        // Update password
        $user->password = Hash::make($request->new_password);
        $user->plain_password = $request->new_password;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully!'
        ]);
    }
}
