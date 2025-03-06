<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware(['can:manage,roles']);
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

        $roles = Role::when($search, function ($query, $search) {
            return $query->where('name', 'like', '%' . $search . '%');
        })
            ->where('id', '!=', 1)
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data kategori user',
            'roles' => $roles,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'permissions' => 'present|array',
            'permissions.*.parent' => 'required|string',
            'permissions.*.action' => 'required|string',
            'permissions.*.subject' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $role = Role::where('name', $request->name)->withTrashed()->first();

        if ($role) {
            $role->restore();
            $role->update($validator->validated());
        } else {
            if (!Role::create($validator->validated())) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal membuat data kategori user'
                ], 400);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil membuat data kategori user'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'success',
            'data' => $role
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'permissions' => 'present|array',
            'permissions.*.parent' => 'required|string',
            'permissions.*.path' => 'required|string',
            'permissions.*.action' => 'required|string',
            'permissions.*.subject' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        if (strtolower($role->name) !== strtolower($request->name) && Role::where('name', $request->name)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kategori user sudah ada'
            ], 422);
        }

        if (!$role->update($validator->validated())) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah data kategori user'
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengubah data kategori user'
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $cantDeletedRoleId = [1, 2];

        if (in_array($role->id, $cantDeletedRoleId)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kategori user ini tidak bisa dihapus'
            ], 422);
        }

        if (User::where('role_id', $role->id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kategori user tidak bisa dihapus karena sudah digunakan oleh user'
            ], 422);
        }

        if (!$role->delete()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus data kategori user'
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil menghapus data kategori user'
        ], 200);
    }
}
