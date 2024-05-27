<?php

namespace App\Http\Controllers;

use App\Models\Catering;
use App\Models\OrderListDetail;
use App\Models\Product;
use App\Models\ProductHistory;
use App\Models\ProductInfo;
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
        $per_page = $request->per_page;
        $search = $request->search;

        if ($search) {
            $page = 1;
        }

        $status = $request->status;

        $caterings = Catering::with([
            'orderList',
            'orderList.inventory',
            'orderList.details',
            'orderList.details.product',
            'orderList.details.product.subCategory.category',
            'orderList.details.product.unit',
            'orderList.details.product.metric',
            'orderList.details.description',
        ])
            ->when($search, function ($query, $search) {
                return $query->where('market_list_number', 'LIKE', '%' . $search . '%')
                    ->orWhereHas('orderList', function ($query) use ($search) {
                        $query->where('order_list_number', 'LIKE', '%' . $search . '%');
                    });
            })
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data market list',
            'caterings' => $caterings
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
            'catering_name' => 'required|string', // Menyesuaikan kunci untuk validasi
            'catering_code' => 'required|string',
            'status' => 'required|string|in:Pending, Approve, Cancel, Waiting',
            'date' => 'required|date',
            'order_list_id' => 'required|integer|exists:order_lists,id',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $createCatering = Catering::create([
            'catering_name' => $request->catering_name,
            'catering_code' => $request->catering_code,
            'status' => $request->status,
            'date' => $request->date ? date('Y-m-d', strtotime($request->date)) : null,
            'user_id' => auth()->user()->id,
            'order_list_id' => $request->order_list_id,
        ]);

        if (!$createCatering) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat data laporan penjualan',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil membuat data laporan penjualan',
            'catering' => $createCatering,
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
    public function update(Request $request, Catering $catering)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:Waiting,Processed,Rejected,Done,Cancel',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $updateCatering = $catering->update([
                'status' => $request->status,
                'user_id' => auth()->user()->id,
            ]);

            if (!$updateCatering) {
                throw new HttpException(400, 'Gagal mengubah status catering');
            }

            if ($request->status == 'Done') {
                $orderListDetails = OrderListDetail::where('order_list_id', $catering->order_list_id)->where('description_id', 6)->get();

                foreach ($orderListDetails as $item) {
                    $product = Product::find($item->product_id);
                    $productInfo = ProductInfo::where('product_id', $item->product_id)->where('inventory_id', $catering->orderList->inventory_id)->first();

                    if ($productInfo->total_stock < $item->quantity) {
                        throw new HttpException(422, 'Stok ' . $product->product_name . ' tidak mencukupi');
                    }

                    $udpateProduct = $product->update([
                        'stock' => $product->stock - $item->quantity,
                    ]);

                    if (!$udpateProduct) {
                        throw new HttpException(400, 'Gagal mengurangi stok produk' . $product->product_name);
                    }

                    $updateProductInfo = $productInfo->update([
                        'total_stock' => $productInfo->total_stock - $item->quantity,
                        'total_stock_out' => $productInfo->total_stock_out + $item->quantity,
                    ]);

                    if (!$updateProductInfo) {
                        throw new HttpException(400, 'Gagal mengubah informasi produk' . $product->product_name);
                    }

                    $createProductHistory = ProductHistory::create([
                        'product_id' => $item->product_id,
                        'date' => $catering->date,
                        'quantity' => $item->quantity * -1,
                        'purchase_price' => 0,
                        'selling_price' => $item->price,
                        'total' => $item->total,
                        'discount_type' => $item->discount_type,
                        'discount_amount' => $item->discount_amount,
                        'discount_percentage' => $item->discount_percentage,
                        'grandtotal' => $item->grandtotal,
                        'remaining_stock' => 0,
                        'reference_number' => $catering->catering_number,
                        'category' => 'Catering',
                        'type' => 'OUT',
                        'inventory_id' => $catering->orderList->inventory_id,
                    ]);

                    if (!$createProductHistory) {
                        throw new HttpException(400, 'Gagal membuat riwayat produk' . $product->product_name);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil mengubah status catering',
            ], 200);
        } catch (HttpException $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Catering $catering)
    {
        $user = auth()->user();

        if ($user->role->name != 'Admin') {
            $this->deactivate($catering->id);
        } else {
            if ($catering->orderList()->exist()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data laporan penjualan, karena data masih terkait dengan data lain',
                ], 400);
            }

            if (!$catering->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data laporan penjualan',
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menghapus data laporan penjualan',
            ], 200);
        }
        return response()->json([
            'status' => 'success',
            'message' => $user->role->name != 'Admin' ? 'Berhasil menonaktifkan laporan penjualan' : 'Berhasil menghapus data laporan penjualan',
        ], 200);
    }

    public function deactivate($id)
    {
        $updateCatering = Catering::where('id', $id)->update([
            'is_active' => 0,
            'deactivated_at' => Carbon::now(),
        ]);

        if (!$updateCatering) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menonaktifkan laporan penjualan',
            ], 400);
        }
    }
}
