<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function productsPdf()
    {
        // Requirement 3b: Implementasi query SQL (Raw SQL example)
        $products = DB::select("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id
        ");
        
        // Convert to objects if needed for the view
        $products = collect($products);

        $data = [
            'title' => 'Inventory Product Report',
            'date' => date('d/m/Y'),
            'products' => $products
        ];

        $pdf = Pdf::loadView('reports.products', $data);
        
        return $pdf->download('products-report.pdf');
    }
}
