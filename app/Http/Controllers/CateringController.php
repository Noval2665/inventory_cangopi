<?php

namespace App\Http\Controllers;

use App\Models\Catering;
use App\Models\PurchaseReport;
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
        $per_page = $request->per_page ?? 10000;
        $search = $request->search;

        $report = Catering::when($search, function ($query, $search, $date) {
            return $query->where('order_list', 'LIKE', '%' . $search . '%')
            or $query->where('date', '==', $date);
        })
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data laporan penjualan',
            //'categories' => $purchase_order,
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

        $updateCatering = $catering->update([
            'catering_name' => $request->catering_name,
            'catering_code' => $request->catering_code,
            'status' => $request->status,
            'date' => $request->date ? date('Y-m-d', strtotime($request->date)) : null,
            'user_id' => auth()->user()->id,
            'order_list_id' => $request->order_list_id,
        ]);

        if (!$updateCatering) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah data laporan penjualan',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengubah data laporan penjualan',
            'catering' => $catering,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Catering $catering)
    {
        $user = auth()->user();

        if ($user->role->name != 'Admin') {
            $this->deactivate($catering->id);
        }
        else{
            if($catering->orderList()->exist()){
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

?>