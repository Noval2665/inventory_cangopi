<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Exception;

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

        $recipes = Recipe::with(['finishedProduct'])->when($search, function ($query, $search) {
            return $query->whereHas('finishedProduct', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->orWhere('product_code', 'LIKE', '%' . $search . '%')
                        ->where('product_name', 'LIKE', '%' . $search . '%');
                })
                    ->where('product_type', 'finished');
            });
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
            'finished_product_id' => 'required|numeric|exists:products,id',
            'recipes' => 'required|array',
            'recipes.*.product_id' => 'required|numeric|exists:products,id',
            'recipes.*.measurement' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        DB::beginTransaction();

        try {

            $createRecipe = Recipe::create([
                'finished_product_id' => $request->finished_product_id,
                'user_id' => auth()->user()->id,
            ]);

            if (!$createRecipe) {
                throw new HttpException(400, 'Gagal membuat data resep');
            }

            foreach ($request->recipes as $recipe) {
                $createRecipeDetail = $createRecipe->details()->create([
                    'product_id' => $recipe['product_id'],
                    'measurement' => $recipe['measurement'],
                ]);

                if (!$createRecipeDetail) {
                    throw new HttpException(400, 'Gagal membuat data detail resep');
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil membuat data resep',
            ], 201);
        } catch (HttpException $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
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
            'finished_product_id' => 'required|numeric|exists:products,id',
            'recipes' => 'required|array',
            'recipes.*.product_id' => 'required|numeric|exists:products,id',
            'recipes.*.measurement' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $updateRecipe = $recipe->update([
                'selling_price' => $request->selling_price,
                'portions' => $request->portions,
                'measurement' => $request->measurement,
                'finished_product_id' => $request->finished_product_id,
                'par_stock_id' => $request->par_stock_id,
                'user_id' => auth()->user()->id,
            ]);

            if (!$updateRecipe) {
                throw new HttpException(400, 'Gagal mengupdate data resep');
            }

            $tempRecipeDetails = $recipe->details()->get();

            if (!$tempRecipeDetails) {
                throw new HttpException(400, 'Gagal mengambil data detail resep');
            }

            if (!$recipe->details()->forceDelete()) {
                throw new HttpException(400, 'Gagal menghapus data detail resep');
            }

            foreach ($request->recipes as $recipe) {
                $createRecipeDetail = $recipe->details()->create([
                    'product_id' => $recipe['product_id'],
                    'measurement' => $recipe['measurement'],
                ]);

                if (!$createRecipeDetail) {
                    throw new HttpException(400, 'Gagal membuat data detail resep');
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil mengupdate data resep',
            ], 200);
        } catch (HttpException $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        }
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

            // if (!$recipe->finishedProduct->delete()) {
            //     return response()->json([
            //         'status' => 'error',
            //         'message' => 'Gagal menghapus data produk',
            //     ], 400);
            // }

            // if (!$recipe->parStock->delete()) {
            //     return response()->json([
            //         'status' => 'error',
            //         'message' => 'Gagal menghapus data stok',
            //     ], 400);
            // }

            // if (!$recipe->finishedProduct->delete()) {
            //     return response()->json([
            //         'status' => 'error',
            //         'message' => 'Gagal menghapus data produk',
            //     ], 400);
            // }

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
