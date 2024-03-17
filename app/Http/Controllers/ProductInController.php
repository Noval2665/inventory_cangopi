<?php

namespace App\Http\Controllers;

use App\Models\ProductIn;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProductInController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page;
        $per_page = $request->per_page ?? 10000;
        $search = $request->search;

        $productIns = ProductIn::when($search, function ($query, $search) {
            return $query->where('item_status', 'LIKE', '%' . $search . '%');
        })
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data produk masuk',
            'productIns' => $productIns,
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
            'order_code' => 'required:string|exists:order_lists,order_code',
            'receive_code' => 'required|string',
            'receive_by' => 'required|string',
            'item_status' => 'required|string',
            'notes' => 'required|string',
            'image' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $createProductIn = ProductIn::create([
            'order_code' => $request->order_code,
            'receive_date' => $request->receive_date,
            'receive_code' => $request->receive_code,
            'receive_by' => $request->receive_by,
            'item_status' => $request->item_status,
            'notes' => $request->notes,
            'image' => $request->image,
            'user_id' => auth()->user()->id,
        ]);

        if (!$createProductIn) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat data produk masuk',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil membuat data produk masuk',
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
    public function update(Request $request, ProductIn $productIn)
    {
        $validator = Validator::make($request->all(), [
            'order_code' => 'required|string|exists:order_lists,order_code',
            'receive_code' => 'required|string',
            'receive_by' => 'required|string',
            'item_status' => 'required|string',
            'notes' => 'required|string',
            'image' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $updateProductIn = $productIn->update([
            'order_code' => $request->order_code,
            'receive_date' => $request->receive_date,
            'receive_code' => $request->receive_code,
            'receive_by' => $request->receive_by,
            'item_status' => $request->item_status,
            'notes' => $request->notes,
            'image' => $request->image,
            'user_id' => auth()->user()->id,
        ]);

        if (!$updateProductIn) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah data produk masuk',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengubah data produk masuk',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductIn $productIn)
    {
        $user = auth()->user();

        if ($user->role == 'admin') {
            $this->deactivate($productIn->id);
        }

        if (!$productIn) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus data produk masuk',
            ], 400);
        }   

        if (!$productIn->orderList->delete()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus data produk masuk',
            ], 400);
        }

        if (!$productIn->delete()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus data produk masuk',
            ], 400);
        }
    }

    public function deactivate($id)
    {
        $updateProductIn = ProductIn::where('id', $id)->update([
            'is_active' => 0,
            'deactivated_at' => Carbon::now(),
        ]);

        if (!$updateProductIn) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menonaktifkan produk masuk',
            ], 400);
        }
    }
}
