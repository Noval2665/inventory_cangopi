<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page;
        $per_page = $request->per_page ?? 10000;
        $search = $request->search;

        $categories = Category::when($search, function ($query, $search) {
            return $query->where('category_name', 'LIKE', '%' . $search . '%');
        })
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data kategori',
            'categories' => $categories,
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

        $createCategory = Category::create([
            'category_name' => ucwords($request->category_name),
            'user_id' => auth()->user()->id,
        ]);

        if (!$createCategory) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat data kategori',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil membuat data kategori',
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
    public function update(Request $request, Category $category)
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

        $updateCategory = $category->update([
            'category_name' => ucwords($request->category_name),
            'user_id' => auth()->user()->id,
        ]);

        if (!$updateCategory) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah data kategori',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengubah data kategori',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        $user = auth()->user();

        if ($user->role->name != 'Admin') {
            $this->deactivate($category->id);
        } else {
            if ($category->subCategories()->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak dapat menghapus data kategori produk yang memiliki sub kategori terkait'
                ], 422);
            }
            if (!$category->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data kategori',
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menghapus data kategori',
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'message' => $user->role->name != 'Admin' ? 'Berhasil menonaktifkan kategori' : 'Berhasil menghapus data kategori',
        ], 200);
    }

    public function deactivate($id)
    {
        $updateCategory = Category::where('id', $id)->update([
            'is_active' => 0,
            'deactivated_at' => Carbon::now(),
        ]);

        if (!$updateCategory) {
            return response()->json([

                'status' => 'error',
                'message' => 'Gagal menonaktifkan kategori',
            ], 400);
        }
    }
}
