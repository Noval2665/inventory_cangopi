<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Carbon\Carbon;
use App\Models\ParStockProduct;
use Symfony\Component\Console\Descriptor\Descriptor;

class ParStockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page;
        $per_page = $request->per_page ?? 10000;
        $search = $request->search;

        $parStocks = ParStockProduct::when($search, function ($query, $search) {
            return $query->where('storage_type', 'LIKE', '%' . $search . '%');
        })
            ->paginate($per_page, ['*'], 'page', $page);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data Par Stock',
            'parStocks' => $parStocks,
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
            'par_stock_code' => 'required|string',
            'par_stock_name' => ucwords('required|string'),
            'minimum_stock' => 'required|numeric',
            'is_active' => 'required|boolean',
            'user_id' => 'required|integer',
            'unit_id' => 'required|integer',
            'storage_id' => 'required|integer',
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ]);
        }

        $createParStockProduct = ParStockProduct::create([
            'par_stock_code' => $request->par_stock_code,
            'par_stock_name' => $request->par_stock_name,
            'minimum_stock' => $request->minimum_stock,
            'is_active' => $request->is_active,
            'user_id' => auth()->user()->id,
            'unit_id' => $request->unit_id,
            'storage_id' => $request->storage_id,
        ]);

        if(!$createParStockProduct){
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan data produk Par Stock',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil menambahkan data produk Par Stock',
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
    public function update(Request $request, ParStockProduct $parStockProduct)
    {
        $validator = Validator::make($request->all(), [
            'par_stock_code' => 'required|string',
            'par_stock_name' => ucwords('required|string'),
            'minimum_stock' => 'required|numeric',
            'is_active' => 'required|boolean',
            'user_id' => 'required|integer',
            'unit_id' => 'required|integer',
            'storage_id' => 'required|integer',
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ]);
        }

        $updateParStockProduct = $parStockProduct->update([
            'par_stock_code' => $request->par_stock_code,
            'par_stock_name' => $request->par_stock_name,
            'minimum_stock' => $request->minimum_stock,
            'is_active' => $request->is_active,
            'user_id' => auth()->user()->id,
            'unit_id' => $request->unit_id,
            'storage_id' => $request->storage_id,
        ]);

        if(!$updateParStockProduct){
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui data produk Par Stock',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil memperbarui data produk Par Stock',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ParStockProduct $parStockProduct)
    {
        $user = auth()->user();

        if ($user->role->name != 'Admin') {
            $this->deactivate($parStockProduct->id);
        } 
        else {
            if (!$parStockProduct->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data produk Par Stock',
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menghapus data produk Par Stock',
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'message' => $user->role->name != 'Admin' ? 'Berhasil menontaktifkan data produk Par Stock' : 'Berhasil menghapus data produk Par Stock',
        ], 200);
    }

    public function deactivate($id){
        $updateParStock = ParStockProduct::where('id', $id)->update([
            'is_active' => 0,
            'deactivated_at' => Carbon::now(),
        ]);

        if(!$updateParStock){
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menonaktifkan produk Par Stock',
            ], 400);
        }
    }
}
