<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request,
    Yajra\DataTables\Facades\DataTables,
    App\Utils\StrLib,
    Auth,
    App\Utils\HtmlHelper,
    Log,
    Illuminate\Support\Facades\DB,
    App\Spylog\Spylog,
    Input,
    App\Message,
    Carbon\Carbon;

class MessagesController extends BasicController {

    public function __construct() {
        $this->table = 'messages';
        $this->model = new Message();
        $this->useDatatables = false;
    }

    public function getTableView(Request $req) {
        $cols = [
            'messages.id as msg_id', 'messages.created_at as msg_created',
            'messages.text as msg_text', 'users.name as username'
        ];
        $items = Message::select($cols)
                ->leftJoin('users', 'users.id', '=', 'messages.user_id')
                ->orderBy('messages.created_at', 'desc')
                ->paginate(20);
        return view('messages.table')->with('items', $items);
    }

    public function getList(Request $req)
    {
        parent::getList($req);
        $cols = [
            'messages.id as msg_id',
            'messages.created_at as msg_created',
            'messages.text as msg_text',
            'users.name as username'
        ];
        $items = Message::select($cols)
            ->leftJoin('users', 'users.id', '=', 'messages.user_id');
        $collection = Datatables::of($items)
            ->editColumn('msg_created', function ($item) {
                return with(new Carbon($item->msg_created))->format('d.m.Y');
            })
            ->addColumn('actions', function ($item) {
                $html = '<div class="btn-group">';
                if (Auth::user()->isAdmin()) {
                    $html .= HtmlHelper::Buttton(url('messages/edit?id=' . $item->msg_id),
                        ['size' => 'sm', 'glyph' => 'pencil']);
                    $html .= HtmlHelper::Buttton(url('messages/remove?id=' . $item->msg_id),
                        ['size' => 'sm', 'glyph' => 'remove']);
                }
                $html .= '</div>';
                return $html;
            })
            ->filter(function ($query) use ($req) {
                if ($req->has('fio')) {
                    $query->where('fio', 'like', "%" . $req->get('fio') . "%");
                }
            })
            ->setTotalRecords(1000)
            ->make();
        return $collection;
    }

//    public function removeItem(Request $req) {
//        return parent::removeItem($req);
//    }
//
    public function updateItem(Request $req) {
        parent::updateItem($req);
        $msg = Message::findOrNew($req->get('id'));
        $msg->fill($req->all());
        if(is_null($msg->caption) || $msg->caption==''){
            $msg->caption = 'Офис';
        }
        if (is_null($msg->user_id)) {
            $msg->user_id = Auth::user()->id;
        }
        if (!$msg->save()) {
            return $this->backWithErr();
        }
        return redirect('messages')->with('msg_suc',  StrLib::SUC_SAVED);
    }

}
