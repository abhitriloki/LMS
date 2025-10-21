<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExport implements FromArray, WithHeadings, WithTitle, WithStyles
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Return data as array
     */
    public function array(): array
    {
        return $this->data['data'] ?? [];
    }

    /**
     * Return headings
     */
    public function headings(): array
    {
        if (empty($this->data['data'])) {
            return [];
        }

        // Get keys from first row as headings
        $firstRow = $this->data['data'][0];
        return array_keys($firstRow);
    }

    /**
     * Return sheet title
     */
    public function title(): string
    {
        return $this->data['title'] ?? 'Report';
    }

    /**
     * Apply styles to worksheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
