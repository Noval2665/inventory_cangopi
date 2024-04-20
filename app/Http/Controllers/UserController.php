<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['can:manage,users']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page;
        $per_page = $request->per_page;
        $search = $request->search;

        if ($search) {
            $page = 1;
        }

        $role_id = $request->role_id;
        $role = $request->role;

        $users = User::with(['role'])
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', '%' . $search . '%');
            })
            ->when($role_id, function ($query, $role_id) {
                return $query->where('role_id', $role_id);
            })
            ->when($role, function ($query, $role) {
                return $query->whereHas('role', function ($query) use ($role) {
                    return $query->where('name', $role);
                });
            })
            ->where('id', '!=', 1)
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data user',
            'users' => $users
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'name' => 'required|string',
            'password' => 'required',
            'role_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first()
            ], 422);
        }

        $user = User::where('username', $request->username)->withTrashed()->first();

        if ($user) {
            $user->restore();
            $user->update($validator->validated());
        } else {
            if (!User::create($validator->validated())) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data gagal ditambahkan'
                ], 400);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil ditambahkan'
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = User::find($id)->load(['role']);

        if (!$data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'success',
            'data' => $data
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = User::find($id);

        if (!$data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data user tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'role' => 'required|exists:roles,id'
        ]);

        if (User::where('username', $request->username)->where('id', '!=', $id)->exists()) {
            if ($data->username != $request->username) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Username sudah digunakan'
                ], 422);
            }
        }

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first()
            ], 422);
        }

        $data->update([
            'username' => $request->username,
            'name' => $request->name,
            'role_id' => $request->role
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil diubah'
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = User::find($id);

        if (!$data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data user tidak ditemukan'
            ], 404);
        }

        $data->update(['role_id' => null]);
        $data->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil dihapus'
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|exists:users',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first()
            ], 422);
        }

        $data = User::where('username', $request->username)->first();

        $data->update([
            'password' => bcrypt($request->password)
        ]);

        $passwordResetToken = $this->generatePasswordResetToken($data->username);

        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil diubah',
            'token' => $passwordResetToken
        ], 200);
    }

    // private function generatePasswordResetToken($username)
    // {
    //     $token = mt_rand(10000, 99999);

    //     $checkToken = PasswordResetToken::where('token', $token)->first();

    //     while ($checkToken) {
    //         $token = mt_rand(10000, 99999);
    //         $checkToken = PasswordResetToken::where('token', $token)->first();
    //     }

    //     PasswordResetToken::create([
    //         'username' => $username,
    //         'token' => $token
    //     ]);

    //     return $token;
    // }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|exists:users',
            'new_password' => 'required',
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first()
            ], 422);
        }

        // $checkToken = PasswordResetToken::where('username', $request->username)
        //     ->where('token', $request->token)
        //     ->first();

        // if (!$checkToken) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Token tidak valid, silahkan minta token baru untuk reset password ya'
        //     ], 422);
        // }

        $data = User::where('username', $request->username)->first();

        $$data->update([
            'password' => bcrypt($request->new_password)
        ]);

        // $checkToken->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil diubah'
        ], 200);
    }
}
