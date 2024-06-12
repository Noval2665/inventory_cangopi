<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Models\Opname;
use App\Models\OpnameDetail;
use App\Models\ProductHistory;
use App\Models\Product;
use App\Models\ProductInfo;
use Exception;

class OpnameController extends Controller
{
    public function __construct()
    {
        $this->middleware(['can:create,stock-opnames', 'can:edit,stock-opnames'])->except('index');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $startDate = date('Y-m-d', strtotime($request->start_date)) . ' 00:00:00';
        $endDate = date('Y-m-d', strtotime($request->end_date)) . ' 23:59:59';

        $data = Opname::with(['details.product.inventoryProductInfo', 'details.product.unit', 'inventory', 'user'])->whereBetween('date', [$startDate, $endDate])->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil menampilkan data Stock Opname',
            'data' => $data,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'inventory_id' => 'required|exists:inventories,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.system_quantity' => 'required|numeric|min:0',
            'items.*.physical_quantity' => 'required|numeric|min:0',
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
                $opnameNumber = Opname::generateOpnameNumber($month, $year);
            } while (Opname::where('opname_number', $opnameNumber)->exists());

            $date = date('Y-m-d', strtotime($request->date));

            $createStockOpname = Opname::create([
                'opname_number' => $opnameNumber,
                'date' => $date,
                'inventory_id' => $request->inventory_id,
                'description' => $request->description,
                'user_id' => auth()->user()->id,
            ]);

            if (!$createStockOpname) {
                throw new Exception('Gagal membuat Stock Opname');
            }

            $difference = 0;
            $differenceTotalPrice = 0;

