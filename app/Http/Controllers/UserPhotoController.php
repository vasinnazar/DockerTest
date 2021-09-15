<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Auth;
use Input;
use yajra\Datatables\Datatables;
use App\UserPhoto;
use Carbon\Carbon;

class UserPhotoController extends Controller {
    public function __construct() {
        $this->middleware('auth');
    }
    /**
     * страница фотографий спецов
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $req) {
        $data = [
            'directorsList' => \App\Region::distinct()->lists('director')->toArray()
        ];
        array_unshift($data['directorsList'], '');
        return view('reports.userphotos.index', $data);
    }
    /**
     * Запрос для списка в таблице фоток
     * @param Request $req
     * @return type
     */
    public function ajaxList(Request $req) {
        $cols = [
            'user_photos.created_at as up_created_at',
            'user_photos.id as up_id',
            'users.name as user_name',
            'subdivisions.name as subdiv_name',
            'user_photos.path as up_path',
        ];
        $items = UserPhoto::select($cols)
                ->leftJoin('subdivisions', 'subdivisions.id', '=', 'user_photos.subdivision_id')
                ->leftJoin('cities', 'subdivisions.city_id', '=', 'cities.id')
                ->leftJoin('regions', 'cities.region_id', '=', 'regions.id')
                ->leftJoin('users', 'users.id', '=', 'user_photos.user_id');
        if (!Auth::user()->isAdmin()) {
            $items->where('regions.director', Auth::user()->name);
        }
        $items = $this->ajaxListSearch($items, $req);
        return Datatables::of($items)
                        ->editColumn('up_created_at', function($item) {
                            return with(new Carbon($item->up_created_at))->format('d.m.Y H:i:s');
                        })
                        ->editColumn('up_path', function($item) {
                            return '<a href="'.url($item->up_path).'" target="_blank"><img src="' . url($item->up_path) . '" width="200px" /></a>';
                        })
                        ->addColumn('actions', function($item) {
                            $html = '<div class="btn-group">';
                            $html .= '</div>';
                            return $html;
                        })
                        ->removeColumn('up_id')
                        ->make();
    }
    function ajaxListSearch($items,$req){
        /**
         * параметры поиска
         */
        if ($req->has('user_id') && !empty($req->get('user_id'))) {
            $items->where('users.id', '=', $req->get('user_id'));
        }
        if ($req->has('subdivision_id') && !empty($req->get('subdivision_id'))) {
            $items->where('subdivisions.id', '=', $req->get('subdivision_id'));
        }
        if ($req->has('date_min') && !empty($req->get('date_min'))) {
            $items->where('user_photos.created_at', '>=', $req->get('date_min'));
        } else {
            $items->where('user_photos.created_at', '>=', Carbon::today());
        }
        if ($req->has('date_max') && !empty($req->get('date_max'))) {
            $items->where('user_photos.created_at', '<=', with(new Carbon($req->get('date_max')))->setTime(23, 59, 59)->format('Y-m-d H:i:s'));
        }
        if ($req->has('director') && !empty($req->get('director'))) {
            $items->where('subdivisions.director', '=', $req->get('director'));
        }
        /**
         * =====================
         */
        return $items;
    }
    public function ajaxBasicList(Request $req){
        $cols = [
            'user_photos.created_at as up_created_at',
            'user_photos.id as up_id',
            'users.name as user_name',
            'subdivisions.name as subdiv_name',
            'user_photos.path as up_path',
        ];
        $items = UserPhoto::select($cols)
                ->leftJoin('subdivisions', 'subdivisions.id', '=', 'user_photos.subdivision_id')
                ->leftJoin('cities', 'subdivisions.city_id', '=', 'cities.id')
                ->leftJoin('regions', 'cities.region_id', '=', 'regions.id')
                ->leftJoin('users', 'users.id', '=', 'user_photos.user_id')
                ->orderBy('user_photos.created_at','desc');
        if (!Auth::user()->isAdmin()) {
            $items->where('regions.director', Auth::user()->name);
        }
        $items = $this->ajaxListSearch($items, $req);
        return $items->get();
    }
    /**
     * Загрузка фото специалиста
     * @param Request $req
     * @return type
     */
    public function userPhotoUpload(Request $req) {
        if (!is_null(\App\UserPhoto::upload(Input::file('webcam'), Auth::user()))) {
            return response('', 200);
        } else {
            return response('', 500);
        }
    }

}
