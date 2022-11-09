<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\AddressDouble;
use Illuminate\Support\Facades\DB;
use Yajra\Datatables\Facades\Datatables;

class AddressDoublesController extends BasicController
{
    /**
     * Открывает страницу списка дублей адресов
     * @return type
     */
    public function index()
    {
        return view('debtors.addressdoubles',[
            'addressdoublesSearchFields'=>  AddressDouble::getSearchFields()
        ]);
    }
    /**
     * Отдает список дублей адресов в таблицу аяксом
     * @param Request $req
     * @return type
     */
    public function ajaxList(Request $req)
    {
        $items = AddressDouble::whereNotNull('id');
        $this->addSearchConditionsToQuery($items, $req->input());
        \PC::debug($items->toSql());
        $collection = Datatables::of($items)
            ->removeColumn('id')
            ->removeColumn('created_at')
            ->removeColumn('updated_at')
            ->editColumn('is_debtor', function ($item) {
                return ($item->is_debtor) ? 'Да' : 'Нет';
            })
            ->addColumn('action', function ($item) {
                return '';
            })
            ->setTotalRecords(1000)
            ->make();
        return $collection;
    }

}
