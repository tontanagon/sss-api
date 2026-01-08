<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ProductExportSheet implements FromView
{

    protected $body;

    public function __construct($body)
    {
        $this->body = $body;
    }

    public function view(): View
    {
        return view('export.ExportProductExcel', [
            'products' => $this->body,
        ]);
    }
}
