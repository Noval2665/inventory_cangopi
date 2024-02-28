<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Carbon\Carbon;
use App\Models\Brand;
use Symfony\Component\Console\Descriptor\Descriptor;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page;
        $per_page = $request->per_page ?? 10000;
        $search = $request->search;

        $brand = Brand::when($search, function ($query, $search) {
            return $query->where('brand_name', 'LIKE', '%' . $search . '%');
        })
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data brand',
            'brands' => $brand,
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
            'brand_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        
        $brand = Brand::create([
            'brand_name' => ucwords($request->brand_name),
            'user_id' => auth()->user()->id,
        ]);

        if (!$brand) {
            return response()->json([
                'status' => 'error',
                'message' => 'Brand gagal ditambahkan'
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Brand berhasil ditambahkan',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Brand $brand)
    {
        return response()->json(['brand' => $brand]);
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
    public function update(Request $request, Brand $brand)
    {
        $validator = Validator::make($request->all(), [
            'brand_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $updateBrand = $brand->update([
            'brand_name' => ucwords($request->brand_name),
            'user_id' => auth()->user()->id,
        ]);

        if (!$updateBrand){
            return response()->json([
                'status' => 'error',
                'message' => 'Brand gagal diperbarui',
            ], 400);
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Brand berhasil diperbarui',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Brand $brand)
    {
        $user = auth()->user();

        if($user->role->name != 'Admin'){
            $this->deactivate($brand->id);
        }
        else{
            if ($brand->products()->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak dapat menghapus data brand yang memiliki produk terkait'
                ], 422);
            }
            if(!$brand->delete()){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data brand',
                ], 400);
            }
            $brand->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menghapus data brand',
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => $user->role->name != 'Admin' ? 'Berhasil mengonaktifkan data brand' : 'Berhasil menghapus data brand',
        ]);
    }

    public function deactivate($id)
    {
        $updateBrand = Brand::where('id', $id)->update([
            'is_active' => 0,
            'deactivated_at' => Carbon::now(),
        ]);

        if(!$updateBrand){
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menonaktifkan brand',
            ]);
        }
    }
}
