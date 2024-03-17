<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RecipeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page;
        $per_page = $request->per_page ?? 10000;
        $search = $request->search;

        $recipes = Recipe::when($search, function ($query, $search) {
            return $query->where('finished_product_name', 'LIKE', '%' . $search . '%');
        })
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data resep',
            'recipes' => $recipes,
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
            'selling_price' => 'required|numeric',
            'portions' => 'required|string',
            'measurement' => 'required|numeric',

            'finished_product_id' => 'required|numeric|exists:finished_products,id',
            'par_stock_id =>' => 'required|numeric|exists:par_stocks,id',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $createRecipe = Recipe::create([
            'selling_price' => $request->selling_price,
            'portions' => $request->portions,
            'measurement' => $request->measurement,
            'finished_product_id' => $request->finished_product_id,
            'par_stock_id' => $request->par_stock_id,
            'user_id' => auth()->user()->id,
        ]);

        if (!$createRecipe) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat data resep',
            ], 400);
        }
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
    public function update(Request $request, Recipe $recipe)
    {
        $validator = Validator::make($request->all(), [
            'selling_price' => 'required|numeric',
            'portions' => 'required|string',
            'measurement' => 'required|numeric',

            'finished_product_id' => 'required|numeric|exists:finished_products,id',
            'par_stock_id' => 'required|numeric|exists:par_stocks,id',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $updateRecipe = $recipe->update([
            'selling_price' => $request->selling_price,
            'portions' => $request->portions,
            'measurement' => $request->measurement,
            'finished_product_id' => $request->finished_product_id,
            'par_stock_id' => $request->par_stock_id,
            'user_id' => auth()->user()->id,
        ]);

        if (!$updateRecipe) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengupdate data resep',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengupdate data resep',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Recipe $recipe)
    {
        $user = auth()->user();

        if ($user->role->name != 'Admin') {
            $this->deactivate($recipe->id);
        } else {

            if (!$recipe->finishedProduct->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data produk',
                ], 400);
            }

            if (!$recipe->parStock->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data stok',
                ], 400);
            }

            if (!$recipe->finishedProduct->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data produk',
                ], 400);
            }

            if (!$recipe->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data resep',
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menghapus data resep',
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'message' => $user->role->name != 'Admin' ? 'Berhasil menonaktifkan resep' : 'Berhasil menghapus data resep',
        ], 200);
    }

    public function deactivate($id)
    {
        $updateRecipe = Recipe::where('id', $id)->update([
            'is_active' => 0,
            'deactivated_at' => Carbon::now(),
        ]);

        if (!$updateRecipe) {
            return response()->json([

                'status' => 'error',
                'message' => 'Gagal menonaktifkan resep',
            ], 400);
        }
    }
}
