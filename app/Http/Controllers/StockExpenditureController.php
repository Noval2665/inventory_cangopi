<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductHistory;
use App\Models\ProductInfo;
use App\Models\PurchaseReception;
use App\Models\StockExpenditure;
use DB;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Validator;

class StockExpenditureController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page;
        $per_page = $request->per_page;
        $search = $request->search;

        $stockExpenditures = StockExpenditure::with([
            'inventory',
            'details',
            'details.product',
            'details.product.subCategory',
            'details.product.unit',
            'details.product.metric',
        ])
            ->when($search, function ($query, $search) {
                return $query->where('stock_expenditure_number', 'LIKE', '%' . $search . '%');
            })
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data pengeluaran stok',
            'stock_expenditures' => $stockExpenditures
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
            'stock_expenditure_items' => ['required', 'array'],
            'stock_expenditure_items.*.product_id' => ['required', 'numeric', 'exists:products,id'],
            'stock_expenditure_items.*.quantity' => ['required', 'numeric'],
            'stock_expenditure_items.*.selling_price' => ['required', 'numeric'],
            'stock_expenditure_items.*.total' => ['required', 'numeric'],
            'stock_expenditure_items.*.discount_type' => ['required', 'string'],
            'stock_expenditure_items.*.discount_amount' => ['nullable', 'numeric'],
            'stock_expenditure_items.*.discount_percentage' => ['nullable'],
            'stock_expenditure_items.*.grandtotal' => ['required', 'numeric'],
            'stock_expenditure_items.*.note' => ['nullable', 'string'],
            'total' => ['required', 'numeric'],
            'discount_type' => ['required', 'string'],
            'discount_amount' => ['nullable', 'numeric'],
            'discount_percentage' => ['nullable', 'numeric'],
            'grandtotal' => ['required', 'numeric'],
            'note' => ['nullable', 'string'],
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
            do {
                $year = date('Y', strtotime($request->date));
                $stockExpenditureNumber = StockExpenditure::generateStockExpenditureNumber($year);
            } while (StockExpenditure::where('stock_expenditure_number', $stockExpenditureNumber)->exists());

            $createStockExpenditure = StockExpenditure::create([
                'stock_expenditure_number' => $stockExpenditureNumber,
                'date' => $request->date,
                'total' => $request->total,
                'discount_type' => $request->discount_type,
                'discount_amount' => $request->discount_amount ?? 0,
                'discount_percentage' => $request->discount_percentage ?? 0,
                'grandtotal' => $request->grandtotal,
                'description' => $request->description,
                'inventory_id' => $request->inventory_id,
                'user_id' => auth()->user()->id,
            ]);

            if (!$createStockExpenditure) {
                throw new HttpException(400, 'Gagal membuat data pengeluaran stok');
            }

            foreach ($request->stock_expenditure_items as $item) {
                $createStockExpenditureItem = $createStockExpenditure->details()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'selling_price' => $item['selling_price'],
                    'total' => $item['total'],
                    'discount_type' => $item['discount_type'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'discount_percentage' => $item['discount_percentage'] ?? 0,
                    'grandtotal' => $item['grandtotal'],
                    'note' => $item['note'],
                    'inventory_id' => $request->inventory_id,
                ]);

                if (!$createStockExpenditureItem) {
                    throw new HttpException(400, 'Gagal membuat data detail pengeluaran stok');
                }

                $product = Product::find($item['product_id']);

                $updateProduct = $product->decrement('stock', $item['quantity']);

                if (!$updateProduct) {
                    throw new HttpException(400, 'Gagal mengurangi stok produk' . $product->product_name);
                }

                $productInfo = ProductInfo::where('product_id', $item['product_id'])
                    ->where('inventory_id', $request->inventory_id)
                    ->first();

                if ($productInfo->total_stock < $item['quantity']) {
                    throw new HttpException(400, 'Stok produk ' . $product->product_name . ' tidak mencukupi');
                }

                $updateProductInfo = ProductInfo::where('product_id', $item['product_id'])
                    ->where('inventory_id', $request->inventory_id)
                    ->update([
                        'total_stock' => $productInfo->total_stock - $item['quantity'],
                        'total_stock_out' => $productInfo->total_stock_out + $item['quantity'],
                    ]);

                if (!$updateProductInfo) {
                    throw new HttpException(400, 'Gagal mengubah data stok informasi produk' . $product->product_name);
                }

                $productHistory = ProductHistory::where('product_id', $item['product_id'])
                    ->where('inventory_id', $request->inventory_id)
                    ->where('remaining_stock', '>', 0)
                    ->orderBy('date', 'ASC')
                    ->get();

                if ($productHistory->count() == 0) {
                    throw new HttpException(400, 'Stok produk' . $product->product_name . 'tidak mencukupi (0) atau tidak ada histori produk');
                }

                $sellQuantity = $item['quantity'];

                foreach ($productHistory as $history) {
                    if ($sellQuantity <= 0) {
                        break;
                    }

                    if ($history->remaining_stock <= $sellQuantity) {
                        $quantityToSell = $history->remaining_stock;
                        $history->remaining_stock = 0;
                    } else {
                        $quantityToSell = $sellQuantity;
                        $history->remaining_stock -= $sellQuantity;
                    }

                    $productHistory = ProductHistory::create([
                        'product_id' => $item['product_id'],
                        'date' => $request->date,
                        'quantity' => $item['quantity'] * -1,
                        'selling_price' => $item['selling_price'],
                        'total' => $item['total'],
                        'discount_type' => $item['discount_type'],
                        'discount_amount' => $item['discount_amount'] ?? 0,
                        'discount_percentage' => $item['discount_percentage'] ?? 0,
                        'grandtotal' => $item['grandtotal'],
                        'reference_number' => $stockExpenditureNumber,
                        'category' => 'stock_expenditure',
                        'type' => 'OUT',
                        'product_history_reference' => $history->id,
                        'inventory_id' => $request->inventory_id,
                    ]);

                    if (!$productHistory) {
                        throw new HttpException(400, 'Gagal membuat data riwayat produk' . $product->product_name);
                    }

                    $sellQuantity -= $quantityToSell;
                    $history->save();

                    if ($sellQuantity > 0) {
                        continue;
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil membuat data pengeluaran stok',
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
    public function update(Request $request, StockExpenditure $stockExpenditure)
    {
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date'],
            'inventory_id' => ['required', 'numeric', 'exists:inventories,id'],
            'stock_expenditure_items' => ['required', 'array'],
            'stock_expenditure_items.*.product_id' => ['required', 'numeric', 'exists:products,id'],
            'stock_expenditure_items.*.quantity' => ['required', 'numeric'],
            'stock_expenditure_items.*.selling_price' => ['required', 'numeric'],
            'stock_expenditure_items.*.total' => ['required', 'numeric'],
            'stock_expenditure_items.*.discount_type' => ['required', 'string'],
            'stock_expenditure_items.*.discount_amount' => ['nullable', 'numeric'],
            'stock_expenditure_items.*.discount_percentage' => ['nullable'],
            'stock_expenditure_items.*.grandtotal' => ['required', 'numeric'],
            'stock_expenditure_items.*.note' => ['nullable', 'string'],
            'total' => ['required', 'numeric'],
            'discount_type' => ['required', 'string'],
            'discount_amount' => ['nullable', 'numeric'],
            'discount_percentage' => ['nullable', 'numeric'],
            'grandtotal' => ['required', 'numeric'],
            'note' => ['nullable', 'string'],
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
            $updateStockExpenditure = $stockExpenditure->update([
                'date' => $request->date,
                'total' => $request->total,
                'discount_type' => $request->discount_type,
                'discount_amount' => $request->discount_amount ?? 0,
                'discount_percentage' => $request->discount_percentage ?? 0,
                'grandtotal' => $request->grandtotal,
                'description' => $request->description,
                'inventory_id' => $request->inventory_id,
                'user_id' => auth()->user()->id,
            ]);

            if (!$updateStockExpenditure) {
                throw new HttpException(400, 'Gagal mengubah data pengeluaran stok');
            }

            $tempStockExpenditureDetails = $stockExpenditure->details()->get();

            if (!$tempStockExpenditureDetails) {
                throw new HttpException(400, 'Gagal mengambil data detail pengeluaran stok');
            }

            if (!$stockExpenditure->details()->forceDelete()) {
                throw new HttpException(400, 'Gagal menghapus data detail pengeluaran stok');
            }

            // 👉 Return stock on deleted item
            $getDeletedItems = $tempStockExpenditureDetails->filter(function ($tempDetail) use ($request) {
                return !collect($request->stock_expenditure_items)->contains(function ($item) use ($tempDetail) {
                    return $item['product_id'] == $tempDetail->product_id && $item['discount_amount'] == $tempDetail->discount_amount;
                });
            });

            if ($getDeletedItems->count() > 0) {
                foreach ($getDeletedItems as $getDeletedItem) {
                    $product = Product::find($getDeletedItem->product_id);

                    $updateProduct = $product->increment('stock', $getDeletedItem->quantity);

                    if (!$updateProduct) {
                        throw new HttpException(400, 'Gagal menambahkan stok produk' . $product->product_name);
                    }

                    $productInfo = ProductInfo::where('product_id', $getDeletedItem->product_id)->where('inventory_id', $stockExpenditure->inventory_id)->first();

                    if (!$productInfo) {
                        throw new HttpException(400, 'Gagal mengambil data informasi produk' . $product->product_name);
                    }

                    $updateProductInfo = $productInfo->update([
                        'total_stock' => $productInfo->total_stock + $getDeletedItem->quantity,
                        'total_stock_out' => $productInfo->total_stock_out - $getDeletedItem->quantity,
                    ]);

                    if (!$updateProductInfo) {
                        throw new HttpException(400, 'Gagal mengubah data informasi produk' . $product->product_name);
                    }

                    $deleteProductHistory = ProductHistory::where('product_id', $getDeletedItem->product_id)
                        ->where('reference_number', $stockExpenditure->stock_expenditure_number)
                        ->where('discount_amount', $getDeletedItem->discount_amount)
                        ->where('category', 'sales')
                        ->forceDelete();

                    if (!$deleteProductHistory) {
                        throw new HttpException(400, 'Gagal menghapus data riwayat produk' . $product->product_name);
                    }
                }
            }

            foreach ($request->stock_expenditure_items as $item) {
                $tempStockExpenditureDetail = $tempStockExpenditureDetails->where('product_id', $item['product_id'])->where('discount_amount', $item['discount_amount'])->first();

                $productHistories = ProductHistory::where('product_id', $item['product_id'])->where('reference_number', $stockExpenditure->stock_expenditure_number)->where('category', 'stock-expenditure')->get();

                foreach ($productHistories as $productHistory) {
                    $productHistoryReference = ProductHistory::where('id', $productHistory->product_history_reference)->first();

                    $updateProductHistory = $productHistoryReference->update([
                        'remaining_stock' => $productHistoryReference->remaining_stock + ($productHistory->quantity * -1),
                    ]);

                    if (!$updateProductHistory) {
                        throw new HttpException(400, 'Gagal mengubah data riwayat produk' . $product->product_name);
                    }
                }

                $checkProductHistory = ProductHistory::where('product_id', $item['product_id'])
                    ->where('reference_number', $stockExpenditure->stock_expenditure_number)
                    ->where('category', 'stock-expenditure')
                    ->first();

                if ($checkProductHistory) {
                    $deleteProductHistory = $checkProductHistory->forceDelete();

                    if (!$deleteProductHistory) {
                        throw new HttpException(400, 'Gagal menghapus data riwayat produk' . $product->product_name);
                    }
                }

                // 👉 Return stock and decrement stock at a time
                $product = Product::where('id', $item['product_id'])->first();

                $updateProduct = $product->update([
                    'stock' => $product->stock - ($tempStockExpenditureDetail ? $tempStockExpenditureDetail->quantity : 0) + $item['quantity'],
                    'selling_price' => $item['selling_price'],
                ]);

                if (!$updateProduct) {
                    throw new HttpException(400, 'Gagal mengubah data produk' . $product->product_name);
                }

                $productInfo = ProductInfo::where('product_id', $item['product_id'])->where('inventory_id', $stockExpenditure->inventory_id)->first();

                $updateProductInfo = $productInfo->update([
                    'total_stock' => $productInfo->total_stock + ($tempStockExpenditureDetail ? $tempStockExpenditureDetail->quantity : 0)
                        - $item['quantity'],
                    'total_stock_out' => $productInfo->total_stock_out - ($tempStockExpenditureDetail ? $tempStockExpenditureDetail->quantity : 0)
                        + $item['quantity'],
                ]);

                if (!$updateProductInfo) {
                    throw new HttpException(400, 'Gagal mengubah data informasi produk' . $product->product_name);
                }

                if (
                    $item['quantity'] > ($productInfo->total_stock + $productInfo->total_stock_out)
                ) {
                    throw new HttpException(422, 'Stok produk ' . $product->name . ' tidak mencukupi. Stok saat ini: ' . ($productInfo->total_stock + $productInfo->total_stock_out));
                }

                $createStockExpenditureDetail = $stockExpenditure->details()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'selling_price' => $item['selling_price'],
                    'total' => $item['total'],
                    'discount_type' => $item['discount_type'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'discount_percentage' => $item['discount_percentage'] ?? 0,
                    'grandtotal' => $item['grandtotal'],
                    'note' => $item['note'] ?? "",
                    'inventory_id' => $stockExpenditure->inventory_id,
                ]);

                if (!$createStockExpenditureDetail) {
                    throw new HttpException(400, 'Gagal membuat data detail pengeluaran stok');
                }

                $productHistory = ProductHistory::where('product_id', $item['product_id'])
                    ->where('inventory_id', $request->inventory_id)
                    ->where('remaining_stock', '>', 0)
                    ->orderBy('date', 'ASC')
                    ->get();

                if ($productHistory->count() == 0) {
                    throw new HttpException(400, 'Stok produk' . $product->product_name . 'tidak mencukupi (0) atau tidak ada histori produk');
                }

                $sellQuantity = $item['quantity'];

                foreach ($productHistory as $history) {
                    if ($sellQuantity <= 0) {
                        break;
                    }

                    if ($history->remaining_stock <= $sellQuantity) {
                        $quantityToSell = $history->remaining_stock;
                        $history->remaining_stock = 0;
                    } else {
                        $quantityToSell = $sellQuantity;
                        $history->remaining_stock -= $sellQuantity;
                    }

                    $productHistory = ProductHistory::create([
                        'product_id' => $item['product_id'],
                        'date' => $request->date,
                        'quantity' => $item['quantity'] * -1,
                        'selling_price' => $item['selling_price'],
                        'total' => $item['total'],
                        'discount_type' => $item['discount_type'],
                        'discount_amount' => $item['discount_amount'] ?? 0,
                        'discount_percentage' => $item['discount_percentage'] ?? 0,
                        'grandtotal' => $item['grandtotal'],
                        'reference_number' => $stockExpenditure->stock_expenditure_number,
                        'category' => 'stock-expenditure',
                        'type' => 'OUT',
                        'product_history_reference' => $history->id,
                        'inventory_id' => $request->inventory_id,
                    ]);

                    if (!$productHistory) {
                        throw new HttpException(400, 'Gagal membuat data riwayat produk' . $product->product_name);
                    }

                    $sellQuantity -= $quantityToSell;
                    $history->save();

                    if ($sellQuantity > 0) {
                        continue;
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil mengubah data pengeluaran stok',
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
