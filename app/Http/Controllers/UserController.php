<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Carbon\Carbon;
use App\Models\User;
use Dotenv\Parser\Value;
use Symfony\Component\Console\Descriptor\Descriptor;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page;
        $per_page = $request->per_page ?? 10000;
        $search = $request->search;

        if ($search) {
            $page = 1;
        }

        $role = $request->role;

        $users = User::with(['role'])
            ->when($search, function ($query, $search) {
                return $query->where('username', 'like', '%' . $search . '%');
            })
            ->when($role, function ($query, $role) {
                return $query->where('role_id', $role);
            })
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil menampilkan data user',
            'users' => $users,
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|unique:users,username',
            'email' => 'required|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'photo' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $createUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role,
            'photo' => $request->photo,
        ]);

        if (!$createUser) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan data user',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'User berhasil ditambahkan',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|unique:users,username' . $user->id,
            'email' => 'required|unique:users,email' . $user->id,
            'role_id' => 'required|exists:roles,id',
            'photo' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $updateUser = $user->update([
            'username' => $request->username,
            'email' => $request->email,
            'role_id' => $request->role,
            'photo' => $request->photo,
        ]);

        if (!$updateUser) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagala mengubah data user',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengubah data user',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $userLogin = auth()->user();

        if ($userLogin->role->name != 'Admin') {
            $this->deactivate($user->id);
        } else {
            if (!$user->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data user'
                ], 400);
            }
        }
        return response()->json([
            'status' => 'success',
            'message' => $userLogin->role->name != 'Admin' ? 'Berhasil menonaktifkan user' : 'Berhasil menghapus data user',
        ], 200);
    }

    public function deactivate($id)
    {
        $updateUser = User::where('id', $id)->update([
            'is_active' => 0,
            'deactivated_at' => now(),
        ]);

        if (!$updateUser) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menonaktifkan user'
            ], 400);
        }
    }
}
