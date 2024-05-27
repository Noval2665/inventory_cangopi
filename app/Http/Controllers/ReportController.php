<?php

namespace App\Http\Controllers;

use App\Models\MarketList;
use App\Models\OrderList;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    // 👉 Order list report
    public function orderLists(Request $request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $search = $request->search;

        $orderLists = OrderList::with([
            'inventory',
            'details',
            'details.product',
            'details.product.supplier',
            'details.product.subCategory.category',
            'details.product.unit',
            'details.product.metric',
            'details.description',
            'user'
        ])
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
            'details',
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
}
