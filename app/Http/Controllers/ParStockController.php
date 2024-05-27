<?php

namespace App\Http\Controllers;

use App\Models\ParStock;
use App\Models\ParStockDetail;
use App\Models\Product;
use App\Models\ProductHistory;
use App\Models\ProductInfo;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

        $parStock = ParStock::with([
            'inventory',
            'details',
            'details.product.subCategory.category',
            'details.product.unit',
            'details.product.metric',
        ])->when($search, function ($query, $search) {
            return $query->where('par_stock', 'LIKE', '%' . $search . '%');
        })
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data par stock',
            'par_stocks' => $parStock,
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
            'par_stock_products' => ['required', 'array'],
            'par_stock_products.*.product_id' => ['required', 'numeric', 'exists:products,id'],
            'par_stock_products.*.physical_stock' => ['required', 'numeric'],
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
                $parStockNumber = ParStock::generateParStockNumber($year);
            } while (ParStock::where('par_stock_number', $parStockNumber)->exists());

            $createParStock = ParStock::create([
                'par_stock_number' => $parStockNumber,
                'date' => $request->date,
                'inventory_id' => $request->inventory_id,
                'description' => $request->description,
                'user_id' => auth()->user()->id,
            ]);

            if (!$createParStock) {
                throw new Exception('Gagal membuat data par stok');
            }

            foreach ($request->par_stock_products as $item) {
                $productInfo = ProductInfo::where('product_id', $item['product_id'])->where('inventory_id', $request->inventory_id)->first();
                $productInfoStock = $productInfo ? $productInfo->total_stock : 0;

                $difference = $item['physical_stock'] - $productInfoStock;

                $createParStockDetail = ParStockDetail::create([
                    'par_stock_id' => $createParStock->id,
                    'product_id' => $item['product_id'],
                    'system_stock' => $productInfoStock,
                    'physical_stock' => $item['physical_stock'],
                    'difference' => $difference,
                ]);

                if (!$createParStockDetail) {
                    throw new Exception('Gagal membuat data detail par stok');
                }

                $product = Product::find($item['product_id']);

                $updateProduct = $product->update([
                    'stock' => $product->stock - $productInfoStock + $item['physical_stock'],
                ]);

                if (!$updateProduct) {
                    throw new Exception('Gagal mengupdate data stock produk' . $item['product_name']);
                }

                if (!$productInfo) {
                    $productInfo = ProductInfo::create([
                        'product_id' => $item['product_id'],
                        'inventory_id' => $request->inventory_id,
                        'total_stock' => $item['physical_stock'],
                        'total_stock_out' => 0,
                        'user_id' => auth()->user()->id,
                    ]);

                    if (!$productInfo) {
                        throw new Exception('Gagal membuat info produk');
                    }
                } else {
                    $updateProductInfo = $productInfo->update([
                        'total_stock' => $item['physical_stock'],
                        'total_stock_out' => ($difference < 0 ? $productInfo->total_stock_out + ($difference * -1) : $productInfo->total_stock_out)
                    ]);

                    if (!$updateProductInfo) {
                        throw new Exception('Gagal mengupdate data informasi produk' . $item['product_name']);
                    }
                }

                $productHistory = ProductHistory::where('product_id', $item['product_id'])
                    ->where('inventory_id', $request->inventory_id)
                    ->where('remaining_stock', '>', 0)
                    ->orderBy('date', 'ASC')
                    ->get();


                if ($difference < 0) {
                    $parStockQuantity = $difference * -1;

                    foreach ($productHistory as $history) {
                        if ($history->remaining_stock <= $parStockQuantity) {
                            $quantityToParStock = $history->remaining_stock;
                            $history->remaining_stock = 0;
                        } else {
                            $quantityToParStock = $parStockQuantity;
                            $history->remaining_stock -= $parStockQuantity;
                            $parStockQuantity = 0;
                        }

                        $parStockQuantity -= $quantityToParStock;
                        $history->save();

                        $createProductHistory = ProductHistory::create([
                            'product_id' => $item['product_id'],
                            'date' => $request->date,
                            'quantity' => $quantityToParStock * -1,
                            'purchase_price' => 0,
                            'selling_price' => 0,
                            'discount_type' => 'amount',
                            'discount_amount' => 0,
                            'discount_percentage' => 0,
                            'total' => 0,
                            // 'remaining_stock' => $quantityToParStock * -1,
                            'reference_number' => $parStockNumber,
                            'category' => 'par-stock',
                            'type' => $difference > 0 ? 'IN' : 'OUT',
                            'product_history_referepnce' => $history->id,
                            'inventory_id' => $request->inventory_id,
                        ]);

                        if (!$createProductHistory) {
                            throw new Exception('Gagal membuat history produk');
                        }

                        if ($parStockQuantity > 0) {
                            continue;
                        }
                    }
                } else {
                    $parStockQuantity = $difference;

                    $createProductHistory = ProductHistory::create([
                        'product_id' => $item['product_id'],
                        'date' => $request->date,
                        'quantity' => $parStockQuantity,
                        'purchase_price' => 0,
                        'selling_price' => 0,
                        'discount_type' => 'amount',
                        'discount_amount' => 0,
                        'discount_percentage' => 0,
                        'total' => 0,
                        'remaining_stock' => $parStockQuantity,
                        'reference_number' => $parStockNumber,
                        'category' => 'par-stock',
                        'type' => $difference > 0 ? 'IN' : 'OUT',
                        'inventory_id' => $request->inventory_id,
                    ]);

                    if (!$createProductHistory) {
                        throw new Exception('Gagal membuat history produk');
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil membuat data par stok',
                'par_stock' => $createParStock,
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
    public function update(Request $request, ParStock $parStock)
    {
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date'],
            'inventory_id' => ['required', 'numeric', 'exists:inventories,id'],
            'par_stock_products' => ['required', 'array'],
            'par_stock_products.*.product_id' => ['required', 'numeric', 'exists:products,id'],
            'par_stock_products.*.physical_stock' => ['required', 'numeric'],
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
            $updateParStock = $parStock->update([
                'date' => $request->date,
                'description' => $request->description,
                'user_id' => auth()->user()->id,
            ]);

            if (!$updateParStock) {
                throw new HttpException(400, 'Gagal mengubah data par stok');
            }

            $tempParStockDetails = $parStock->details()->get();

            if (!$tempParStockDetails) {
                throw new HttpException(400, 'Gagal mengambil data detail par stok');
            }

            // 👉 Return stock on deleted item
            $getDeletedItems = $tempParStockDetails->filter(function ($tempDetail) use ($request) {
                return !collect($request->par_stock_products)->contains(function ($item) use ($tempDetail) {
                    return $item['product_id'] == $tempDetail->product_id && $item['physical_stock'] == $tempDetail->physical_stock;
                });
            });

            if ($getDeletedItems->count() > 0) {
                foreach ($getDeletedItems as $getDeletedItem) {
                    $product = Product::where('id', $getDeletedItem->product_id)->first();

                    $updateProduct = $product->update([
                        'stock' => $product->stock + $getDeletedItem->quantity,
                    ]);

                    if (!$updateProduct) {
                        throw new HttpException(400, 'Gagal mengubah data stok produk' . $product->product_name);
                    }

                    $productInfo = ProductInfo::where('product_id', $getDeletedItem->product_id)->where('inventory_id', $parStock->inventory_id)->first();

                    if (!$productInfo) {
                        throw new HttpException(400, 'Gagal mengambil data informasi produk' . $product->product_name);
                    }

                    $updateProductInfo = $productInfo->update([
                        'total_stock' => $productInfo->total_stock - $getDeletedItem->difference,
                        'total_stock_out' => $getDeletedItem->difference < 0 ? $productInfo->total_stock_out - ($getDeletedItem->difference * -1) : $productInfo->total_stock_out,
                    ]);

                    if (!$updateProductInfo) {
                        throw new HttpException(400, 'Gagal mengubah data informasi produk' . $product->product_name);
                    }

                    $getProductHistory = ProductHistory::where('product_id', $getDeletedItem->product_id)
                        ->where('reference_number', $parStock->par_stock_number)
                        ->where('quantity', $getDeletedItem->difference)
                        ->where('category', 'par-stock')
                        ->first();

                    if ($getDeletedItem->difference < 0) {
                        $updateReferenceProductHistory = ProductHistory::where('id', $getProductHistory->product_history_reference)
                            ->increment('remaining_stock', ($getDeletedItem->difference * -1));

                        if (!$updateReferenceProductHistory) {
                            throw new HttpException(400, 'Gagal mengubah data histori produk' . $product->product_name);
                        }
                    }

                    if (!$getProductHistory->forceDelete()) {
                        throw new HttpException(400, 'Gagal menghapus data histori produk');
                    }

                    $checkOpnameDetails = $parStock->details()->where('product_id', $getDeletedItem->product_id)->get();

                    if ($checkOpnameDetails->count() > 0) {
                        $deleteStockOpnameDetail = $parStock->details()->where('product_id', $getDeletedItem->product_id)->where('physical_stock', $getDeletedItem->physical_stock)->forceDelete();

                        if (!$deleteStockOpnameDetail) {
                            throw new HttpException(400, 'Gagal menghapus data detail par stok');
                        }
                    }
                }
            }

            $difference = 0;

            foreach ($request->par_stock_products as $item) {
                $tempParStockDetail = $tempParStockDetails->where('product_id', $item['product_id'])->first();

                $difference = $item['physical_stock'] - $item['system_stock'];

                if ($difference != $tempParStockDetail->difference) {
                    $checkParStockDetails = $parStock->details()->where('product_id', $item['product_id'])->get();

                    if ($checkParStockDetails->count() > 0) {
                        $deleteParStockDetail = $parStock->details()->where('product_id', $item['product_id'])->where('physical_stock', $tempParStockDetail->physical_stock)->forceDelete();

                        if (!$deleteParStockDetail) {
                            throw new HttpException(400, 'Gagal menghapus data detail par stok');
                        }
                    }

                    $productInfo = ProductInfo::where('product_id', $item['product_id'])->where('inventory_id', $parStock->inventory_id)->first();

                    $createParStockDetail = $parStock->details()->create([
                        'par_stock_id' => $parStock->id,
                        'product_id' => $item['product_id'],
                        'system_stock' => $productInfo->total_stock,
                        'physical_stock' => $item['physical_stock'],
                        'difference' => $difference,
                        // 'description' => $item['description'] ?? "",
                    ]);

                    if (!$createParStockDetail) {
                        throw new HttpException(400, 'Gagal membuat data detail par stok');
                    }

                    $product = Product::find($item['product_id']);

                    $updateProduct = $product->update([
                        'stock' => $item['physical_stock'],
                    ]);

                    if (!$updateProduct) {
                        throw new HttpException(400, 'Gagal mengubah data stok produk' . $item['product_name']);
                    }

                    $updateProductInfo = $productInfo->update([
                        'total_stock' => $item['physical_stock'],
                        'total_stock_out' => ($difference < 0 ? $productInfo->total_stock_out -
                            $tempParStockDetail->difference + ($difference * -1) : $productInfo->total_stock_out)
                    ]);

                    if (!$updateProductInfo) {
                        throw new HttpException(400, 'Gagal mengubah data informasi produk' . $item['product_name']);
                    }

                    $productHistory = ProductHistory::where('product_id', $item['product_id'])
                        ->where('inventory_id', $parStock->inventory_id)
                        ->where('remaining_stock', '>', 0)
                        ->orderBy('date', 'ASC')
                        ->get();

                    if ($difference < 0) {
                        $opnameParStock = $difference * -1;

                        foreach ($productHistory as $history) {
                            $history->remaining_stock += $tempParStockDetail->difference;

                            if ($history->remaining_stock <= $opnameParStock) {
                                $quantityToParStock = $history->remaining_stock;
                                $history->remaining_stock = 0;
                            } else {
                                $quantityToParStock = $opnameParStock;
                                $history->remaining_stock -= $opnameParStock;
                                $opnameParStock = 0;
                            }

                            $opnameParStock -= $quantityToParStock;

                            $history->save();

                            $createProductHistory = ProductHistory::create([
                                'product_id' => $item['product_id'],
                                'date' => $request->date,
                                'quantity' => $quantityToParStock * -1,
                                'purchase_price' => 0,
                                'selling_price' => 0,
                                'discount_type' => 'amount',
                                'discount_amount' => 0,
                                'discount_percentage' => 0,
                                'total' => 0,
                                // 'remaining_stock' => $quantityToParStock * -1,
                                'reference_number' => $parStock->par_stock_number,
                                'category' => 'par-stock',
                                'type' => $difference > 0 ? 'IN' : 'OUT',
                                'product_history_reference' => $history->id,
                                'inventory_id' => $request->inventory_id,
                            ]);

                            if (!$createProductHistory) {
                                throw new HttpException(400, 'Gagal membuat data histori produk' . $item['product_name']);
                            }

                            if ($opnameParStock > 0) {
                                continue;
                            }
                        }
                    } else {
                        $opnameParStock = $difference;

                        $createProductHistory = ProductHistory::create([
                            'product_id' => $item['product_id'],
                            'date' => $request->date,
                            'quantity' => $opnameParStock,
                            'purchase_price' => 0,
                            'selling_price' => 0,
                            'discount_type' => 'amount',
                            'discount_amount' => 0,
                            'discount_percentage' => 0,
                            'total' => 0,
                            'remaining_stock' => $opnameParStock,
                            'reference_number' => $parStock->par_stock_number,
                            'category' => 'par-stock',
                            'type' => $difference > 0 ? 'IN' : 'OUT',
                            'inventory_id' => $request->inventory_id,
                        ]);

                        if (!$createProductHistory) {
                            throw new Exception('Gagal membuat data histori produk');
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil mengubah data par stok',
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
    public function destroy(ParStock $parStock)
    {
        $user = auth()->user();

        if ($user->role->name != 'Admin') {
            $this->deactivate($parStock->id);
        } else {
            $parStock = ParStock::where('id', $parStock->id)->first();

            if (!$parStock->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data par stock',
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menghapus par stock',
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'message' => $user->role->name != 'Admin' ? 'Berhasil menonaktifkan data par stock' : 'Berhasil menghapus data par stock',
        ], 200);
    }

    public function activate($id)
    {
        $updateParstock = ParStock::where('id', $id)->update([
            'is_active' => 1,
            'activated_at' => Carbon::now(),
        ]);
    }

    public function deactivate($id)
    {
        $updateParstock = ParStock::where('id', $id)->update([
            'is_active' => 0,
            'deactivated_at' => Carbon::now(),
        ]);

        if (!$updateParstock) {
            return response()->json([

                'status' => 'error',
                'message' => 'Gagal menonaktifkan par stock',
            ], 400);
        }
    }
}
