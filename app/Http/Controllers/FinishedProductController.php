<?php

namespace App\Http\Controllers;

use App\Models\FinishedProduct;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FinishedProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page;
        $per_page = $request->per_page ?? 10000;
        $search = $request->search;

        $finishedProducts = FinishedProduct::when($search, function ($query, $search) {
            return $query->where('finished_product_name', 'LIKE', '%' . $search . '%');
        })
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data produk jadi',
            'finishedProducts' => $finishedProducts,
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
            'category_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $createFinishedProduct = FinishedProduct::create([
            'finished_product_name' => ucwords($request->finished_product_name),
            'user_id' => auth()->user()->id,
        ]);

        if (!$createFinishedProduct) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat data produk jadi',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil membuat data produk jadi',
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
    public function update(Request $request, FinishedProduct $finishedProduct)
    {
        $validator = Validator::make($request->all(), [
            'finished_product_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $updateFinishedProduct = $finishedProduct->update([
            'finished_product_name' => ucwords($request->finished_product_name),
            'user_id' => auth()->user()->id,
        ]);

        if (!$updateFinishedProduct) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah data produk jadi',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengubah data produk jadi',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FinishedProduct $finishedProduct)
    {
        $user = auth()->user();

        if ($user->role->name != 'Admin') {
            $this->deactivate($finishedProduct->id);
        } else {
            $finishedProduct = FinishedProduct::where('id', $finishedProduct->id)->firstOrFail();

            if (!$finishedProduct->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data produk jadi',
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menghapus data produk jadi',
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'message' => $user->role->name != 'Admin' ? 'Berhasil menonaktifkan data produk jadi' : 'Berhasil menghapus data produk jadi',
        ], 200);
    }

    public function deactivate($id)
    {
        $updateFinishedProduct = FinishedProduct::where('id', $id)->update([
            'is_active' => 0,
            'deactivated_at' => Carbon::now(),
        ]);

        if (!$updateFinishedProduct) {
            return response()->json([

                'status' => 'error',
                'message' => 'Gagal menonaktifkan produk jadi',
            ], 400);
        }
    }
}
