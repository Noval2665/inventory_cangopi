<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Carbon\Carbon;
use App\Models\Storage;
use Symfony\Component\Console\Descriptor\Descriptor;

class StorageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page;
        $per_page = $request->per_page ?? 10000;
        $search = $request->search;

        $storages = Storage::when($search, function ($query, $search) {
            return $query->where('storage_type', 'LIKE', '%' . $search . '%');
        })
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data Penyimpanan',
            'storages' => $storages,
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
        DB::beginTransaction();

        $validator = Validator::make($request->all(), [
            'storage_type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $storage = Storage::where('storage_type', $request->storage_type)->withTrashed()->first();

            if ($storage) {
                $storage->restore();

                $updateStorage = $storage->update([
                    'is_active' => 1,
                    'user_id' => auth()->user()->id,
                ]);

                if (!$updateStorage) {
                    throw new HttpException(400, 'Gagal mengubah data penyimpanan');
                }
            } else {
                $createStorage = Storage::create([
                    'storage_type' => $request->storage_type,
                    'user_id' => auth()->user()->id,
                ]);

                if (!$createStorage) {
                    throw new HttpException(400, 'Gagal menambahkan data penyimpanan');
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menambahkan data penyimpanan',
            ], 201);
        } catch (HttpException $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Storage $storage)
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
    public function update(Request $request, Storage $storage)
    {
        $validator = Validator::make($request->all(), [
            'storage_type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $updateStorage = $storage->update([
            'storage_type' => $request->storage_type,
            'inventory_id' => $request->inventory_id,
            'user_id' => auth()->user()->id,
        ]);

        if (!$updateStorage) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui data Penyimpanan',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil memperbarui data Penyimpanan',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Storage $storage)
    {
        $user = auth()->user();

        if ($user->role->name != 'Admin') {
            $this->deactivate($storage->id);
        } else {
            if ($storage->products()->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak dapat menghapus data Penyimpanan produk yang memiliki produk terkait'
                ], 422);
            }

            if (!$storage->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data Penyimpanan',
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menghapus data Penyimpanan',
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'message' => $user->role->name != 'Admin' ? 'Berhasil menonaktifkan Penyimpanan' : 'Berhasil menghapus data Penyimpanan',
        ], 200);
    }

    public function deactivate($id)
    {
        $updateStorage = Storage::where('id', $id)->update([
            'is_active' => 0,
            'deactivated_at' => Carbon::now(),
        ]);

        if (!$updateStorage) {
            return response()->json([

                'status' => 'error',
                'message' => 'Gagal menonaktifkan Storage',
            ], 400);
        }
    }
}
