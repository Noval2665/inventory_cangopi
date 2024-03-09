<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class purchase_report extends Model
{
    use HasFactory;
    protected $guarded = [];

    // Menggunakan metode exportToPdf
    $pdfFilePath = PurchaseReport::find($id)->exportToPdf();
    // Mengirim file PDF sebagai respons
    return response()->download($pdfFilePath);

    // Menggunakan metode exportToExcel
    $excelFilePath = PurchaseReport::find($id)->exportToExcel();
    // Mengirim file Excel sebagai respons
    return response()->download($excelFilePath);
}
