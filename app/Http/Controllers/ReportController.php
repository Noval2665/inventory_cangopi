<?php

namespace App\Http\Controllers;

use App\Models\MarketList;
use App\Models\Opname;
use App\Models\OrderList;
use App\Models\ParStock;
use App\Models\PurchaseReturn;
use App\Models\StockExpenditure;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    // 👉 Order list report
    public function orderLists(Request $request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $category = $request->category;
        $search = $request->search;

        $orderLists = OrderList::with([
            'inventory',
            'details' => function ($query) use ($category) {
                if ($category != 'caterings') {
                    $query->where('description_id', '!=', 6);
                } else {
                    $query->where('description_id', 6);
                }
            },
            'details.product',
            'details.product.supplier',
            'details.product.subCategory.category',
            'details.product.unit',
            'details.product.metric',
            'details.description',
            'user'
        ])
            ->when($category == 'caterings', function ($query) {
                $query->with('catering')->whereHas('catering');
            })
            ->when($search, function ($query, $search) {
                return $query->where('order_list_number', 'LIKE', '%' . $search . '%');
            })
            ->when($startDate, function ($query, $startDate) {
                return $query->whereDate('date', '>=', $startDate);
            })
            ->when($endDate, function ($query, $endDate) {
                return $query->whereDate('date', '<=', $endDate);
            })
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data laporan order list',
            'data' => $orderLists,
        ], 200);
    }

    // 👉 Market list report
    public function marketLists(Request $request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $status = $request->status;
        $search = $request->search;

        $marketLists = MarketList::with([
            'orderList',
            'orderList.inventory',
            'orderList.details',
            'orderList.details.product',
            'orderList.details.product.supplier',
            'orderList.details.product.subCategory.category',
            'orderList.details.product.unit',
            'orderList.details.product.metric',
            'orderList.details.description',
            'user'
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
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data market list',
            'data' => $marketLists
        ], 200);
    }

    // 👉 Purchase reception report
    public function purchaseReceptions(Request $request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $search = $request->search;
        $user = $request->user;
        $inventory = $request->inventory;
        $supplier = $request->supplier;

        $data = [];

        $data = OrderList::with([
            'inventory',
            'details' => function ($query) {
                $query->where('description_id', '!=', 6);
            },
            'details.product',
            'details.product.supplier',
            'details.product.subCategory.category',
            'details.product.unit',
            'details.product.metric',
            'details.description',
            'user'
        ])
            ->when($user, function ($query, $user) {
                return $query->where('user_id', $user);
            })
            ->when($inventory, function ($query, $inventory) {
                return $query->where('inventory_id', $inventory);
            })
            ->when($supplier, function ($query, $supplier) {
                return $query->where('supplier_id', $supplier);
            })
            ->when($search, function ($query, $search) {
                return $query->whereHas('details.product', function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%');
                })
                    ->orWhereHas('supplier', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('user', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhere('purchase_number', 'like', '%' . $search . '%');
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil menampilkan data',
            'data' => $data
        ]);
    }

    // 👉 Stock expenditure report
    public function stockExpenditures(Request $request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $search = $request->search;

        $stockExpenditures = StockExpenditure::with([
            'inventory',
            'details',
            'details.product',
            'details.product.supplier',
            'details.product.subCategory.category',
            'details.product.unit',
            'details.product.metric',
            'user'
        ])
            ->when($search, function ($query, $search) {
                return $query->where('stock_expenditure_number', 'LIKE', '%' . $search . '%')
                    ->orWhereHas('details.product', function ($query) use ($search) {
                        $query->where('name', 'LIKE', '%' . $search . '%');
                    });
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data laporan pengeluaran stok',
            'data' => $stockExpenditures,
        ], 200);
    }

    // 👉 Purchase return report
    public function purchaseReturns(Request $request)
    {
        $startDate = date('Y-m-d', strtotime($request->start_date)) . ' 00:00:00';
        $endDate = date(
            'Y-m-d',
            strtotime($request->end_date)
        ) . ' 23:59:59';

        $search = $request->search;
        $supplier = $request->supplier;
        $user = $request->user;
        $inventory = $request->inventory;
        $returnType = $request->return_type;

        $data = [];

        $data = PurchaseReturn::with(['orderList', 'orderList.inventory', 'details', 'details.product.unit', 'user'])
            ->when($supplier, function ($query, $supplier) {
                return $query->whereHas('orderList.supplier', function ($query) use ($supplier) {
                    $query->where('id', $supplier);
                });
            })
            ->when($user, function ($query, $user) {
                return $query->where('user_id', $user);
            })
            ->when($inventory, function ($query, $inventory) {
                return $query->whereHas('orderList.inventory', function ($query) use ($inventory) {
                    $query->where('id', $inventory);
                });
            })
            ->when($returnType, function ($query, $returnType) {
                return $query->where('return_type', $returnType);
            })
            ->when($search, function ($query, $search) {
                return $query->whereHas('orderList', function ($query) use ($search) {
                    $query->where('invoice_number', 'like', '%' . $search . '%');
                })
                    ->orWhereHas('orderList.details', function ($query) use ($search) {
                        $query->whereHas('product', function ($query) use ($search) {
                            $query->where('name', 'like', '%' . $search . '%');
                        });
                    })
                    ->orWhere('purchase_return_number', 'like', '%' . $search . '%');
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil menampilkan data',
            'data' => $data
        ]);
    }

    // 👉 Par stok report
    public function parStocks(Request $request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $search = $request->search;

        $parStocks = ParStock::with([
            'inventory',
            'details',
            'details.product',
            'details.product.subCategory.category',
            'details.product.unit',
            'details.product.metric',
            'user'
        ])
            ->when($search, function ($query, $search) {
                return $query->where('par_stock_number', 'LIKE', '%' . $search . '%')
                    ->orWhereHas('details.product', function ($query) use ($search) {
                        $query->where('name', 'LIKE', '%' . $search . '%');
                    });
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data laporan par stok',
            'data' => $parStocks,
        ], 200);
    }

    // 👉 Stock opname report
    public function opnames(Request $request)
    {
        $startDate = date('Y-m-d', strtotime($request->start_date)) . ' 00:00:00';
        $endDate = date(
            'Y-m-d',
            strtotime($request->end_date)
        ) . ' 23:59:59';
        $search = $request->search;
        $user = $request->user;
        $inventory = $request->inventory;

        $data = Opname::with(['user', 'inventory', 'details.product.unit'])
            ->when($user, function ($query, $user) {
                return $query->where('user_id', $user);
            })
            ->when($inventory, function ($query, $inventory) {
                return $query->where('inventory_id', $inventory);
            })
            ->when($search, function ($query, $search) {
                return $query->whereHas('details.product', function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%');
                })
                    ->orWhereHas('inventory', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('user', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhere('opname_number', 'like', '%' . $search . '%');
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil menampilkan data',
            'data' => $data,
        ]);
    }
}
