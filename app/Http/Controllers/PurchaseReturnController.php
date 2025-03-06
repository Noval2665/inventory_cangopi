<?php

namespace App\Http\Controllers;

use App\Models\OrderListDetail;
use App\Models\Product;
use App\Models\ProductHistory;
use App\Models\ProductInfo;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnDetail;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PurchaseReturnController extends Controller
{
    public function __construct()
    {
        $this->middleware(['can:create,purchase-returns', 'can:edit,purchase-returns'])->except('index');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page;
        $per_page = $request->per_page ?? 10000;
        $search = $request->search;

        if ($search) {
            $page = 1;
        }

        $purchaseReturnNumber = $request->purchase_return_number;
        $startDate = $request->start_date ? date('Y-m-d', strtotime($request->start_date)) : null;
        $endDate = $request->end_date ? date('Y-m-d', strtotime($request->end_date)) : null;
        $type = $request->type;

        $purchaseReturns = PurchaseReturn::with([
            'orderList',
            'orderList.details',
            'orderList.productHistories' => function ($query) {
                $query->where('remaining_stock', '>', 0);
            },
            'orderList.inventory',
            'details',
            'details.product',
            'details.product.subCategory.category',
            'details.product.unit',
            'details.product.metric',
        ])
            ->when($startDate, function ($query, $startDate) {
                return $query->where('date', '>=', $startDate);
            })->when($endDate, function ($query, $endDate) {
                return $query->where('date', '<=', $endDate);
            })->when($purchaseReturnNumber, function ($query, $purchaseReturnNumber) {
                return $query->where('purchase_return_number', 'like', '%' . $purchaseReturnNumber . '%');
            })->when($type, function ($query, $type) {
                if ($type == 'not_returned') {
                    return $query->whereHas('purchase.details', function ($query) {
                        $query->where('return_status', true);
                    });
                }
            })
            ->orderBy('date', 'ASC')
            ->paginate($per_page, ['*'], 'page', $page);


        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data retur pembelian',
            'purchase_returns' => $purchaseReturns,
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
            'order_list_id' => 'required|numeric|exists:order_lists,id',
            'date' => 'required|date',
            'purchase_return_items' => 'required|array',
            'purchase_return_items.*.product_id' => 'required|numeric|exists:products,id',
            'purchase_return_items.*.quantity' => 'required|numeric|min:1',
            'purchase_return_items.*.reason' => 'nullable|string',
            'return_type' => 'required|in:refund,change_product',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            do {
                $year = date('Y', strtotime($request->date));
                $month = date('m', strtotime($request->date));
                $purchaseReturnNumber = PurchaseReturn::generatePurchaseReturnNumber($month, $year);
            } while (PurchaseReturn::where('purchase_return_number', $purchaseReturnNumber)->exists());

            $createPurchaseReturn = PurchaseReturn::create([
                'purchase_return_number' => $purchaseReturnNumber,
                'order_list_id' => $request->order_list_id,
                'date' => $request->date,
                'total' => 0,
                'return_type' => $request->return_type,
                'user_id' => auth()->user()->id,
            ]);

            if (!$createPurchaseReturn) {
                throw new Exception('Gagal membuat data retur pembelian');
            }

            if ($request->return_type == 'refund') {
                $createRefund = $this->refund($request->all(), $createPurchaseReturn->id, $purchaseReturnNumber, 'add');

                if ($createRefund['status'] == 'error') {
                    throw new Exception($createRefund['message']);
                }
            } else if ($request->return_type == 'change_product') {
                $createChangeProduct = $this->changeItem($request->all(), $createPurchaseReturn->id, $purchaseReturnNumber, 'add');

                if ($createChangeProduct['status'] == 'error') {
                    throw new Exception($createChangeProduct['message']);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil membuat data retur pembelian',
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
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
    public function update(Request $request, PurchaseReturn $purchaseReturn)
    {
        $validator = Validator::make($request->all(), [
            'purchase_return_id' => 'required|numeric|exists:purchase_returns,id',
            'order_list_id' => 'required|numeric|exists:order_lists,id',
            'date' => 'required|date',
            'purchase_return_items' => 'required|array',
            'purchase_return_items.*.product_id' => 'required|numeric|exists:products,id',
            'purchase_return_items.*.quantity' => 'required|numeric|min:1',
            'purchase_return_items.*.reason' => 'nullable|string',
            'return_type' => 'required|in:refund,change_product',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $updatePurchaseReturn = $purchaseReturn->update([
                'date' => $request->date,
                'total' => 0,
                'return_type' => $request->return_type,
                'user_id' => auth()->user()->id,
            ]);

            if (!$updatePurchaseReturn) {
                throw new Exception('Gagal mengubah data retur pembelian');
            }

            if (!$purchaseReturn->details()->forceDelete()) {
                throw new Exception('Gagal menghapus detail retur pemwbelian');
            }

            $deleteProductHistory = ProductHistory::where('reference_number', $purchaseReturn->purchase_return_number)->forceDelete();

            if (!$deleteProductHistory) {
                throw new Exception('Gagal menghapus histori produk');
            }

            if ($request->return_type == 'refund') {
                $createRefund = $this->refund($request->all(), $purchaseReturn->id, $purchaseReturn->purchase_return_number, 'update');

                if ($createRefund['status'] == 'error') {
                    throw new Exception($createRefund['message']);
                }
            } else if ($request->return_type == 'change_product') {
                $createChangeProduct = $this->changeItem($request->all(), $purchaseReturn->id, $purchaseReturn->purchase_return_number, 'update');

                if ($createChangeProduct['status'] == 'error') {
                    throw new Exception($createChangeProduct['message']);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil mengubah data retur pembelian',
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchaseReturn $purchaseReturn)
    {
        DB::beginTransaction();

        try {
            $purchaseReturnDetails = $purchaseReturn->details()->get();

            foreach ($purchaseReturnDetails as $item) {
                $orderListDetail = OrderListDetail::where('order_list_id', $purchaseReturn->order_list_id)->where('product_id', $item->product_id)->where('discount_amount', $item->discount_amount)->first();

                $updatePurchaseDetail = $orderListDetail->update([
                    'return_quantity' => $orderListDetail->return_quantity - $item->quantity,
                    'return_amount' => $orderListDetail->return_amount - $item->total,
                    'return_status' => $orderListDetail->return_quantity - $item->quantity != $orderListDetail->quantity ? true : false,
                ]);

                if (!$updatePurchaseDetail) {
                    throw new Exception('Gagal mengubah detail pembelian');
                }

                $product = Product::find($item->product_id);

                $updateProduct = $product->increment('stock', $item->quantity);

                if (!$updateProduct) {
                    throw new Exception('Gagal mengubah stok produk');
                }

                $productHistory = ProductHistory::where('product_id', $item->product_id)->where('reference_number', $purchaseReturn->purchase_return_number)->where('discount_amount', $item->discount_amount)->where('category', 'purchase-return')->first();

                $productHistoryReference = ProductHistory::where('id', $productHistory->product_history_reference)->first();

                $updateProductHistory = $productHistoryReference->update([
                    'remaining_stock' => $productHistoryReference->remaining_stock + $item->quantity,
                ]);

                if (!$updateProductHistory) {
                    throw new Exception('Gagal mengubah data histori produk');
                }

                $productInfo = ProductInfo::find($item->product_id);

                $updateProductInfo = $productInfo->update([
                    'total_stock' => $productInfo->total_stock + $item->quantity,
                ]);

                if (!$updateProductInfo) {
                    throw new Exception('Gagal mengubah data informasi produk');
                }

                $productHistory = ProductHistory::where('product_id', $item->product_id)->where('reference_number', $purchaseReturn->purchase_return_number)->where('discount_amount', $item->discount_amount)->first();

                if (!$productHistory->delete()) {
                    throw new Exception('Gagal menghapus data histori produk');
                }
            }

            // $deleteProductHistory = ProductHistory::where('reference_number', $purchaseReturn->purchase_return_number)->delete();

            // if (!$deleteProductHistory) {
            //     throw new Exception('Gagal menghapus histori produk');
            // }

            if (!$purchaseReturn->details()->delete()) {
                throw new Exception('Gagal menghapus detail retur pembelian');
            }

            if (!$purchaseReturn->delete()) {
                throw new Exception('Gagal menghapus retur pembelian');
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menghapus retur pembelian',
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function refund(array $data, int $purchaseReturnID, string $purchaseReturnNumber, string $action)
    {
        DB::beginTransaction();

        try {
            $total = 0;

            $tempOrderListDetails = OrderListDetail::where('order_list_id', $data['order_list_id'])->get();

            foreach ($data['purchase_return_items'] as $item) {
                $tempOrderListDetails = $tempOrderListDetails->where('id', $item['id'])->where('product_id', $item['product_id'])->first();

                // if ($action == 'update') {
                //     $checkProductHistory = ProductHistory::where('product_id', $item['product_id'])
                //         ->where('reference_number', $purchaseReturnNumber)
                //         ->where('category', 'purchase-return')
                //         ->first();

                //     if ($checkProductHistory) {
                //         $deleteProductHistory = $checkProductHistory->forceDelete();

                //         if (!$deleteProductHistory) {
                //             throw new Exception('Gagal menghapus histori produk ID:' . $checkProductHistory->id);
                //         }
                //     }
                // }

                $orderListDetail = OrderListDetail::where('id', $item['id'])->where('order_list_id', $data['order_list_id'])->where('product_id', $item['product_id'])->first();

                $createPurchaseReturnDetail = PurchaseReturnDetail::create([
                    'purchase_return_id' => $purchaseReturnID,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'purchase_price' => $orderListDetail->price,
                    'discount_type' => $orderListDetail->discount_type,
                    'discount_amount' => $orderListDetail->discount_amount,
                    'discount_percentage' => $orderListDetail->discount_percentage,
                    'total' => ($item['quantity'] * $orderListDetail->price) - ($item['quantity'] * $orderListDetail->discount_amount),
                    'reason' => $item['reason'],
                ]);

                $total += ($item['quantity'] * $orderListDetail->price) - ($item['quantity'] * $orderListDetail->discount_amount);

                if (!$createPurchaseReturnDetail) {
                    throw new Exception('Gagal membuat detail retur pembelian');
                }

                if ($action == 'add') {
                    $updatePurchaseDetail = $orderListDetail->update([
                        'return_quantity' => $orderListDetail->return_quantity + $item['quantity'],
                        'return_amount' => $orderListDetail->return_amount + (($item['quantity'] * $orderListDetail->price) - (($orderListDetail->return_quantity + $item['quantity']) * $orderListDetail->discount_amount)),
                        'return_status' => $orderListDetail->return_quantity + $item['quantity'] == $orderListDetail->quantity ? false : true,
                    ]);
                } else {
                    $updatePurchaseDetail = $orderListDetail->update([
                        'return_quantity' => $item['quantity'],
                        'return_amount' => ($item['quantity'] * $orderListDetail->price) - ($item['quantity'] * $orderListDetail->discount_amount),
                        'return_status' => $orderListDetail->return_quantity + $item['quantity'] == $orderListDetail->quantity ? false : true,
                    ]);
                }

                if (!$updatePurchaseDetail) {
                    throw new Exception('Gagal mengubah detail pembelian');
                }

                $product = Product::find($item['product_id']);

                if ($action == 'add') {
                    $updateProduct = $product->decrement('stock', $item['quantity']);
                } else {
                    $updateProduct = $product->update([
                        'stock' => $product->stock + $tempOrderListDetails->return_quantity - $item['quantity'],
                    ]);
                }

                if (!$updateProduct) {
                    throw new Exception('Gagal mengubah stok produk');
                }

                $productHistory = ProductHistory::where('product_id', $item['product_id'])->where('reference_number', $data['order_list_number'])->where('discount_amount', $item['discount_amount'])->first();

                if ($action == 'add') {
                    $updateProductHistory = $productHistory->decrement('remaining_stock', $item['quantity']);
                } else {
                    $updateProductHistory = $productHistory->update([
                        'remaining_stock' => $productHistory->remaining_stock + $tempOrderListDetails->return_quantity - $item['quantity'],
                    ]);
                }

                if (!$updateProductHistory) {
                    throw new Exception('Gagal mengubah data histori produk');
                }

                $productInfo = ProductInfo::find($item['product_id']);

                if ($action == 'add') {
                    $updateProductInfo = $productInfo->update([
                        'total_stock' => $productInfo->total_stock - $item['quantity'],
                    ]);
                } else {
                    $updateProductInfo = $productInfo->update([
                        'total_stock' => $productInfo->total_stock + $tempOrderListDetails->return_quantity - $item['quantity'],
                    ]);
                }

                if (!$updateProductInfo) {
                    throw new Exception('Gagal mengubah data informasi produk');
                }

                $createProductHistory = ProductHistory::create([
                    'product_id' => $item['product_id'],
                    'date' => $data['date'],
                    'quantity' => $item['quantity'] * -1,
                    'purchase_price' => $orderListDetail->price,
                    'selling_price' => 0,
                    'discount_type' => $orderListDetail->discount_type,
                    'discount_amount' => $orderListDetail->discount_amount,
                    'discount_percentage' => $orderListDetail->discount_percentage,
                    'total' => $item['quantity'] * $orderListDetail->price,
                    'remaining_stock' => 0,
                    'reference_number' => $purchaseReturnNumber,
                    'category' => 'purchase-return',
                    'type' => 'OUT',
                    'product_history_reference' => $productHistory->id,
                    'inventory_id' => $productHistory->inventory_id,
                ]);

                if (!$createProductHistory) {
                    throw new Exception('Gagal membuat data histori produk');
                }
            }

            $updatePurchaseReturn = PurchaseReturn::where('id', $purchaseReturnID)->update([
                'total' => $total,
            ]);

            if (!$updatePurchaseReturn) {
                throw new Exception('Gagal mengubah total retur pembelian');
            }

            DB::commit();

            return [
                'status' => 'success',
                'message' => 'Berhasil membuat data detail retur pembelian',
            ];
        } catch (Exception $e) {
            DB::rollBack();

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function changeItem(array $data, int $purchaseReturnID, string $purchaseReturnNumber, string $action)
    {
        DB::beginTransaction();

        try {
            $total = 0;

            $tempOrderListDetails = OrderListDetail::where('order_list_id', $data['order_list_id'])->get();

            foreach ($data['purchase_return_items'] as $item) {
                $tempOrderListDetails = $tempOrderListDetails->where('id', $item['id'])->where('product_id', $item['product_id'])->first();

                // if ($action == 'update') {
                //     $checkProductHistory = ProductHistory::where('product_id', $item['product_id'])
                //         ->where('reference_number', $purchaseReturnNumber)
                //         ->where('category', 'purchase-return')
                //         ->first();

                //     if ($checkProductHistory) {
                //         $deleteProductHistory = $checkProductHistory->forceDelete();

                //         if (!$deleteProductHistory) {
                //             throw new Exception('Gagal menghapus histori produk ID:' . $checkProductHistory->id);
                //         }
                //     }
                // }

                $orderListDetail = OrderListDetail::where('id', $item['id'])->where('order_list_id', $data['order_list_id'])->where('product_id', $item['product_id'])->first();

                $createPurchaseReturnDetail = PurchaseReturnDetail::create([
                    'purchase_return_id' => $purchaseReturnID,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $orderListDetail->price,
                    'discount_type' => $orderListDetail->discount_type,
                    'discount_amount' => $orderListDetail->discount_amount,
                    'discount_percentage' => $orderListDetail->discount_percentage,
                    'total' => ($item['quantity'] * $orderListDetail->price) - ($item['quantity'] * $orderListDetail->discount_amount),
                    // 'condition' => $item['condition'],
                    'reason' => $item['reason'],
                ]);

                $total += ($item['quantity'] * $orderListDetail->price) - ($item['quantity'] * $orderListDetail->discount_amount);

                if (!$createPurchaseReturnDetail) {
                    throw new Exception('Gagal membuat detail retur pembelian');
                }

                if ($action == 'add') {
                    $updatePurchaseDetail = $orderListDetail->update([
                        'return_quantity' => $orderListDetail->return_quantity + $item['quantity'],
                        'return_amount' => $orderListDetail->return_amount + (($item['quantity'] * $orderListDetail->price) - (($orderListDetail->return_quantity + $item['quantity']) * $orderListDetail->discount_amount)),
                        'return_status' => $orderListDetail->return_quantity + $item['quantity'] == $orderListDetail->quantity ? false : true,
                    ]);
                } else {
                    $updatePurchaseDetail = $orderListDetail->update([
                        'return_quantity' => $item['quantity'],
                        'return_amount' => ($item['quantity'] * $orderListDetail->price) - ($item['quantity'] * $orderListDetail->discount_amount),
                        'return_status' => $orderListDetail->return_quantity + $item['quantity'] == $orderListDetail->quantity ? false : true,
                    ]);
                }

                if (!$updatePurchaseDetail) {
                    throw new Exception('Gagal mengubah detail pembelian');
                }

                $product = Product::find($item['product_id']);

                if ($action == 'add') {
                    $updateProduct = $product->decrement('stock', $item['quantity']);
                } else {
                    $updateProduct = $product->update([
                        'stock' => $product->stock + $tempOrderListDetails->return_quantity - $item['quantity'],
                    ]);
                }

                if (!$updateProduct) {
                    throw new Exception('Gagal mengubah stok produk');
                }

                $productHistory = ProductHistory::where('product_id', $item['product_id'])->where('reference_number', $data['order_list_number'])->where('discount_amount', $item['discount_amount'])->first();

                if ($action == 'add') {
                    $updateProductHistory = $productHistory->decrement('remaining_stock', $item['quantity']);
                } else {
                    $updateProductHistory = $productHistory->update([
                        'remaining_stock' => $productHistory->remaining_stock + $tempOrderListDetails->return_quantity - $item['quantity'],
                    ]);
                }

                if (!$updateProductHistory) {
                    throw new Exception('Gagal mengubah data histori produk');
                }

                $productInfo = ProductInfo::find($item['product_id']);

                if ($action == 'add') {
                    $updateProductInfo = $productInfo->update([
                        'total_stock' => $productInfo->total_stock - $item['quantity'],
                    ]);
                } else {
                    $updateProductInfo = $productInfo->update([
                        'total_stock' => $productInfo->total_stock + $tempOrderListDetails->return_quantity - $item['quantity'],
                    ]);
                }

                if (!$updateProductInfo) {
                    throw new Exception('Gagal mengubah data informasi produk');
                }

                $createProductHistory = ProductHistory::create([
                    'product_id' => $item['product_id'],
                    'date' => $data['date'],
                    'quantity' => $data['quantity'],
                    'purchase_price' => $data['purchase_price'],
                    'selling_price' => $data['selling_price'],
                    'discount_type' => $data['discount_type'],
                    'discount_amount' => $data['discount_amount'],
                    'discount_percentage' => $data['discount_percentage'],
                    'total' => $data['total'],
                    'remaining_stock' => $data['remaining_stock'],
                    'reference_number' => $data['reference_number'],
                    'category' => $data['category'],
                    'type' => $data['type'],
                    'product_history_reference' => $data['product_history_reference'],
                    'inventory_id' => $data['inventory_id'],
                ]);

                if (!$createProductHistory) {
                    throw new Exception('Gagal membuat data histori produk');
                }
            }

            $updatePurchaseReturn = PurchaseReturn::where('id', $purchaseReturnID)->update([
                'total' => $total,
            ]);

            if (!$updatePurchaseReturn) {
                throw new Exception('Gagal mengubah total retur pembelian');
            }

            DB::commit();

            return [
                'status' => 'success',
                'message' => 'Berhasil membuat data detail retur pembelian',
            ];
        } catch (Exception $e) {
            DB::rollBack();

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
}
