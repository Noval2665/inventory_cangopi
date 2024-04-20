<?php

namespace App\Http\Controllers;

use App\Models\OrderCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OrderCodeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page;
        $per_page = $request->per_page ?? 10000;
        $search = $request->search;

        $orderCodes = OrderCode::when($search, function ($query, $search) {
            return $query->where('order_code', 'LIKE', '%' . $search . '%');
        })
            ->paginate($per_page, ['*'], 'page', $page);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data kode order',
            'orderCodes' => $orderCodes,
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
            'order_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $createOrderCode = OrderCode::create([
            'order_code' => $request->order_code,
            'user_id' => auth()->user()->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Data kode order berhasil ditambahkan',
            'orderCode' => $createOrderCode,
        ], 201);

        if (!$createOrderCode) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat data kode order',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil membuat data kode order',
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
    public function update(Request $request, OrderCode $orderCode)
    {
        $validator = Validator::make($request->all(), [
            'order_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $updateOrderCode = $orderCode->update([
            'order_code' => $request->order_code,
            'user_id' => auth()->user()->id,
        ]);

        if (!$updateOrderCode) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah data kode order',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengubah data kode order',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrderCode $orderCode)
    {
        $user = auth()->user();

        if($user->role->name != 'Admin') {
            $this->deactivate($orderCode->id);
        }
        else{
            if($orderCode->orderList()->exist()){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data kode order, karena kode order masih digunakan',
                ], 400);
            }
            if (!$orderCode->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data kode order',
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menghapus data kode order',
            ], 200);
        }
        return response()->json([
            'status' => 'success',
            'message' => $user->role->name != 'Admin' ? 'Berhasil menonaktifkan data kode order' : 'Berhasil menghapus data kode order',
        ], 400);
    }

    public function deactivate($id){
        $updateOrderCode = OrderCode::where('id', $id)->update([
            'is_active' => 0,
            'deactivated_at' => Carbon::now(),
        ]);

        if (!$updateOrderCode) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menonaktifkan data kode order',
            ], 400);
        }
    }
}
