<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $dates = ['deactivated_at'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function generatePdf($id)
    {
        $pdfFilePath = $this->find($id)->exportToPdf();
        return response()->download($pdfFilePath);
    }

    public function generateExcel($id)
    {
        $excelFilePath = $this->find($id)->exportToExcel();
        return response()->download($excelFilePath);
    }
}

?>