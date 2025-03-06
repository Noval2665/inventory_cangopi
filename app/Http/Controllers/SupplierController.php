<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Lcobucci\JWT\Validation\Constraint\ValidAt;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page;
        $per_page = $request->per_page ?? 10000;
        $search = $request->search;

        $suppliers = Supplier::when($search, function ($query, $search) {
            return $query->where('supplier_name', 'LIKE', '%' . $search . '%');
        })
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data supplier',
            'suppliers' => $suppliers,
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
            'supplier_name' => 'required|string',
            'phone_number' => 'required|numeric',
            'address' => 'nullable|string',

        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }
        try {
            $supplier = Supplier::where('supplier_name', $request->supplier_name)->withTrashed()->first();

            if ($supplier) {
                $supplier->restore();

                $updateSupplier = $supplier->update([
                    'phone_number' => $request->phone_number,
                    'address' => $request->address,
                    'is_active' => 1,
                    'user_id' => auth()->user()->id,
                ]);

                if (!$updateSupplier) {
                    throw new HttpException(400, 'Gagal mengubah data supplier');
                }
            } else {
                $createSupplier = Supplier::create([
                    'supplier_name' => ucwords($request->supplier_name),
                    'phone_number' => $request->phone_number,
                    'address' => $request->address,
                    'user_id' => auth()->user()->id,
                ]);

                if (!$createSupplier) {
                    throw new HttpException(400, 'Gagal membuat data supplier');
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil membuat data supplier',
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
    public function update(Request $request, Supplier $supplier)
    {
        $validator = Validator::make($request->all(), [
            'supplier_name' => 'required|string',
            'phone_number' => 'required|numeric',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $updateSupplier = $supplier->update([
            'supplier_name' => ucwords($request->supplier_name),
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'user_id' => auth()->user()->id,
        ]);

        if (!$updateSupplier) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah data supplier',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengubah data supplier',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        $user = auth()->user();

        if ($user->role->name != 'Admin') {
            $this->deactivate($supplier->id);
        } else {
            if ($supplier->products()->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak dapat menghapus data supplier yang memiliki produk terkait',
                ], 422);
            }

            if (!$supplier->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data supplier',
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menghapus data supplier',
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => $user->role->name == 'Admin' ? 'Berhasil menonaktifkan data supplier' : 'Berhasil menghapus data supplier',
        ]);
    }

    public function deactivate($id)
    {
        $updateSupplier = Supplier::where('id', $id)->update([
            'is_active' => 0,
            'deactivated_at' => Carbon::now(),
        ]);

        if (!$updateSupplier) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menonaktifkan data supplier',
            ], 400);
        }
    }
}
