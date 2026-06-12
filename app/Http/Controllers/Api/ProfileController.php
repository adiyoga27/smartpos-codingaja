<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends ApiController
{
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
            $validated['photo'] = $request->file('photo')->store('users', 'public');
        }

        if ($validated['email'] !== $user->email) {
            $validated['email_verified_at'] = null;
        }

        $user->update($validated);

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'photo' => $user->photo ? asset('storage/'.$user->photo) : null,
        ], 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return $this->error('Password saat ini salah.', 422);
        }

        $user->update(['password' => $validated['password']]);

        return $this->success(null, 'Password berhasil diperbarui.');
    }
}
