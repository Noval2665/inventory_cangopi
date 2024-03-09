<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class purchase_report extends Model
{
    use HasFactory;
    protected $guarded = [];

<<<<<<< HEAD
    // Menggunakan metode exportToPdf
    $pdfFilePath = PurchaseReport::find($id)->exportToPdf();
    // Mengirim file PDF sebagai respons
    return response()->download($pdfFilePath);

    // Menggunakan metode exportToExcel
    $excelFilePath = PurchaseReport::find($id)->exportToExcel();
    // Mengirim file Excel sebagai respons
    return response()->download($excelFilePath);
=======
    protected $dates = ['deactivated_at'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
>>>>>>> f8439e0f7a137292a5dacc4857f45907b7572086
}
