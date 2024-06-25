<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Carbon\Carbon;
use App\Models\Description;
use Symfony\Component\Console\Descriptor\Descriptor;

class DescriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page;
        $per_page = $request->per_page ?? 10000;
        $search = $request->search;

        $descriptions = Description::when($search, function ($query, $search) {
            return $query->where('description_name', 'LIKE', '%' . $search . '%');
        })
            ->paginate($per_page, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'message' => 'Menampilkan data deskripsi',
            'descriptions' => $descriptions,
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
            'description_type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }


        $createDescription = Description::create([
            'description_type' => ucwords($request->description_type),
            'user_id' => auth()->user()->id,
        ]);

        if (!$createDescription) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat data deskripsi',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil membuat data deskripsi',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Description $description)
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
    public function update(Request $request, Description $description)
    {
        $validator = Validator::make($request->all(), [
            'description_type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $updateDescription = $description->update([
            'description_type' => ucwords($request->description_type),
            'user_id' => auth()->user()->id,
        ]);

        if (!$updateDescription) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui data deskripsi',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil memperbarui data deskripsi',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Description $description)
    {
        $user = auth()->user();

        if ($user->role->name != 'Admin') {
            $this->deactivate($description->id);
        } else {
            if (!$description->delete()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus data deskripsi',
                ], 400);
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menghapus data deskripsi',
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'message' => $user->role->name == 'Admin' ? 'Berhasil menonaktifkan data deskripsi' : 'Berhasil menghapus data deskripsi',
        ]);
    }

    public function deactivate($id)
    {
        $updateDescription = Description::where('id', $id)->update([
            'is_active' => 0,
            'deactivated_at' => Carbon::now(),
        ]);

        if (!$updateDescription) {
            return response()->json([

                'status' => 'error',
                'message' => 'Gagal menonaktifkan deskripsi',
            ], 400);
        }
    }
}
