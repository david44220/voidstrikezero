<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Mail\MailService;
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

        AuthService::login($user);
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
            'email_verified_at' => null, // Explicitly require verification
            'xp' => 0,
            'level' => 1,
        ]);

        $user->save();

        // Generate email verification token
        $rawVerifyToken = bin2hex(random_bytes(32));
        $verifyTokenHash = hash('sha256', $rawVerifyToken);
        Database::insert('email_verifications', [
            'user_id' => $user->id,
            'token_hash' => $verifyTokenHash,
            'expires_at' => gmdate('Y-m-d H:i:s', time() + 86400),
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);

        // Send email verification via mail abstraction
        MailService::sendEmailVerification($email, $rawVerifyToken);

        AuthService::login($user);
        flash('success', __('auth.registered_success'));

        return redirect('/dashboard');
    }

    public function verifyEmail(Request $request): Response
    {
        $rawToken = (string) $request->query('token');
        $email = strtolower(trim((string) $request->query('email')));

        if (empty($rawToken) || empty($email)) {
            flash('error', 'Invalid verification link.');
            return redirect('/dashboard');
        }

        $tokenHash = hash('sha256', $rawToken);
        $record = Database::selectOne(
            "SELECT ev.*, u.id as matched_user_id 
             FROM email_verifications ev
             JOIN users u ON u.id = ev.user_id
             WHERE LOWER(u.email) = LOWER(:email) AND ev.token_hash = :hash AND ev.expires_at > CURRENT_TIMESTAMP",
            [':email' => $email, ':hash' => $tokenHash]
        );

        if (!$record) {
            flash('error', 'Verification token is invalid or has expired.');
            return redirect('/dashboard');
        }

        $user = User::find((int) $record['matched_user_id']);
        if ($user) {
            $user->email_verified_at = gmdate('Y-m-d H:i:s');
            $user->save();

            // Purge consumed verification record
            Database::delete('email_verifications', 'user_id = :uid', [':uid' => $user->id]);

            flash('success', 'Pilot neural-link email verified successfully!');
        }

        return redirect('/dashboard');
    }

    public function resendVerification(Request $request): Response
    {
        $user = AuthService::user();
        if (!$user) {
            return redirect('/login');
        }

        if ($user->isEmailVerified()) {
            flash('info', 'Your email address is already verified.');
            return redirect('/dashboard');
        }

        // Purge old tokens
        Database::delete('email_verifications', 'user_id = :uid', [':uid' => $user->id]);

        // Generate new token
        $rawVerifyToken = bin2hex(random_bytes(32));
        $verifyTokenHash = hash('sha256', $rawVerifyToken);
        Database::insert('email_verifications', [
            'user_id' => $user->id,
            'token_hash' => $verifyTokenHash,
            'expires_at' => gmdate('Y-m-d H:i:s', time() + 86400),
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);

        MailService::sendEmailVerification($user->email, $rawVerifyToken);
        flash('success', 'Verification link dispatched to your email address.');

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
                // Purge prior reset tokens for this user
                Database::delete('password_resets', 'email = :email', [':email' => $email]);

                $rawToken = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $rawToken);
                $expiresAt = gmdate('Y-m-d H:i:s', time() + 3600);

                Database::insert('password_resets', [
                    'email' => $email,
                    'token_hash' => $tokenHash,
                    'expires_at' => $expiresAt,
                    'created_at' => gmdate('Y-m-d H:i:s'),
                ]);

                // Deliver securely via MailService without exposing raw token in UI
                MailService::sendPasswordReset($email, $rawToken);
            }
        }

        // Always show the same generic confirmation to prevent user enumeration
        flash('success', __('auth.reset_link_sent'));
        return redirect('/forgot-password');
    }

    public function showResetPassword(Request $request): Response
    {
        $token = (string) $request->query('token');
        $email = (string) $request->query('email');
        return view('auth.reset_password', [
            'title' => __('auth.reset_title'),
            'token' => $token,
            'email' => $email,
        ]);
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
            return redirect('/reset-password?token=' . urlencode((string) $request->input('token')) . '&email=' . urlencode((string) $request->input('email')));
        }

        $rawToken = (string) $request->input('token');
        $email = strtolower(trim((string) $request->input('email')));
        $password = (string) $request->input('password');

        $tokenHash = hash('sha256', $rawToken);
        $reset = Database::selectOne(
            "SELECT * FROM password_resets 
             WHERE email = :email AND token_hash = :hash AND expires_at > CURRENT_TIMESTAMP",
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

            // Invalidate existing sessions
            Session::getInstance()->destroy();

            flash('success', __('auth.password_reset_success'));
            return redirect('/login');
        }

        flash('error', __('auth.invalid_token'));
        return redirect('/forgot-password');
    }
}
