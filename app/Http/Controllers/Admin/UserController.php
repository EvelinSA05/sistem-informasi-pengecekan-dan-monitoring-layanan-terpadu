<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
        $search = $request->get('search', '');

        $users = User::where('role', 'tester')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('nip', 'like', "%{$search}%")
                      ->orWhere('bidang', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->get(['id', 'name', 'email', 'nip', 'bidang', 'status', 'role', 'created_at']);

        $stats = [
            'total'    => User::where('role', 'tester')->count(),
            'aktif'    => User::where('role', 'tester')->where('status', 'aktif')->count(),
            'nonaktif' => User::where('role', 'tester')->where('status', 'tidak aktif')->count(),
        ];

        return Inertia::render('Admin/ManajemenUser', [
            'users'  => $users,
            'stats'  => $stats,
            'search' => $search,
        ]);
    }

    /**
     * Simpan tester baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'   => 'required|string|max:100',
            'email'  => 'required|string|lowercase|email|max:150|unique:users,email',
            'nip'    => 'nullable|string|digits:18|unique:users,nip',
            'bidang' => 'nullable|string|max:100',
            'status' => 'required|in:aktif,tidak aktif',
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'username' => explode('@', $request->email)[0] . '_' . rand(1000, 9999),
            'nip'      => $request->nip,
            'bidang'   => $request->bidang,
            'status'   => $request->status,
            'role'     => 'tester',
            'password' => Hash::make('password123'), // default password
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Tester berhasil ditambahkan.');
    }

    /**
     * Update data tester.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'name'   => 'required|string|max:100',
            'email'  => 'required|string|lowercase|email|max:150|unique:users,email,' . $user->id,
            'nip'    => 'nullable|string|digits:18|unique:users,nip,' . $user->id,
            'bidang' => 'nullable|string|max:100',
            'status' => 'required|in:aktif,tidak aktif',
        ]);

        $user->update([
            'name'   => $request->name,
            'email'  => $request->email,
            'nip'    => $request->nip,
            'bidang' => $request->bidang,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Data tester berhasil diperbarui.');
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

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Tester berhasil dihapus.');
    }
}
