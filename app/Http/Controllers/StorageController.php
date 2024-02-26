<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Carbon\Carbon;
use App\Models\Storage;
use Symfony\Component\Console\Descriptor\Descriptor;

class StorageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $storages = Storage::all();
        return response()->json(['storages' => $storages]);
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
            'storage_type' => 'required|string',
        ]);

        if ($validator->fails()){
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        DB::startTransaction();
        try{
            $storage = Storage::create([
                'storage_type' => $request->storage_type,
            ]);

            if(!$storage){
                throw new HttpException(400, 'Storage gagal ditambahkan');
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Storage berhasil ditambahkan',
            ], 201);
        }

        catch (HttpException $e){
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
    public function show(Storage $storage)
    {
        return response()->json(['storage' => $storage]);
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
    public function update(Request $request, Storage $storage)
    {
        $validator = Validator::make($request->all(), [
            'storage_type' => 'required|string',
        ]);

        if ($validator->fails()){
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $updateStorage = $storage->update([
            'storage_type' => $request->storage_type,
        ]);

        if (!$updateStorage){
            return response()->json([
                'status' => 'error',
                'message' => 'Storage gagal diperbarui',
            ], 400);
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Storage berhasil diperbarui',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Storage $storage)
    {
        $storage->delete();

        return response()->json(['message' => 'Storage berhasil dihapus']);
    }
}
