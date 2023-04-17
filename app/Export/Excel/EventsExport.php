<?php

namespace App\Export\Excel;

use App\DebtorsEventType;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EventsExport implements FromCollection, WithHeadings
{

    private $events;

    public function __construct($events)
    {
        $this->events = $events;
    }

    public function headings(): array
    {
        return [
            'Дата план',
            'Тип мероприятия',
            'ФИО должника',
            'Дата факт',
            'Ответственный',
        ];
    }

    public function collection()
    {
        $collectEvents = collect();
        foreach ($this->events as $event) {
            $item = collect([
                Carbon::parse($event->de_date)->format('d.m.Y'),
                (DebtorsEventType::where('id', $event->de_type_id)->first())->name,
                $event->passports_fio,
                Carbon::parse($event->de_created_at)->format('d.m.Y'),
                $event->de_username,
            ]);
            $collectEvents->push($item);
        }
        return $collectEvents;
    }
}
