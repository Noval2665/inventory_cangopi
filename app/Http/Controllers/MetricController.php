<?php

namespace App\Http\Controllers;

use App\Models\Metric;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MetricController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page;
        $per_page = $request->per_page ?? 10000;
        $search = $request->search;

        $metrics = Metric::when($search, function ($query, $search) {
            return $query->where('metric_name', 'LIKE', '%' . $search . '%');
        })
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data metrik',
            'metrics' => $metrics,
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
        DB::beginTransaction();

        $validator = Validator::make($request->all(), [
            'metric_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $metric = Metric::where('metric_name', $request->metric_name)->withTrashed()->first();

            if ($metric) {
                $updateMetric = Metric::where('id', $metric->id)->update([
                    'is_active' => 1,
                    'user_id' => auth()->user()->id,
                ]);

                if (!$updateMetric) {
                    throw new HttpException(400, 'Gagal mengubah data metrik');
                }
            } else {

                $createMetric = Metric::create([
                    'metric_name' => ucwords($request->metric_name),
                    'user_id' => auth()->user()->id,
                ]);

                if (!$createMetric) {
                    throw new HttpException(400, 'Gagal membuat data metrik');
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil membuat data metrik',
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
    public function update(Request $request, Metric $metric)
    {
        $validator = Validator::make($request->all(), [
            'metric_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $updateMetric = $metric->update([
            'metric_name' => ucwords($request->metric_name),
            'user_id' => auth()->user()->id,
        ]);

        if (!$updateMetric) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah data metrik',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengubah data metrik',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Metric $metric)
    {
        $user = auth()->user();

        if ($user->role->name != 'Admin') {
            $this->deactivate($metric->id);
        } else {
            if ($metric->products()->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak dapat menghapus data metrik produk yang memiliki produk terkait'
                ], 422);
            }
            if (!$metric->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data metrik',
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menghapus data metrik',
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'message' => $user->role->name != 'Admin' ? 'Berhasil menonaktifkan metrik' : 'Berhasil menghapus data metrik',
        ], 200);
    }

    public function deactivate($id)
    {
        $update = Metric::where('id', $id)->update([
            'is_active' => 0,
            'deactivated_at' => Carbon::now(),
        ]);

        if (!$update) {
            return response()->json([

                'status' => 'error',
                'message' => 'Gagal menonaktifkan metrik',
            ], 400);
        }
    }
}
