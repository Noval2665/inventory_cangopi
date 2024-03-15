<?php

namespace App\Http\Controllers;

use App\Models\Sales;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SalesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page;
        $per_page = $request->per_page ?? 10000;
        $search = $request->search;
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
            'finished_product_id' => 'required|numeric|exists:finished_products,id',
            'quantity_sold' => 'required|numeric',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);
        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $createSales = Sales::create([
            'finished_product_id' => $request->finished_product_id,
            'quantity_sold' => $request->quantity_sold,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'user_id' => auth()->user()->id,
        ]);

        if (!$createSales) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat data penjualan',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil membuat data penjualan',
        ], 200);
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
    public function update(Request $request, Sales $sales)
    {
        $validator = Validator::make($request->all(), [
            'finished_product_id' => 'required|numeric|exists:finished_products,id',
            'quantity_sold' => 'required|numeric',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);
        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $updateSales = $sales->update([
            'finished_product_id' => $request->finished_product_id,
            'quantity_sold' => $request->quantity_sold,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'user_id' => auth()->user()->id,
        ]);

        if (!$updateSales) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah data penjualan',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengubah data penjualan',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sales $sales)
    {
        $user = auth()->user();

        if ($user->role->name != 'Admin') {
            $this->deactivate($sales->id);
        } else {
            if ($sales->finishedProduct()->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak dapat menghapus data penjualan yang memiliki produk terkait'
                ], 422);
            }

            if (!$sales->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data penjualan',
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menghapus data penjualan',
            ], 200);

        }

        return response()->json([
            'status' => 'success',
            'message' => $user->role->name != 'Admin' ? 'Berhasil menonaktifkan penjualan' : 'Berhasil menghapus data penjualan',
        ], 200);
    }

    public function deactivate($id)
    {
        $updateSales = Sales::where('id', $id)->update([
            'is_active' => 0,
            'deactivated_at' => Carbon::now(),
        ]);

        if (!$updateSales) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menonaktifkan penjualan',
            ], 400);
        }
    }
}
