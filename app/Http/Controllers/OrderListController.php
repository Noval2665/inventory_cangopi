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
            'date' => ['required', 'date'],
            'inventory_id' => ['required', 'numeric', 'exists:inventories,id'],
            'order_list_items' => ['required', 'array'],
            'order_list_items.*.product_id' => ['required', 'numeric', 'exists:products,id'],
            'order_list_items.*.quantity' => ['required', 'numeric'],
            'order_list_items.*.discount_type' => ['required', 'string'],
            'order_list_items.*.discount_amount' => ['nullable', 'numeric'],
            'order_list_items.*.discount_percentage' => ['nullable'],
            'order_list_items.*.description' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 400);
        }

        DB::beginTransaction();

        try {
            do {
                $year = date('Y', strtotime($request->date));
                $orderListNumber = OrderList::generateOrderListNumber($year);
            } while (OrderList::where('order_list_number', $orderListNumber)->exists());

            $createOrderList = OrderList::create([
                'order_list_number' => $orderListNumber,
                'date' => $request->date,
                'total' => $request->total,
                'discount_type' => $request->discount_type ?? 'amount',
                'discount_amount' => $request->discount_amount ?? 0,
                'discount_percentage' => $request->discount_percentage ?? 0,
                'grandtotal' => $request->grandtotal,
                'description_id' => $request->description_id ?? null,
                'description' => $request->description,
                'user_id' => auth()->user()->id,
            ]);

            if (!$createOrderList) {
                throw new HttpException(400, 'Gagal menambahkan data order list');
            }

            foreach ($request->order_list_items as $orderListItem) {
                $createOrderListItem = $createOrderList->details()->create([
                    'product_id' => $orderListItem['product_id'],
                    'quantity' => $orderListItem['quantity'],
                    'purchase_price' => $orderListItem['purchase_price'],
                    'total' => $orderListItem['total'],
                    'discount_type' => $orderListItem['discount_type'] ?? 'amount',
                    'discount_amount' => $orderListItem['discount_amount'] ?? 0,
                    'discount_percentage' => $orderListItem['discount_percentage'] ?? 0,
                    'grandtotal' => $orderListItem['grandtotal'],
                    'inventory_id' => $request->inventory_id,
                ]);

                if (!$createOrderListItem) {
                    throw new HttpException(400, 'Gagal menambahkan data detail order list');
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menambahkan data order list',
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