            foreach ($request->items as $item) {
                $difference = $item['physical_quantity'] - $item['system_quantity'];

                $stockOpnameDetail = OpnameDetail::create([
                    'opname_id' => $createStockOpname->id,
                    'product_id' => $item['product_id'],
                    'system_quantity' => $item['system_quantity'],
                    'physical_quantity' => $item['physical_quantity'],
                    'difference' => $difference,
                    'description' => $item['description'],
                ]);

                if (!$stockOpnameDetail) {
                    throw new Exception('Gagal membuat detail opname');
                }

                $product = Product::find($item['product_id']);
                $productInfo = ProductInfo::where('product_id', $item['product_id'])->where('inventory_id', $request->inventory_id)->first();

                if (!$productInfo) {
                    $productInfo = ProductInfo::create([
                        'product_id' => $item['product_id'],
                        'inventory_id' => $request->inventory_id,
                        'total_stock' => $item['physical_quantity'],
                        'total_stock_out' => 0,
                        'user_id' => auth()->user()->id,
                    ]);

                    if (!$productInfo) {
                        throw new Exception('Gagal membuat info produk');
                    }
                } else {
                    $updateProductInfo = $productInfo->update([
                        'total_stock' => $item['physical_quantity'],
                        'total_stock_out' => ($difference < 0 ? $productInfo->total_stock_out + ($difference * -1) : $productInfo->total_stock_out)
                    ]);

                    if (!$updateProductInfo) {
                        throw new Exception('Gagal mengupdate info produk');
                    }
                }

                $updateProduct = $product->update([
                    'stock' => $product->stock - $productInfo->total_stock + $item['physical_quantity'],
                ]);

                if (!$updateProduct) {
                    throw new Exception('Gagal mengupdate stock produk');
                }

                $productHistory = ProductHistory::where('product_id', $item['product_id'])
                    ->where('inventory_id', $request->inventory_id)
                    ->where('remaining_stock', '>', 0)
                    ->orderBy('id', 'ASC')
                    ->orderBy('date', 'ASC')
                    ->get();

                if ($difference < 0) {
                    $opnameQuantity = $difference * -1;

                    foreach ($productHistory as $history) {
                        if ($history->remaining_stock <= $opnameQuantity) {
                            $quantityToOpname = $history->remaining_stock;
                            $history->remaining_stock = 0;
                        } else {
                            $quantityToOpname = $opnameQuantity;
                            $history->remaining_stock -= $opnameQuantity;
                            $opnameQuantity = 0;
                        }

                        $opnameQuantity -= $quantityToOpname;
                        $history->save();

                        $createProductHistory = ProductHistory::create([
                            'product_id' => $item['product_id'],
                            'date' => $date,
                            'quantity' => $quantityToOpname * -1,
                            'purchase_price' => 0,
                            'selling_price' => $difference > 0 ? $product->selling_price : 0,
                            'discount_type' => 'amount',
                            'discount_amount' => 0,
                            'discount_percentage' => 0,
                            'total' => $difference > 0 ? $product->selling_price * $quantityToOpname : 0,
                            // 'remaining_stock' => $quantityToOpname * -1,
                            'reference_number' => $opnameNumber,
                            'category' => 'opname',
                            'type' => $difference > 0 ? 'IN' : 'OUT',
                            'product_history_reference' => $history->id,
                            'inventory_id' => $request->inventory_id,
                        ]);

                        if (!$createProductHistory) {
                            throw new Exception('Gagal membuat history produk');
                        }

                        if ($opnameQuantity > 0) {
                            continue;
                        }

                        $differenceTotalPrice += $difference * $product->selling_price;
                    }
                } else {
                    $opnameQuantity = $difference;

                    $createProductHistory = ProductHistory::create([
                        'product_id' => $item['product_id'],
                        'date' => $date,
                        'quantity' => $opnameQuantity,
                        'purchase_price' => 0,
                        'selling_price' => $product->selling_price,
                        'discount_type' => 'amount',
                        'discount_amount' => 0,
                        'discount_percentage' => 0,
                        'total' => $product->selling_price * $opnameQuantity,
                        'remaining_stock' => $opnameQuantity,
                        'reference_number' => $opnameNumber,
                        'category' => 'opname',
                        'type' => $difference > 0 ? 'IN' : 'OUT',
                        'inventory_id' => $request->inventory_id,
                    ]);

                    if (!$createProductHistory) {
                        throw new Exception('Gagal membuat history produk');
                    }

                    $differenceTotalPrice += $opnameQuantity * $product->selling_price;
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil membuat data stok opname',
            ], 201);
        } catch (Exception $e) {
            DB::rollback();

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
        $data = Opname::with(['details.product.unit', 'inventory', 'user'])->find($id);

        if (!$data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stock Opname tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Opname $opname)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'inventory_id' => 'required|exists:inventories,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.system_quantity' => 'required|numeric|min:1',
            'items.*.physical_quantity' => 'required|numeric|min:1',
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
            $updateStockOpname = $opname->update([
                'date' => $request->date,
                'description' => $request->description,
                'user_id' => auth()->user()->id,
            ]);

            if (!$updateStockOpname) {
                throw new Exception('Gagal mengubah data stock opname');
            }

            $tempStockOpnameDetails = $opname->details()->get();

            if (!$tempStockOpnameDetails) {
                throw new Exception('Data detail stock opname tidak ditemukan');
            }

            // 👉 Return stock on deleted item
            $getDeletedItems = $tempStockOpnameDetails->filter(function ($tempDetail) use ($request) {
                return !collect($request->items)->contains(function ($item) use ($tempDetail) {
                    return $item['product_id'] == $tempDetail->product_id && $item['physical_quantity'] == $tempDetail->physical_quantity;
                });
            });

            if ($getDeletedItems->count() > 0) {
                foreach ($getDeletedItems as $getDeletedItem) {
                    $product = Product::where('id', $getDeletedItem->product_id)->first();

                    $updateProduct = $product->update([
                        'stock' => $product->stock + $getDeletedItem->quantity,
                    ]);

                    if (!$updateProduct) {
                        throw new Exception('Gagal mengubah stok produk');
                    }

                    $productInfo = ProductInfo::where('product_id', $getDeletedItem->product_id)->where('inventory_id', $opname->inventory_id)->first();

                    if (!$productInfo) {
                        throw new Exception('Info produk tidak ditemukan');
                    }

                    $updateProductInfo = $productInfo->update([
                        'total_stock' => $productInfo->total_stock - $getDeletedItem->difference,
                        'total_stock_out' => $getDeletedItem->difference < 0 ? $productInfo->total_stock_out - ($getDeletedItem->difference * -1) : $productInfo->total_stock_out,
                    ]);

                    if (!$updateProductInfo) {
                        throw new Exception('Gagal mengubah info produk');
                    }

                    $getProductHistory = ProductHistory::where('product_id', $getDeletedItem->product_id)
                        ->where('reference_number', $opname->opname_number)
                        ->where('quantity', $getDeletedItem->difference)
                        ->where('category', 'opname')
                        ->first();

                    if ($getDeletedItem->difference < 0) {
                        $updateReferenceProductHistory = ProductHistory::where('id', $getProductHistory->product_history_reference)
                            ->increment('remaining_stock', ($getDeletedItem->difference * -1));

                        if (!$updateReferenceProductHistory) {
                            throw new Exception('Gagal mengubah data histori produk');
                        }
                    }

                    if (!$getProductHistory->forceDelete()) {
                        throw new Exception('Gagal menghapus histori produk');
                    }

                    $checkOpnameDetails = $opname->details()->where('product_id', $getDeletedItem->product_id)->get();

                    if ($checkOpnameDetails->count() > 0) {
                        $deleteStockOpnameDetail = $opname->details()->where('product_id', $getDeletedItem->product_id)->where('physical_quantity', $getDeletedItem->physical_quantity)->forceDelete();

                        if (!$deleteStockOpnameDetail) {
                            throw new Exception('Gagal menghapus data detail stok opname');
                        }
                    }
                }
            }

            $difference = 0;
            $differenceTotalPrice = 0;

            foreach ($request->items as $item) {
                $tempStockOpnameDetail = $tempStockOpnameDetails->where('product_id', $item['product_id'])->first();

                $difference = $item['physical_quantity'] - $item['system_quantity'];

                if ($difference != $tempStockOpnameDetail->difference) {
                    $checkOpnameDetails = $opname->details()->where('product_id', $item['product_id'])->get();

                    if ($checkOpnameDetails->count() > 0) {
                        $deleteStockOpnameDetail = $opname->details()->where('product_id', $item['product_id'])->where('physical_quantity', $tempStockOpnameDetail->physical_quantity)->forceDelete();

                        if (!$deleteStockOpnameDetail) {
                            throw new Exception('Gagal menghapus data detail stok opname');
                        }
                    }

                    $createStockOpnameDetail = OpnameDetail::create([
                        'opname_id' => $opname->id,
                        'product_id' => $item['product_id'],
                        'system_quantity' => $item['system_quantity'],
                        'physical_quantity' => $item['physical_quantity'],
                        'difference' => $difference,
                        'description' => $item['description'],
                    ]);

                    if (!$createStockOpnameDetail) {
                        throw new Exception('Gagal membuat data detail stock opname');
                    }

                    $product = Product::find($item['product_id']);
                    $productInfo = ProductInfo::where('product_id', $item['product_id'])->where('inventory_id', $request->inventory_id)->first();

                    if (!$productInfo) {
                        $productInfo = ProductInfo::create([
                            'product_id' => $item['product_id'],
                            'inventory_id' => $request->inventory_id,
                            'total_stock' => $item['physical_quantity'],
                            'total_stock_out' => 0,
                            'user_id' => auth()->user()->id,
                        ]);

                        if (!$productInfo) {
                            throw new Exception('Gagal membuat info produk');
                        }
                    } else {
                        $updateProductInfo = $productInfo->update([
                            'total_stock' => $item['physical_quantity'],
                            'total_stock_out' => ($difference < 0 ? $productInfo->total_stock_out -
                                $tempStockOpnameDetail->difference + ($difference * -1) : $productInfo->total_stock_out)
                        ]);

                        if (!$updateProductInfo) {
                            throw new Exception('Gagal mengupdate info produk');
                        }
                    }

                    $updateProduct = $product->update([
                        'stock' => $product->stock - $productInfo->total_stock + $item['physical_quantity'],
                    ]);

                    if (!$updateProduct) {
                        throw new Exception('Gagal mengupdate stock produk');
                    }

                    $productHistory = ProductHistory::where('product_id', $item['product_id'])
                        ->where('inventory_id', $opname->inventory_id)
                        ->where('remaining_stock', '>', 0)
                        ->orderBy('id', 'ASC')
                        ->orderBy('date', 'ASC')
                        ->get();

                    if ($difference < 0) {
                        $opnameQuantity = $difference * -1;

                        foreach ($productHistory as $history) {
                            $history->remaining_stock += $tempStockOpnameDetail->difference;

                            if ($history->remaining_stock <= $opnameQuantity) {
                                $quantityToOpname = $history->remaining_stock;
                                $history->remaining_stock = 0;
                            } else {
                                $quantityToOpname = $opnameQuantity;
                                $history->remaining_stock -= $opnameQuantity;
                                $opnameQuantity = 0;
                            }

                            $opnameQuantity -= $quantityToOpname;

                            $history->save();

                            $createProductHistory = ProductHistory::create([
                                'product_id' => $item['product_id'],
                                'date' => $request->date,
                                'quantity' => $quantityToOpname * -1,
                                'purchase_price' => 0,
                                'selling_price' => $difference > 0 ? $product->selling_price : 0,
                                'discount_type' => 'amount',
                                'discount_amount' => 0,
                                'discount_percentage' => 0,
                                'total' => $difference > 0 ? $product->selling_price * $quantityToOpname : 0,
                                // 'remaining_stock' => $quantityToOpname * -1,
                                'balance' => $productInfo->total_stock,
                                'reference_number' => $opname->opname_number,
                                'category' => 'opname',
                                'type' => $difference > 0 ? 'IN' : 'OUT',
                                'product_history_reference' => $history->id,
                                'inventory_id' => $request->inventory_id,
                            ]);

                            if (!$createProductHistory) {
                                throw new Exception('Gagal membuat data histori produk');
                            }

                            if ($opnameQuantity > 0) {
                                continue;
                            }

                            $differenceTotalPrice += $difference * $product->selling_price;
                        }
                    } else {
                        $opnameQuantity = $difference;

                        $createProductHistory = ProductHistory::create([
                            'product_id' => $item['product_id'],
                            'date' => $request->date,
                            'quantity' => $opnameQuantity,
                            'purchase_price' => 0,
                            'selling_price' => $product->selling_price,
                            'discount_type' => 'amount',
                            'discount_amount' => 0,
                            'discount_percentage' => 0,
                            'total' => $product->selling_price * $opnameQuantity,
                            'remaining_stock' => $opnameQuantity,
                            'balance' => $productInfo->total_stock,
                            'reference_number' => $opname->opname_number,
                            'category' => 'opname',
                            'type' => $difference > 0 ? 'IN' : 'OUT',
                            'inventory_id' => $request->inventory_id,
                        ]);

                        if (!$createProductHistory) {
                            throw new Exception('Gagal membuat data histori produk');
                        }

                        $differenceTotalPrice += $opnameQuantity * $product->selling_price;
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil mengubah stok opname',
            ], 200);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Opname $opname)
    {
        DB::beginTransaction();

        try {
            $stockOpnameDetails = $opname->details()->get();

            foreach ($stockOpnameDetails as $item) {
                $product = Product::find($item->product_id);

                $updateProduct = $product->update([
                    'stock' => $item->system_quantity,
                ]);

                if (!$updateProduct) {
                    throw new Exception('Gagal mengubah stok produk');
                }

                $productInfo = ProductInfo::where('product_id', $item->product_id)->where('inventory_id', $opname->inventory_id)->first();

                $updateProductInfo = $productInfo->update([
                    'total_stock' => $productInfo->total_stock - $item->difference,
                    'total_stock_out' => ($item->difference < 0 ? $productInfo->total_stock_out - ($item->difference * -1) : $productInfo->total_stock_out),
                ]);

                if (!$updateProductInfo) {
                    throw new Exception('Gagal mengubah data info produk');
                }
            }

            $deleteProductHistory = ProductHistory::where('reference_number', $opname->opname_number)->where('category', 'opname')->delete();

            if (!$deleteProductHistory) {
                throw new Exception('Gagal menghapus data histori produk');
            }

            if (!$opname->details()->delete()) {
                throw new Exception('Gagal menghapus data detail stok opname');
            }

            if (!$opname->delete()) {
                throw new Exception('Gagal menghapus data stok opname');
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menghapus data stok opname',
            ], 200);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
