<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as RulesPassword;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    // Metode Register Pengguna
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:255', 'unique:users'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', RulesPassword::min(8)],
                'phone' => ['required', 'string', 'max:255'],
            ]);

            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
            ]);

            $karyawanRole = Role::where('name', 'Karyawan')->first();
            $user->assignRole($karyawanRole);

            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ], 'User Registered');
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error->getMessage()
            ], 'Authentication Failed', 500);
        }
    }

    public function publicregister(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:255', 'unique:users'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', RulesPassword::min(8)],
                'phone' => ['required', 'string', 'max:255'],
            ]);

            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
            ]);

            $karyawanRole = Role::where('name', 'Customer')->first();
            $user->assignRole($karyawanRole);

            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ], 'User Registered');
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error->getMessage()
            ], 'Authentication Failed', 500);
        }
    }
    // Metode Login Pengguna
    public function login(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required|string',
                'password' => 'required|string'
            ]);

            $user = User::where('username', $request->username)->first();

            if (!$user) {
                return ResponseFormatter::error([
                    'message' => 'User not found'
                ], 'Authentication Failed', 404);
            }

            $credentials = [
                'email' => $user->email,
                'password' => $request->input('password')
            ];

            if (!Auth::attempt($credentials)) {
                return ResponseFormatter::error([
                    'message' => 'Unauthorized'
                ], 'Authentication Failed', 401);
            }

            $authenticatedUser = $request->user();

            $tokenResult = $authenticatedUser->createToken('authToken')->plainTextToken;
            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $authenticatedUser
            ], 'Authentication Success');
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something Went Wrong',
                'error' => $error->getMessage()
            ], 'Authentication Failed', 500);
        }
    }

    // Ambil Data Pengguna
    public function fetch(Request $request)
    {
        return ResponseFormatter::success($request->user(), 'Data Profile User Berhasil diambil');
    }

    // Cek Peran Pengguna
    public function checkUserRoles(Request $request)
    {
        // Mengambil pengguna yang sedang login
        $user = $request->user();

        // Memastikan pengguna telah diinisialisasi dengan role
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Mengambil nama-nama role pengguna
        $roles = $user->getRoleNames(); // Mengembalikan koleksi nama role

        // Mengembalikan response dalam format JSON
        return response()->json([
            'roles' => $roles
        ]);
    }


    // Perbarui Profil Pengguna
    public function updateProfile(Request $request)
    {
        try {
            // Validasi data request
            $data = $request->validate([
                'name' => ['sometimes', 'string', 'max:255'],
                'username' => ['sometimes', 'string', 'max:255', 'unique:users,username,' . $request->user()->id],
                'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $request->user()->id],
                'phone' => ['sometimes', 'string', 'max:255'],
            ]);

            // Ambil pengguna yang terautentikasi
            $user = Auth::user();

            // Perbarui data pengguna
            $user->update($data);
            // Kembalikan respons sukses
            return ResponseFormatter::success($user, 'Profile updated successfully');
        } catch (Exception $error) {
            // Kembalikan respons error jika terjadi kesalahan
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error->getMessage()
            ], 'Profile Update Failed', 500);
        }
    }


    // Logout Pengguna
    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken()->delete();
        return ResponseFormatter::success($token, 'Token Revoked');
    }

    // Manajemen Pengguna - Ambil Daftar Pengguna
    public function getAllUsers(Request $request)
    {
        // Pastikan hanya Pimpinan yang dapat mengakses ini
        if (!$request->user()->hasRole('Pimpinan')) {
            return ResponseFormatter::error([
                'message' => 'Unauthorized'
            ], 'Access Denied', 403);
        }

        return ResponseFormatter::success(User::all(), 'Users fetched successfully');
    }
    // Pastikan Anda mengimpor facade Log

    public function assignRole(Request $request, $id)
    {
        try {
            // Ensure only Pimpinan can access this
            if (!$request->user()->hasRole('Pimpinan')) {
                return ResponseFormatter::error([
                    'message' => 'Unauthorized'
                ], 'Access Denied', 403);
            }

            // Validate the incoming role
            $request->validate([
                'role' => 'required|string|exists:roles,name',
            ]);

            // Log the request details
            Log::info('Assign Role Request', [
                'user_id' => $id,
                'requested_role' => $request->role
            ]);

            // Check if the User object has a valid ID
            $user = User::find($id);
            if (!$user) {
                return ResponseFormatter::error([
                    'message' => 'User not found or invalid'
                ], 'User Assignment Error', 404);
            }

            // Get the role based on the provided role name
            $role = Role::where('name', $request->role)->first();
            if (!$role) {
                return ResponseFormatter::error([
                    'message' => 'Role not found'
                ], 'Role Assignment Error', 404);
            }

            // Remove any existing roles before assigning a new one (optional)
            $user->roles()->detach();

            // Assign the role to the user
            $user->assignRole($role);

            return ResponseFormatter::success([], 'Role assigned successfully');
        } catch (\Exception $e) {
            // Log the exception message
            Log::error('Role Assignment Error', [
                'error' => $e->getMessage()
            ]);

            return ResponseFormatter::error([
                'message' => $e->getMessage()
            ], 'Role assignment failed', 500);
        }
    }





    // Manajemen Pengguna - Hapus Pengguna
    public function deleteUser(Request $request, User $user)
    {
        if (!$request->user()->hasRole('Pimpinan')) {
            Log::error('Unauthorized access attempt by user ID: ' . $request->user()->id);
            return ResponseFormatter::error([
                'message' => 'Unauthorized'
            ], 'Access Denied', 403);
        }

        try {
            $deleted = $user->delete();
            Log::info('User deletion attempted for user ID: ' . $user->id);

            if ($deleted) {
                Log::info('User deleted successfully for user ID: ' . $user->id);
                return ResponseFormatter::success([], 'User deleted successfully');
            } else {
                Log::error('Failed to delete user for user ID: ' . $user->id);
                return ResponseFormatter::error([], 'Failed to delete user', 500);
            }
        } catch (Exception $e) {
            Log::error('Exception during user deletion: ' . $e->getMessage());
            return ResponseFormatter::error([], 'Failed to delete user', 500);
        }
    }

    // Manajemen Pengguna - Nonaktifkan Pengguna
    public function toggleStatus(Request $request, User $user)
    {
        // Pastikan hanya Pimpinan yang dapat mengakses ini
        if (!$request->user()->hasRole('Pimpinan')) {
            return ResponseFormatter::error([
                'message' => 'Unauthorized'
            ], 'Access Denied', 403);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return ResponseFormatter::success($user, 'User status updated successfully');
    }
}
