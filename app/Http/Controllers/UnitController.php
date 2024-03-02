<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Carbon\Carbon;
use App\Models\Unit;
use Symfony\Component\Console\Descriptor\Descriptor;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page;
        $per_page = $request->per_page ?? 10000;
        $search = $request->search;

        $units = Unit::when($search, function ($query, $search) {
            return $query->where('unit_name', 'like', '%' . $search . '%');
        })
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil menampilkan data satuan',
            'units' => $units,
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
            'unit_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $createUnit = Unit::create([
            'unit_name' => ucwords($request->unit_name),
            'user_id' => auth()->user()->id,
        ]);

        if (!$createUnit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat data satuan',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil membuat data satuan',
        ], 201);
    }

    public function show(Unit $unit)
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
    public function update(Request $request, Unit $unit)
    {
        $validator = Validator::make($request->all(), [
            'unit_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $updateUnit = $unit->update([
            'unit_name' => ucwords($request->unit_name),
            'user_id' => auth()->user()->id,
        ]);

        if (!$updateUnit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah data satuan',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengubah data satuan',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Unit $unit)
    {
        $userLogin = auth()->user();

        if ($userLogin->role->name != 'Admin') {
            $this->deactivate($unit->id);
        } else {
            if ($unit->products->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak dapat menghapus data satuan yang memiliki produk terkait'
                ], 400);
            }

            if (!$unit->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data satuan'
                ], 400);
            }
        }
        return response()->json([
            'status' => 'success',
            'message' => $userLogin->role->name != 'Admin' ? 'Berhasil menonaktifkan satuan' : 'Berhasil menghapus data satuan',
        ], 200);
    }

    public function deactivate($id)
    {
        $updateUnit = Unit::where('id', $id)->update([
            'is_active' => 0,
            'deactivated_at' => now(),
        ]);

        if (!$updateUnit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menonaktifkan satuan'
            ], 400);
        }
    }
}
