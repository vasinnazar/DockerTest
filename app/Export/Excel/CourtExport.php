<?php

namespace App\Export\Excel;

use App\Passport;
use App\StrUtils;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CourtExport implements FromCollection, WithHeadings
{
    private $rows;

    public function __construct($rows)
    {
        $this->rows = $rows;
    }

    public function headings(): array
    {
        return [
            'FILE_NAME',
            'ADDRESSLINE_TO',
            'RECIPIENT_TYPE',
            'RECIPIENT',
            'INN',
            'KPP',
            'LETTER_REG_NUMBER',
            'LETTER_TITLE',
            'MAILCATEGORY',
            'ADDRESSLINE_RETURN',
            'WOMAILRANK',
            'ADDITIONAL_INFO',
            'LETTER_COMMENT'
        ];
    }

    public function collection()
    {

        $params = collect();

        foreach ($this->rows as $row) {

            $item = collect([
                $row['address'],
                '0',
                $row['fio'],
                '',
                '' ,
                $row['debtor_id' ],
                '',
                '0',
                $row['address_company'],
                '',
                '',
                '',
            ]);
            $params->push($item);
        }
        return $params;
    }
}
