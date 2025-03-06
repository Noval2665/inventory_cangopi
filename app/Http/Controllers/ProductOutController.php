<?php

namespace App\Http\Controllers;

use App\Models\ProductOut;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProductOutController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page;
        $per_page = $request->per_page ?? 10000;
        $search = $request->search;

        $productOuts = ProductOut::when($search, function ($query, $date) {
            return $query->where('date', '>=', );
        })
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data produk keluar',
            'productOuts' => $productOuts,
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
            'date' => 'required|date',
            'out_by' => 'required|string',
            'quantity' => 'required|numeric',
            'product_id' => 'required|numeric|exists:products,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
            ], 400);
        }

        $createProductOut = ProductOut::create([
            'date' => $request->date ? date('Y-m-d', strtotime($request->date)) : null,
            'out_by' => $request->out_by,
            'quantity' => $request->quantity,
            'product_id' => $request->product_id,
            'user_id' => auth()->user()->id,
        ]);

        if (!$createProductOut) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat data produk keluar',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil membuat data produk keluar',
        ], 201);   
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
    public function update(Request $request, ProductOut $productOut)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'out_by' => 'required|string',
            'quantity' => 'required|numeric',
            'product_id' => 'required|numeric|exists:products,id',
            'user_id' => auth()->user()->id,
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
            ], 400);
        }

        $updateProductOut = $productOut->update([
            'date' => $request->date ? date('Y-m-d', strtotime($request->date)) : null,
            'out_by' => $request->out_by,
            'quantity' => $request->quantity,
            'product_id' => $request->product_id,
            'user_id' => auth()->user()->id,
        ]);

        if (!$updateProductOut) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah data produk keluar',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengubah data produk keluar',
        ], 200);        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductOut $productOut)
    {
        $user = auth()->user();

        if ($user->role->name != 'Admin') {
            $this->deactivate($productOut->id);
        } else {
            if (!$productOut->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data produk keluar',
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menghapus data produk keluar',
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'message' => $user->role->name != 'Admin' ? 'Berhasil menonaktifkan produk keluar' : 'Berhasil menghapus data produk keluar',
        ], 200);
    }
    
    public function deactivate($id)
    {
        $updateProductOut = ProductOut::where('id', $id)->update([
            'is_active' => 0,
            'deactivated_at' => Carbon::now(),
        ]);

        if (!$updateProductOut) {
            return response()->json([

                'status' => 'error',
                'message' => 'Gagal menonaktifkan produk keluar',
            ], 400);
        }
    }
}
