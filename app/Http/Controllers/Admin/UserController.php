<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Tampilkan daftar semua tester.
     */
    public function index(Request $request): Response
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
        ]);

        $search = (string) $request->get('search', '');

        $users = User::where('role', 'tester')
            ->when(!empty($search), function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 5), ['id', 'name', 'email', 'status', 'role', 'created_at'])
            ->withQueryString();

        $stats = [
            'total'    => User::where('role', 'tester')->count(),
            'aktif'    => User::where('role', 'tester')->where('status', 'aktif')->count(),
            'nonaktif' => User::where('role', 'tester')->where('status', 'tidak aktif')->count(),
        ];

        return Inertia::render('Admin/ManajemenUser', [
            'users'  => $users,
            'stats'  => $stats,
            'search' => $search,
            'flash'  => [
                'success' => session('success'),
                'error'   => session('error'),
            ],
        ]);
    }

    /**
     * Simpan tester baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|string|lowercase|email|max:150|unique:users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'status'   => 'required|in:aktif,tidak aktif',
        ]);

        try {
            User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'username' => explode('@', $validated['email'])[0] . '_' . rand(1000, 9999),
                'status'   => $validated['status'],
                'role'     => 'tester',
                'password' => Hash::make($validated['password']),
            ]);

            return redirect()->route('admin.users.index')
                ->with('success', 'Tester berhasil ditambahkan.');
        } catch (\Throwable $e) {
            Log::error('Gagal menambahkan tester baru: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.users.index')
                ->with('error', 'Terjadi kesalahan saat menambahkan tester. Silakan periksa kembali input Anda.');
        }
    }

    /**
     * Update data tester.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name'   => 'required|string|max:100',
            'email'  => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'status' => 'required|in:aktif,tidak aktif',
        ]);

        try {
            $user->update([
                'name'   => $validated['name'],
                'email'  => $validated['email'],
                'status' => $validated['status'],
            ]);

            return redirect()->route('admin.users.index')
                ->with('success', 'Data tester berhasil diperbarui.');
        } catch (\Throwable $e) {
            Log::error('Gagal memperbarui data tester: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.users.index')
                ->with('error', 'Terjadi kesalahan saat memperbarui data tester. Silakan coba lagi.');
        }
    }

    /**
     * Hapus tester.
     */
    public function destroy(User $user): RedirectResponse
    {
        // Pastikan yang dihapus hanya tester, bukan admin
        if ($user->role === 'admin') {
            return redirect()->route('admin.users.index')
                ->with('error', 'Akun admin tidak dapat dihapus.');
        }

        try {
            $user->delete();

            return redirect()->route('admin.users.index')
                ->with('success', 'Tester berhasil dihapus.');
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus tester: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.users.index')
                ->with('error', 'Gagal menghapus tester. Akun mungkin masih memiliki riwayat tugas atau pengecekan.');
        }
    }
}
