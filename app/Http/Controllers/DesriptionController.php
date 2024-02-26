<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Carbon\Carbon;
use App\Models\Description;
use Symfony\Component\Console\Descriptor\Descriptor;

class DesriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $descriptions = Description::all();
        return response()->json(['descriptions' => $descriptions]);
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

        DB::startTransaction();
        try {
            $description = Description::create([
                'description_type' => $request->description_type,
            ]);

            if (!$description) {
                throw new HttpException(400, 'Deskripsi gagal ditambahkan');
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Deskripsi berhasil ditambahkan',
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
    public function show(Description $description)
    {
        return response()->json(['description' => $description]);
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

        if($validator->fails()){
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $updateDescription = $description->update([
            'description_type' => $request->description_type,
        ]);

        if (!$updateDescription){
            return response()->json([
                'status' => 'error',
                'message' => 'Deskripsi gagal diperbarui',
            ], 400);
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Deskripsi berhasil diperbarui',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $description = Description::findOrFail($id);
        $description->delete();

        return response()->json(['message' => 'Deskripsi berhasil dihapus']);
    }
}
