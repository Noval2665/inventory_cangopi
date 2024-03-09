<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Carbon\Carbon;
use App\Models\Inventory;
use Symfony\Component\Console\Descriptor\Descriptor;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page;
        $per_page = $request->per_page ?? 10000;
        $search = $request->search;

        $inventories = Inventory::when($search, function ($query, $search) {
            return $query->where('name', 'LIKE', '%' . $search . '%');
        })
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data Gudang',
            'inventories' => $inventories,
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
            'inventory_type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $inventory = Inventory::where('inventory_type', $request->inventory_type)->withTrashed()->first();

            if ($inventory) {
                $updateInventory = Inventory::where('id', $inventory->id)->update([
                    'is_active' => 1,
                    'user_id' => auth()->user()->id,
                ]);

                if (!$updateInventory) {
                    throw new HttpException(400, 'Gagal mengubah data gudang');
                }
            } else {

                $createInventory = Inventory::create([
                    'inventory_type' => ucwords($request->inventory_type),
                    'user_id' => auth()->user()->id,
                ]);

                if (!$createInventory) {
                    throw new HttpException(400, 'Gagal membuat data gudang');
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil membuat data gudang',
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
    public function show(string $id)
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
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'inventory_type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $updateInventory = Inventory::where('id', $id)->update([
            'inventory_type' => $request->inventory_type,
            'user_id' => auth()->user()->id,
        ]);

        if (!$updateInventory) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui data Gudang',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil memperbarui data Gudang',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Inventory $inventory)
    {
        $user = auth()->user();

        if ($user->role->name != 'Admin') {
            $this->deactivate($inventory->id);
        } else {
            if ($inventory->storages()->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak dapat menghapus data gudang yang memiliki storage terkait'
                ], 422);
            }

            if (!$inventory->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data Gudang',
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menghapus data Gudang',
            ], 200);
        }
        return response()->json([
            'status' => 'success',
            'message' => $user->role->name != 'Admin' ? 'Berhasil menonaktifkan data Gudang' : 'Berhasil menghapus data Gudang',
        ], 200);
    }

    public function deactivate($id)
    {
        $updateInventory = Inventory::where('id', $id)->update([
            'is_active' => 0,
            'deactivated_at' => Carbon::now(),
        ]);

        if (!$updateInventory) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menonaktifkan Gudang',
            ]);
        }
    }
}
