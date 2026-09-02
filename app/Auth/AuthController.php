<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Users\User;

class AuthController
{
    public function showLogin(Request $request): Response
    {
        return view('auth.login', ['title' => __('nav.login')]);
    }

    public function login(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            flash('error', $validator->firstError());
            session()->flashInput($request->all());
            return redirect('/login');
        }

        $login = trim((string) $request->input('login'));
        $password = (string) $request->input('password');
        $remember = (bool) $request->input('remember', false);

        // Find user by username or email
        $user = User::findByUsername($login) ?? User::findByEmail($login);

        if (!$user || !$user->verifyPassword($password)) {
            flash('error', __('auth.invalid_credentials'));
            session()->flashInput($request->all());
            return redirect('/login');
        }

        if (!$user->isActive()) {
            flash('error', __('auth.account_banned'));
            return redirect('/login');
        }

        AuthService::login($user, $remember);
        flash('success', __('auth.login_title') . ' // ' . e($user->display_name));

        return redirect('/dashboard');
    }

    public function showRegister(Request $request): Response
    {
        return view('auth.register', ['title' => __('nav.register')]);
    }

    public function register(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|alpha_dash|min:3|max:30|unique:users,username',
            'email' => 'required|email|max:190|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'display_name' => 'max:50',
            'selected_vehicle' => 'in:striker,titan,phantom',
        ]);

        if ($validator->fails()) {
            flash('error', $validator->firstError());
            session()->flashInput($request->all());
            return redirect('/register');
        }

        $username = trim((string) $request->input('username'));
        $email = strtolower(trim((string) $request->input('email')));
        $displayName = trim((string) $request->input('display_name')) ?: $username;
        $vehicle = (string) $request->input('selected_vehicle', 'striker');
        $password = (string) $request->input('password');

        $user = new User([
            'username' => $username,
            'email' => $email,
            'password_hash' => AuthService::hashPassword($password),
            'display_name' => $displayName,
            'selected_vehicle' => $vehicle,
            'preferred_locale' => session()->get('locale', 'en'),
            'role' => 'player',
            'status' => 'active',
            'email_verified_at' => gmdate('Y-m-d H:i:s'), // Auto-verified in local environment
            'xp' => 0,
            'level' => 1,
        ]);

        $user->save();

        AuthService::login($user);
        flash('success', __('auth.registered_success'));

        return redirect('/dashboard');
    }

    public function logout(Request $request): Response
    {
        AuthService::logout();
        flash('success', __('auth.logout_success'));
        return redirect('/');
    }

    public function showForgotPassword(Request $request): Response
    {
        return view('auth.forgot_password', ['title' => __('auth.reset_title')]);
    }

    public function sendResetLink(Request $request): Response
    {
        $email = strtolower(trim((string) $request->input('email')));
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $user = User::findByEmail($email);
            if ($user) {
                $rawToken = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $rawToken);
                $expiresAt = gmdate('Y-m-d H:i:s', time() + 3600);

                Database::insert('password_resets', [
                    'email' => $email,
                    'token_hash' => $tokenHash,
                    'expires_at' => $expiresAt,
                    'created_at' => gmdate('Y-m-d H:i:s'),
                ]);

                // Store in flash for testing/demo display
                flash('info', "Recovery token generated: {$rawToken} (Valid for 1 hour)");
            }
        }

        flash('success', __('auth.reset_link_sent'));
        return redirect('/forgot-password');
    }

    public function showResetPassword(Request $request): Response
    {
        $token = (string) $request->query('token');
        return view('auth.reset_password', ['title' => __('auth.reset_title'), 'token' => $token]);
    }

    public function resetPassword(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            flash('error', $validator->firstError());
            return redirect('/reset-password?token=' . urlencode((string) $request->input('token')));
        }

        $rawToken = (string) $request->input('token');
        $email = strtolower(trim((string) $request->input('email')));
        $password = (string) $request->input('password');

        $tokenHash = hash('sha256', $rawToken);
        $reset = Database::selectOne(
            "SELECT * FROM password_resets WHERE email = :email AND token_hash = :hash AND expires_at > CURRENT_TIMESTAMP",
            [':email' => $email, ':hash' => $tokenHash]
        );

        if (!$reset) {
            flash('error', __('auth.invalid_token'));
            return redirect('/forgot-password');
        }

        $user = User::findByEmail($email);
        if ($user) {
            $user->password_hash = AuthService::hashPassword($password);
            $user->save();

            // Purge used tokens
            Database::delete('password_resets', 'email = :email', [':email' => $email]);

            flash('success', __('auth.password_reset_success'));
            return redirect('/login');
        }

        flash('error', __('auth.invalid_token'));
        return redirect('/forgot-password');
    }
}
