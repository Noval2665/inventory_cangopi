<?php

namespace App\Http\Controllers;

use App\Models\ClosingBook;
use App\Models\OrderList;
use App\Models\Product;
use App\Models\ProductHistory;
use App\Models\ProductInfo;
use App\Models\Purchase;
use DB;
use Exception;
use Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PurchaseReceptionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['can:create,purchase-receptions', 'can:edit,purchase-receptions']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
            'order_list_id' => ['required', 'numeric', 'exists:order_lists,id'],
            'date' => ['required', 'date'],
            'order_list_items' => ['required', 'array'],
            'order_list_items.*.order_list_detail_id' => ['required', 'numeric', 'exists:order_list_details,id'],
            'order_list_items.*.product_id' => ['required', 'numeric', 'exists:products,id'],
            'order_list_items.*.quantity' => ['required', 'numeric'],
            'order_list_items.*.received_quantity' => ['nullable', 'numeric'],
            'order_list_items.*.total' => ['required', 'numeric'],
            'order_list_items.*.note' => ['nullable', 'string'],
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $orderList = OrderList::where('id', $request->order_list_id)->first();

            $total = array_reduce($request->order_list_items, function ($carry, $item) {
                return $carry + $item['total'];
            });

            $newDiscountAmount = 0;
            $newDiscountPercentage = 0;
            $newPPNAmount = 0;
            $newGrandtotal = 0;

            if ($orderList->discount_type == 'amount') {
                $newDiscountPercentage = $orderList->discount_amount / $total * 100;

                $newDiscountPercentage = is_int($newDiscountPercentage)
                    ? intval($newDiscountPercentage)
                    : round($newDiscountPercentage, 2);

                $newGrandtotal = $total - $orderList->discount_amount + $newPPNAmount;
            } else {
                $newDiscountAmount = $total * $orderList->discount_percentage / 100;

                $newGrandtotal = $total - $newDiscountAmount + $newPPNAmount;
            }

            $updateOrderList = $orderList->update([
                'total' => $total,
                'discount_amount' => $newDiscountAmount == 0 ? $orderList->discount_amount : $newDiscountAmount,
                'discount_percentage' => $newDiscountPercentage == 0 ? $orderList->discount_percentage : $newDiscountPercentage,
                'grandtotal' => $newGrandtotal,
            ]);

            if (!$updateOrderList) {
                throw new HttpException(400, 'Gagal mengubah data order list');
            }

            foreach ($request->order_list_items as $item) {
                if ($item['purchase_reception_status'] != 'complete') {
                    $orderListDetail = $orderList->details()->where('id', $item['order_list_detail_id'])->where('product_id', $item['product_id'])->first();

                    $updateOrderListDetail = $orderListDetail->update([
                        'received_quantity' => $orderListDetail->received_quantity + $item['received_quantity'],
                        'total' => $item['total'],
                        'note' => $item['note'],
                        'purchase_reception_status' => ($orderListDetail->received_quantity + $item['received_quantity']) == $item['quantity'] ? 'complete' : 'incomplete',
                    ]);

                    if (!$updateOrderListDetail) {
                        throw new HttpException(400, 'Gagal mengubah data detail order list');
                    }

                    if ($item['received_quantity'] > 0) {
                        $product = Product::where('id', $item['product_id'])->first();

                        $updateProduct = $product->update([
                            'stock' => $product->stock + $item['received_quantity'],
                        ]);

                        if (!$updateProduct) {
                            throw new HttpException(400, 'Gagal mengubah data stok produk' . $product->name);
                        }

                        $productInfo = ProductInfo::where('product_id', $item['product_id'])->where('inventory_id', $orderList->inventory_id)->first();

                        if (!$productInfo) {
                            $productInfo = ProductInfo::create([
                                'product_id' => $item['product_id'],
                                'inventory_id' => $request->inventory_id,
                                'total_stock' => $item['quantity'],
                                'total_stock_out' => 0,
                            ]);

                            if (!$productInfo) {
                                throw new HttpException(400, 'Gagal membuat data informasi produk' . $product->name);
                            }
                        } else {
                            $updateProductInfo = $productInfo->update([
                                'total_stock' => $productInfo->total_stock + $item['received_quantity'],
                            ]);

                            if (!$updateProductInfo) {
                                throw new HttpException(400, 'Gagal mengubah data informasi produk' . $product->name);
                            }
                        }

                        $productHistory = ProductHistory::create([
                            'product_id' => $item['product_id'],
                            'date' => $request->date,
                            'quantity' => $item['received_quantity'],
                            'purchase_price' => $orderListDetail->price,
                            'selling_price' => 0,
                            'discount_type' => $orderListDetail->discount_type,
                            'discount_amount' => $orderListDetail->discount_amount,
                            'discount_percentage' => $orderListDetail->discount_percentage,
                            'total' => $item['total'],
                            'remaining_stock' => $item['received_quantity'],
                            'reference_number' => $orderList->order_list_number,
                            'category' => $orderListDetail->description->description_name,
                            'type' => 'IN',
                            'inventory_id' => $orderList->inventory_id,
                        ]);

                        if (!$productHistory) {
                            throw new HttpException(400, 'Gagal membuat data histori produk' . $product->name);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil membuat data penerimaan order list',
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
        $validator = Validator::make($request->all(), [
            'order_list_id' => ['required', 'numeric', 'exists:order_lists,id'],
            'date' => ['required', 'date'],
            'order_list_items' => ['required', 'array'],
            'order_list_items.*.order_list_detail_id' => ['required', 'numeric', 'exists:order_list_details,id'],
            'order_list_items.*.product_id' => ['required', 'numeric', 'exists:products,id'],
            'order_list_items.*.quantity' => ['required', 'numeric'],
            'order_list_items.*.received_quantity' => ['nullable', 'numeric'],
            'order_list_items.*.total' => ['required', 'numeric'],
            'order_list_items.*.note' => ['nullable', 'string'],
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $orderList = OrderList::where('id', $request->order_list_id)->first();

            $total = array_reduce($request->order_list_items, function ($carry, $item) {
                return $carry + $item['total'];
            });

            $newDiscountAmount = 0;
            $newDiscountPercentage = 0;
            $newPPNAmount = 0;
            $newGrandtotal = 0;

            if ($orderList->discount_type == 'amount') {
                $newDiscountPercentage = $orderList->discount_amount / $total * 100;

                $newDiscountPercentage = is_int($newDiscountPercentage)
                    ? intval($newDiscountPercentage)
                    : round($newDiscountPercentage, 2);

                $newGrandtotal = $total - $orderList->discount_amount + $newPPNAmount;
            } else {
                $newDiscountAmount = $total * $orderList->discount_percentage / 100;

                $newGrandtotal = $total - $newDiscountAmount + $newPPNAmount;
            }

            $updateOrderList = $orderList->update([
                'total' => $total,
                'discount_amount' => $newDiscountAmount == 0 ? $orderList->discount_amount : $newDiscountAmount,
                'discount_percentage' => $newDiscountPercentage == 0 ? $orderList->discount_percentage : $newDiscountPercentage,
                'grandtotal' => $newGrandtotal,
            ]);

            if (!$updateOrderList) {
                throw new HttpException(400, 'Gagal mengubah data order list');
            }

            foreach ($request->order_list_items as $item) {
                if ($item['received_quantity'] != $item['last_received_quantity']) {
                    $orderListDetail = $orderList->details()->where('id', $item['order_list_detail_id'])->where('product_id', $item['product_id'])->first();

                    $checkProductHistory = ProductHistory::where('product_id', $item['product_id'])->where('discount_amount', $item['discount_amount'])->where('reference_number', $orderList->order_list_number)->first();

                    if ($checkProductHistory) {
                        $deleteProductHistory = $checkProductHistory->forceDelete();

                        if (!$deleteProductHistory) {
                            throw new HttpException(400, 'Gagal menghapus histori produk');
                        }
                    }

                    if ($item['received_quantity'] > 0) {
                        $product = Product::where('id', $item['product_id'])->first();

                        $updateProduct = $product->update([
                            'stock' => $product->stock - $orderListDetail->received_quantity + $item['received_quantity'],
                        ]);

                        if (!$updateProduct) {
                            throw new HttpException(400, 'Gagal mengubah data stok produk' . $product->name);
                        }

                        $productInfo = ProductInfo::where('product_id', $item['product_id'])->where('inventory_id', $orderListDetail->inventory_id)->first();

                        if (!$productInfo) {
                            $productInfo = ProductInfo::create([
                                'product_id' => $item['product_id'],
                                'inventory_id' => $request->inventory_id,
                                'total_stock' => $item['quantity'],
                                'total_stock_out' => 0,
                            ]);

                            if (!$productInfo) {
                                throw new HttpException(400, 'Gagal membuat data informasi produk' . $product->name);
                            }
                        } else {
                            $updateProductInfo = $productInfo->update([
                                'total_stock' => $productInfo->total_stock - $orderListDetail->received_quantity + $item['received_quantity'],
                            ]);

                            if (!$updateProductInfo) {
                                throw new HttpException(400, 'Gagal mengubah data informasi produk' . $product->name);
                            }
                        }

                        $productHistory = ProductHistory::create([
                            'product_id' => $item['product_id'],
                            'date' => $request->date,
                            'quantity' => $item['received_quantity'],
                            'purchase_price' => $orderListDetail->price,
                            'selling_price' => 0,
                            'discount_type' => $orderListDetail->discount_type,
                            'discount_amount' => $orderListDetail->discount_amount,
                            'discount_percentage' => $orderListDetail->discount_percentage,
                            'total' => $item['total'],
                            'remaining_stock' => $item['received_quantity'],
                            'reference_number' => $orderList->order_list_number,
                            'category' => $orderListDetail->description->description_name,
                            'type' => 'IN',
                            'inventory_id' => $orderList->inventory_id,
                        ]);

                        if (!$productHistory) {
                            throw new HttpException(400, 'Gagal membuat data histori produk' . $product->name);
                        }
                    } else {
                        $product = Product::where('id', $item['product_id'])->first();

                        $updateProduct = $product->update([
                            'stock' => $product->stock - $orderListDetail->received_quantity + $item['received_quantity'],
                        ]);

                        if (!$updateProduct) {
                            throw new Exception('Gagal mengubah stok produk');
                        }

                        $productInfo = ProductInfo::where('product_id', $item['product_id'])->where('inventory_id', $orderList->inventory_id)->first();

                        if (!$productInfo) {
                            $productInfo = ProductInfo::create([
                                'product_id' => $item['product_id'],
                                'inventory_id' => $request->inventory_id,
                                'total_stock' => $item['quantity'],
                                'total_stock_out' => 0,
                            ]);

                            if (!$productInfo) {
                                throw new HttpException(400, 'Gagal membuat data informasi produk');
                            }
                        } else {
                            $updateProductInfo = $productInfo->update([
                                'total_stock' => $productInfo->total_stock - $orderListDetail->received_quantity,
                            ]);

                            if (!$updateProductInfo) {
                                throw new HttpException(400, 'Gagal mengubah data informasi produk');
                            }
                        }
                    }

                    $updateOrderListDetail = $orderListDetail->update([
                        'received_quantity' => $item['received_quantity'],
                        'discount_amount' => $item['discount_amount'],
                        'discount_percentage' => $item['discount_percentage'],
                        'total' => $item['total'],
                        'note' => $item['note'],
                        'purchase_reception_status' => ($orderListDetail->quantity == $item['received_quantity'] ? 'complete' : 'incomplete')
                    ]);

                    if (!$updateOrderListDetail) {
                        throw new HttpException(400, 'Gagal mengubah data detail order list');
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil mengubah data penerimaan order list',
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
    public function destroy(string $id)
    {
        //
    }
}
