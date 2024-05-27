<?php

namespace App\Http\Controllers;

use App\Models\MarketList;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MarketListController extends Controller
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

        $marketLists = MarketList::with([
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
            'market_lists' => $marketLists
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
            'order_list_id' => ['required', 'numeric', 'exists:order_lists,id'],
            'note' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $createMarketList = MarketList::create([
            'market_list_name' => ucwords($request->market_list_name),
            'status' => $request->status,
            'date' => $request->date,
            'user_id' => auth()->user()->id,
        ]);

        if (!$createMarketList) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat data market list',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil membuat data market list',
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
    public function update(Request $request, MarketList $marketList)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:Waiting,Approved,Rejected,Canceled',
            'evidence_of_transfer' => 'nullable|image|mimes:jpeg,png,jpg,pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $updateMarketList = $marketList->update([
            'status' => $request->status,
            'evidence_of_transfer' => $request->file('evidence_of_transfer')
                ? $request->file('evidence_of_transfer')->store('images', 'public')
                : $marketList->evidence_of_transfer,
            'user_id' => auth()->user()->id,
        ]);

        if (!$updateMarketList) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah data market list',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengubah data market list',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MarketList $marketList)
    {
        $user = auth()->user();

        if ($user->role->name != 'Admin') {
            $this->deactivate($marketList->id);
        } else {
            if ($marketList->orderList()->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak dapat menghapus data market list yang memiliki order list terkait'
                ], 422);
            }

            if (!$marketList->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data market list',
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menghapus data market list',
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'message' => $user->role->name != 'Admin' ? 'Berhasil menonaktifkan data market list' : 'Gagal menghapus data market list',
        ]);
    }

    public function deactivate($id)
    {
        $updateMarketList = MarketList::where('id', $id)->update([
            'is_active' => 0,
            'deactivated_at' => Carbon::now(),
        ]);

        if (!$updateMarketList) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menonaktifkan market list',
            ], 400);
        }
    }
}
