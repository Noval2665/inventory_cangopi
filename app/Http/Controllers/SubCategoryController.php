<?php

namespace App\Http\Controllers;

use App\Models\SubCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SubCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page;
        $per_page = $request->per_page ?? 10000;
        $search = $request->search;

        if ($search) {
            $page = 1;
        }

        $subCategories = SubCategory::with(['category'])->when($search, function ($query, $search) {
            return $query->where('sub_category_name', 'LIKE', '%' . $search . '%')
                ->orWhereHas('category', function ($query) use ($search) {
                    $query->where('category_name', 'LIKE', '%' . $search . '%');
                });
        })
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data sub kategori',
            'sub_categories' => $subCategories,
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
            'category_id' => 'required|exists:categories,id',
            'sub_category_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $createSubCategory = SubCategory::create([
            'category_id' => $request->category_id,
            'sub_category_name' => ucwords($request->sub_category_name),
            'user_id' => auth()->user()->id,
        ]);

        if (!$createSubCategory) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat data sub kategori',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil membuat data sub kategori',
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
    public function update(Request $request, SubCategory $subCategory)
    {

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'sub_category_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $updateSubCategory = $subCategory->update([
            'category_id' => $request->category_id,
            'sub_category_name' => ucwords($request->sub_category_name),
            'user_id' => auth()->user()->id,
        ]);

        if (!$updateSubCategory) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah data sub kategori',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengubah data sub kategori',
        ], 200);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SubCategory $subCategory)
    {
        $user = auth()->user();

        if ($user->role->name != 'Admin') {
            $this->deactivate($subCategory->id);
        } else {
            if ($subCategory->products()->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak dapat menghapus data sub kategori produk yang memiliki produk terkait'
                ], 422);
            }
            if (!$subCategory->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data sub kategori',
                ], 400);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => $user->role->name != 'Admin' ? 'Berhasil menonaktifkan sub kategori' : 'Berhasil menghapus data sub kategori',
        ], 200);
    }

    public function deactivate($id)
    {
        $updateSubCategory = SubCategory::where('id', $id)->update([
            'is_active' => 0,
            'deactivated_at' => Carbon::now(),
        ]);

        if (!$updateSubCategory) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menonaktifkan sub kategori',
            ], 400);
        }
    }
}
