<?php

namespace App\Http\Controllers;

use App\Models\ProductHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProductHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page;
        $per_page = $request->per_page ?? 10000;
        $search = $request->search;

        $productHistories = ProductHistory::when($search, function ($query, $search) {
            return $query->where('type', 'LIKE', '%' . $search . '%') or
                $query->where('product_name', 'LIKE', '%' . $search . '%');
        })
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data product history',
            'productHistories' => $productHistories,
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
            'quantity' => 'required|integer',
            'purchase_price' => 'required|integer',
            'selling_price' => 'required|integer',
            'discount_type' => 'required|string|in:amount,percentage',
            'discount_amount' => 'required|integer',
            'discount_percentage' => 'required|integer',
            'total' => 'required|integer',
            'remaining_stock' => 'required|integer',
            'reference_number' => 'required|string',
            'category' => 'required|string',
            'type' => 'required|string|in:in,out',
            'product_history_reference' => 'required|string',
            'user_id' => 'required|integer|exists:users,id',
            'product_id' => 'required|integer|exists:products,id',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $createProductHistory = ProductHistory::create([
            'date' => $request->date,
            'quantity' => $request->quantity,
            'purchase_price' => $request->purchase_price,
            'selling_price' => $request->selling_price,
            'discount_type' => $request->discount_type,
            'discount_amount' => $request->discount_amount,
            'discount_percentage' => $request->discount_percentage,
            'total' => $request->total,
            'remaining_stock' => $request->remaining_stock,
            'reference_number' => $request->reference_number,
            'category' => $request->category,
            'type' => $request->type,
            'product_history_reference' => $request->product_history_reference,

            'user_id' => auth()->user()->id,
            'product_id' => $request->product_id,
            'inventory_id' => $request->inventory_id,
        ]);

        if (!$createProductHistory) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat data product history',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil membuat data product history',
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
    public function update(Request $request, ProductHistory $productHistory)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'quantity' => 'required|integer',
            'purchase_price' => 'required|integer',
            'selling_price' => 'required|integer',
            'discount_type' => 'required|string|in:amount,percentage',
            'discount_amount' => 'required|integer',
            'discount_percentage' => 'required|integer',
            'total' => 'required|integer',
            'remaining_stock' => 'required|integer',
            'reference_number' => 'required|string',
            'category' => 'required|string',
            'type' => 'required|string|in:in,out',
            'product_history_reference' => 'required|string',

            'user_id' => 'required|integer|exists:users,id',
            'product_id' => 'required|integer|exists:products,id',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $updateProductHistory = $productHistory->update([
            'date' => $request->date,
            'quantity' => $request->quantity,
            'purchase_price' => $request->purchase_price,
            'selling_price' => $request->selling_price,
            'discount_type' => $request->discount_type,
            'discount_amount' => $request->discount_amount,
            'discount_percentage' => $request->discount_percentage,
            'total' => $request->total,
            'remaining_stock' => $request->remaining_stock,
            'reference_number' => $request->reference_number,
            'category' => $request->category,
            'type' => $request->type,
            'product_history_reference' => $request->product_history_reference,

            'user_id' => auth()->user()->id,
            'product_id' => $request->product_id,
            'inventory_id' => $request->inventory_id,
        ]);

        if (!$updateProductHistory) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengupdate data product history',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengupdate data product history',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductHistory $productHistory)
    {
        $user = auth()->user();

        if ($user->role->name != 'Admin') {
            $this->deactivate($productHistory->id);
        } else {

            if (!$productHistory->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data product history',
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menghapus data product history',
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'message' => $user->role->name != 'Admin' ? 'Berhasil menonaktifkan product history' : 'Berhasil menghapus data product history',
        ], 200);
    }

    public function deactivate($id)
    {
        $updateProductHistory = ProductHistory::where('id', $id)->update([
            'is_active' => 0,
            'deactivated_at' => Carbon::now(),
        ]);

        if (!$updateProductHistory) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menonaktifkan product history',
            ], 400);
        }
    }
}
