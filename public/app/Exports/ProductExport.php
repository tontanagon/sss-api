<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProductExport implements WithMultipleSheets
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        $sheets = [];

        // $users = $this->data['users'];
        // $answers = $this->data['answers'];

        // foreach ($this->data['map_answers'] as $map_answer) {
        //     if (!empty($map_answer)) {
        //         $sheets[] = new ProductExportSheet($map_answer, $users, $answers);
        //     }
        // }

        return $sheets;
    }
}
