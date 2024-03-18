<?php

namespace App\Http\Controllers;

use App\Models\ParStock;
use Carbon\Carbon;
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

        $parstock = ParStock::when($search, function ($query, $search) {
            return $query->where('par_stock', 'LIKE', '%' . $search . '%');
        })
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data par stock',
            'parstock' => $parstock,
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
            'par_stock' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $createParstock = ParStock::create([
            //'' => ucwords($request->),
            'user_id' => auth()->user()->id,
        ]);

        if (!$createParstock) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat data par stock',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil membuat data par stock',
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
    public function update(Request $request, ParStock $parStock)
    {
        $validator = Validator::make($request->all(), [
            'par_stock' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $updateParstock = $parStock->update([
            'par_stock' => ucwords($request->par_stock),
            'user_id' => auth()->user()->id,
        ]);

        if (!$updateParstock) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah data kategori',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengubah data par stock',
        ], 200);
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
