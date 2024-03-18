<?php

namespace App\Http\Controllers;

use App\Models\MarketList;
use App\Models\PurchaseReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CateringController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page;
        $per_page = $request->per_page ?? 10000;
        $search = $request->search;

        $report = PurchaseReport::when($search, function ($query, $search) {
            return $query->where('dates', 'LIKE', '%' . $search . '%');
        })
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data laporan penjualan',
            //'categories' => $purchase_order,
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
            'item_name' => 'required|string', // Menyesuaikan kunci untuk validasi
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $report = PurchaseReport::create([
            'item_name' => ucwords($request->item_name),
            'user_id' => auth()->user()->id,
        ]);

        if (!$report) { // Mengubah $createReport menjadi $report
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambah laporan penjualan',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil menambah laporan penjualan',
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

?>