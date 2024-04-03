<?php

namespace App\Http\Controllers;

use App\Models\OrderList;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OrderListController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page;
        $per_page = $request->per_page ?? 10000;
        $search = $request->search;

        $orderLists = OrderList::when($search, function ($query, $search, $date) {
            return $query->where('product_name', 'LIKE', '%' . $search . '%') 
            or $query->where('order_date', '==', $date);
        })
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data produk',
            'orderLists' => $orderLists,
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
            'product_id' => 'required|string|exists:products,id',
            'order_code_id' => 'required|numeric|exists:order_codes,id',
            'quantity' => 'required|numeric',
            'description' => 'required|string',
            'order_date' =>'required|date', // Menambahkan validasi 'order_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
            ], 400);
        }

        $createOrderList = OrderList::create([
            'order_date' => $request->order_date ? date('Y-m-d', strtotime($request->order_date)) : null, // Menambahkan 'order_date' dengan nilai 'Carbon::now()
            'quantity' => $request->quantity,
            'description' => $request->description,
            'user_id' => auth()->user()->id,
            'product_id' => $request->product_id,
            'order_code_id' => $request->order_code_id,
        ]);

        if (!$createOrderList) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan produk',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil menambahkan produk',
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
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
    public function update(Request $request, OrderList $orderList)
    {
        $validator = Validator::make($request->all(), [
            'order_date' => 'required|date', // Menambahkan validasi 'order_date'
            'quantity' => 'required|numeric',
            'description' => 'required|string',
            'user_id' => auth()->user()->id,
            'product_id' => 'required|string',
            'order_code_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors(),
            ], 400);
        }

        $updateOrderList = $orderList->update([
            'order_date' => $request->order_date ? date('Y-m-d', strtotime($request->order_date)) : null, // Menambahkan 'order_date' dengan nilai 'Carbon::now()
            'quantity' => $request->quantity,
            'description' => $request->description,
            'product_id' => $request->product_id,
            'user_id' => auth()->user()->id,
            'order_code_id' => $request->order_code_id,
        ]);

        if (!$updateOrderList) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah data produk',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengubah data produk',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrderList $orderList)
    {
        $user = auth()->user();

        if ($user->role->name != 'Admin') {
            $this->deactivate($orderList->id);
        } else {
            if ($orderList->orderLists()->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak dapat menghapus data produk yang memiliki order terkait'
                ], 422);
            }

            if (!$orderList->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data produk',
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menghapus data produk',
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => $user->role->name == 'Admin' ? 'Berhasil menonaktifkan data produk' : 'Berhasil menghapus data produk',
        ], 200);
    }

    public function deactivate($id)
    {
        $updateOrderList = OrderList::where('id', $id)->update([
            'is_active' => 0,
            'deactivated_at' => Carbon::now(),
        ]);

        if (!$updateOrderList) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menonaktifkan produk',
            ], 400);
        }
    }
}
