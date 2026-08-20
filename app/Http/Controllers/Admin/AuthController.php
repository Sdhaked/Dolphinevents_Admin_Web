<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\LoginOtpMail;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
        $otpSession = $this->adminLoginOtpSession();

        return view('admin.auth.login', [
            'showOtpForm' => (bool) $otpSession,
            'otpEmail' => $otpSession['email'] ?? old('email'),
            'resendWaitSeconds' => $this->adminLoginOtpWaitSeconds(),
        ]);
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
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'No admin account was found for this email.',
            ])->withInput($request->only('email'));
        }

        if (!$this->sendAdminLoginOtp($user)) {
            return back()
                ->withErrors(['email' => $this->adminLoginOtpDeliveryErrorMessage()])
                ->withInput($request->only('email'));
        }

        return back()
            ->withInput($request->only('email'))
            ->with('success', 'OTP sent successfully');
    }

    public function verifyLoginOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ]);

        $otpSession = $this->adminLoginOtpSession();

        if (!$otpSession || ($otpSession['email'] ?? null) !== $validated['email']) {
            return redirect()->route('login')
                ->withErrors(['otp' => 'OTP session expired. Please request a new OTP.'])
                ->withInput($request->only('email'));
        }

        if (!Hash::check($validated['otp'], $otpSession['otp_hash'] ?? '')) {
            return back()
                ->withErrors(['otp' => 'Invalid OTP. Please check and try again.'])
                ->withInput($request->only('email'));
        }

        $user = User::find($otpSession['user_id'] ?? null);

        if (!$user || $user->email !== $validated['email']) {
            $request->session()->forget('admin_login_otp');

            return redirect()->route('login')
                ->withErrors(['email' => 'Admin account not found.']);
        }

        Auth::login($user);
        $request->session()->forget('admin_login_otp');
        $request->session()->regenerate();
        $this->setDefaultActiveEvent($request);

        return redirect()->intended('admin')->with('success', 'Welcome back!');
    }

    public function resendLoginOtp(Request $request)
    {
        $otpSession = $this->adminLoginOtpSession();

        if (!$otpSession) {
            return redirect()->route('login')
                ->withErrors(['email' => 'OTP session expired. Please request a new OTP.']);
        }

        $waitSeconds = $this->adminLoginOtpWaitSeconds();
        if ($waitSeconds > 0) {
            return back()
                ->withInput(['email' => $otpSession['email'] ?? null])
                ->with('warning', 'Please wait ' . $waitSeconds . ' seconds before resending OTP.');
        }

        $user = User::find($otpSession['user_id'] ?? null);
        if (!$user) {
            $request->session()->forget('admin_login_otp');

            return redirect()->route('login')
                ->withErrors(['email' => 'Admin account not found.']);
        }

        if (!$this->sendAdminLoginOtp($user)) {
            return back()
                ->withInput(['email' => $user->email])
                ->withErrors(['email' => $this->adminLoginOtpDeliveryErrorMessage()]);
        }

        return back()
            ->withInput(['email' => $user->email])
            ->with('success', 'OTP sent successfully');
    }

    public function changeLoginEmail(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()
                ->withErrors(['email' => 'No admin account was found for this email.'])
                ->withInput($request->only('email'));
        }

        if (!$this->sendAdminLoginOtp($user)) {
            return back()
                ->withErrors(['email' => $this->adminLoginOtpDeliveryErrorMessage()])
                ->withInput($request->only('email'));
        }

        return redirect()->route('login')
            ->withInput($request->only('email'))
            ->with('success', 'OTP sent successfully');
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

    private function sendAdminLoginOtp(User $user): bool
    {
        if ($this->adminLoginOtpUsesNonDeliveringMailer()) {
            Log::warning('Admin login OTP was not sent because the mailer does not deliver email.', [
                'mailer' => config('mail.default'),
                'email' => $user->email,
            ]);

            return false;
        }

        $otp = (string) random_int(100000, 999999);

        try {
            Mail::to($user->email)->send(new LoginOtpMail($otp, $user));
        } catch (\Throwable $exception) {
            Log::error('Admin login OTP email failed to send.', [
                'email' => $user->email,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }

        session()->put('admin_login_otp', [
            'user_id' => $user->id,
            'email' => $user->email,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(15)->toIso8601String(),
            'resend_available_at' => now()->addSeconds(60)->toIso8601String(),
        ]);

        return true;
    }

    private function adminLoginOtpUsesNonDeliveringMailer(): bool
    {
        return in_array(config('mail.default'), ['array', 'log', 'null'], true);
    }

    private function adminLoginOtpDeliveryErrorMessage(): string
    {
        return 'OTP email could not be sent. Please check mail configuration and try again.';
    }

    private function adminLoginOtpSession(): ?array
    {
        $otpSession = session('admin_login_otp');

        if (!is_array($otpSession) || empty($otpSession['expires_at'])) {
            return null;
        }

        if (now()->gt(Carbon::parse($otpSession['expires_at']))) {
            session()->forget('admin_login_otp');
            return null;
        }

        return $otpSession;
    }

    private function adminLoginOtpWaitSeconds(): int
    {
        $otpSession = $this->adminLoginOtpSession();
        $resendAvailableAt = $otpSession['resend_available_at'] ?? null;

        if (!$resendAvailableAt) {
            return 0;
        }

        $availableAt = Carbon::parse($resendAvailableAt);

        return $availableAt->isFuture() ? (int) now()->diffInSeconds($availableAt) : 0;
    }

    private function setDefaultActiveEvent(Request $request): void
    {
        $event = Event::latest()->first();
        if ($event) {
            $request->session()->put('active_event_id', $event->id);
            return;
        }

        $request->session()->forget('active_event_id');
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
