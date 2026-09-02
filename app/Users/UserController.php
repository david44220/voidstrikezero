<?php

declare(strict_types=1);

namespace App\Users;

use App\Auth\AuthService;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Matches\MatchRepository;

class UserController
{
    public function dashboard(Request $request): Response
    {
        $user = AuthService::user();
        if (!$user) {
            return redirect('/login');
        }

        $stats = MatchRepository::getUserStats($user->id);
        $rank = MatchRepository::getUserRank($user->id);
        $history = MatchRepository::getUserMatchHistory($user->id, 10);
        $pb = MatchRepository::getPersonalBest($user->id);

        return view('dashboard.profile', [
            'user' => $user,
            'stats' => $stats,
            'rank' => $rank,
            'history' => $history,
            'pb' => $pb,
            'title' => __('nav.dashboard'),
        ]);
    }

    public function showSettings(Request $request): Response
    {
        $user = AuthService::user();
        return view('dashboard.settings', [
            'user' => $user,
            'title' => __('dashboard.settings_title'),
        ]);
    }

    public function updateSettings(Request $request): Response
    {
        $user = AuthService::user();
        if (!$user) {
            return redirect('/login');
        }

        $validator = Validator::make($request->all(), [
            'display_name' => 'required|max:50',
            'selected_vehicle' => 'in:striker,titan,phantom',
            'preferred_locale' => 'in:en,fr',
        ]);

        if ($validator->fails()) {
            flash('error', $validator->firstError());
            return redirect('/settings');
        }

        $user->display_name = trim((string) $request->input('display_name'));
        $user->selected_vehicle = (string) $request->input('selected_vehicle', 'striker');
        $user->preferred_locale = (string) $request->input('preferred_locale', 'en');

        // Handle avatar upload if provided
        $file = $request->file('avatar');
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $avatarError = $this->handleAvatarUpload($user, $file);
            if ($avatarError !== null) {
                flash('error', $avatarError);
                return redirect('/settings');
            }
        }

        $user->save();
        session()->set('locale', $user->preferred_locale);

        flash('success', __('dashboard.profile_updated'));
        return redirect('/settings');
    }

    public function changePassword(Request $request): Response
    {
        $user = AuthService::user();
        if (!$user) {
            return redirect('/login');
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            flash('error', $validator->firstError());
            return redirect('/settings');
        }

        $currentPass = (string) $request->input('current_password');
        $newPass = (string) $request->input('new_password');

        if (!$user->verifyPassword($currentPass)) {
            flash('error', 'Current passcode verification failed. Access denied.');
            return redirect('/settings');
        }

        $user->password_hash = AuthService::hashPassword($newPass);
        $user->save();

        // Invalidate prior session state and regenerate security session
        Session::getInstance()->regenerate(true);

        flash('success', 'Passcode updated successfully. Session re-authenticated.');
        return redirect('/settings');
    }

    private function handleAvatarUpload(User $user, array $file): ?string
    {
        // 1. Max size 2MB
        $maxSize = 2 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            return "Avatar file size exceeds 2MB limit.";
        }

        // 2. Real MIME inspection via finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!array_key_exists($mime, $allowedMimes)) {
            return "Invalid image format. Only JPEG, PNG, and WebP are allowed.";
        }

        // 3. Inspect image dimensions to prevent polyglot file attacks
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false || $imageInfo[0] <= 0 || $imageInfo[1] <= 0) {
            return "Corrupted or invalid image content.";
        }

        if ($imageInfo[0] > 2000 || $imageInfo[1] > 2000) {
            return "Image dimensions cannot exceed 2000x2000 pixels.";
        }

        $extension = $allowedMimes[$mime];
        $safeName = 'avatar_' . $user->id . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destDir = dirname(__DIR__, 2) . '/public/uploads/avatars';

        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $destination = $destDir . DIRECTORY_SEPARATOR . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return "Failed to save avatar image.";
        }

        // Delete previous custom avatar if exists
        if ($user->avatar_url && str_starts_with($user->avatar_url, '/uploads/avatars/')) {
            $oldPath = dirname(__DIR__, 2) . '/public' . $user->avatar_url;
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        $user->avatar_url = '/uploads/avatars/' . $safeName;
        return null;
    }
}
