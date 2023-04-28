<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request,
    App\MaterialsClaim,
    Yajra\DataTables\Facades\DataTables,
    App\Utils\StrLib,
    Auth,
    App\Utils\HtmlHelper,
    App\MySoap,
    App\Customer,
    App\Passport,
    Log,
    Illuminate\Support\Facades\DB,
    App\Spylog\Spylog,
    App\ContractForm,
    mikehaertl\wkhtmlto\Pdf,
    Input,
    Carbon\Carbon;

class MaterialsClaimsController extends BasicController {

    public function __construct() {
        $this->table = 'materials_claims';
        $this->model = new MaterialsClaim();
    }

    public function getTableView(Request $req) {
        return parent::getTableView($req);
    }

    public function editItem(Request $req) {
//        $res = parent::editItem($req);
        $item = $this->getItemByID($req);
        if (is_null($item)) {
            $item = $this->model;
        }
        return view($this->table . '.edit')->with('item', $item);
    }

    public function getList(Request $req)
    {
        parent::getList($req);
        $cols = [
            'materials_claims.id as matclaim_id',
            'materials_claims.created_at as matclaim_created_at',
            'materials_claims.claim_date as matclaim_claim_date',
            'users.name as user_name',
            'subdivisions.name as subdivision_name'
        ];
        $items = MaterialsClaim::select($cols)
            ->leftJoin('users', 'users.id', '=', 'materials_claims.user_id')
            ->leftJoin('subdivisions', 'subdivisions.id', '=', 'materials_claims.subdivision_id');

        $collection = Datatables::of($items)
            ->editColumn('matclaim_created_at', function ($item) {
                return with(new Carbon($item->matclaim_created_at))->format('d.m.Y');
            })
            ->editColumn('matclaim_claim_date', function ($item) {
                return with(new Carbon($item->matclaim_claim_date))->format('d.m.Y');
            })
            ->addColumn('actions', function ($item) {
                $html = '<div class="btn-group">';
                $html .= HtmlHelper::Buttton(url('matclaims/edit?id=' . $item->matclaim_id),
                    ['size' => 'sm', 'glyph' => 'pencil']);
                if (Auth::user()->isAdmin()) {
                    $html .= HtmlHelper::Buttton(url('matclaims/remove?id=' . $item->matclaim_id),
                        ['size' => 'sm', 'glyph' => 'remove']);
                }
                $html .= '</div>';
                return $html;
            })
            ->filter(function ($query) use ($req) {
                if ($req->has('fio')) {
                    $query->where('users.name', 'like', "%" . $req->get('fio') . "%");
                }
                if (!Auth::user()->isAdmin()) {
                    $query->where('materials_claims.subdivision_id', Auth::user()->subdivision_id);
                }
            })
            ->rawColumns(['actions'])
            ->toJson();
        return $collection;
    }

    public function removeItem(Request $req) {
        return parent::removeItem($req);
    }

    public function updateItem(Request $req) {
        parent::updateItem($req);
        $matclaim = MaterialsClaim::findOrNew($req->get('id', NULL));
        $matclaim->fill($req->all());
        $matclaimData = json_decode($matclaim->data,true);
        if(is_array($matclaimData) && count($matclaimData)==1 && $matclaimData[0]['amount']==0){
            $matclaim->status = 1;
        }
        if (Auth::user()->isAdmin()) {
            $matclaim->user_id = (is_null($matclaim->user_id)) ? Auth::user()->id : $matclaim->user_id;
            $matclaim->subdivision_id = (is_null($matclaim->subdivision_id)) ? Auth::user()->subdivision_id : $matclaim->subdivision_id;
        } else {
            $matclaim->user_id = Auth::user()->id;
            $matclaim->subdivision_id = Auth::user()->subdivision_id;
        }
        $res1c = MySoap::saveMaterialsClaim([
                    'created_at' => (is_null($matclaim->created_at)) ? Carbon::now()->format('YmdHis') : $matclaim->created_at->format('YmdHis'),
                    'claim_date' => with(new Carbon($matclaim->claim_date))->format('YmdHis'),
                    'user_id_1c' => (Auth::user()->isAdmin() && !is_null($matclaim->user_id)) ? $matclaim->user->id_1c : Auth::user()->id_1c,
                    'subdivision_id_1c' => (Auth::user()->isAdmin() && !is_null($matclaim->subdivision_id)) ? $matclaim->subdivision->name_id : Auth::user()->subdivision->name_id,
                    'data' => $this->JSONtoXML($matclaimData),
                    'sfp_old' => $matclaim->sfp_old,
                    'sfp_new' => $matclaim->sfp_new,
                    'sfp_claim' => $matclaim->sfp_claim,
                    'id_1c' => $matclaim->id_1c,
                    'status' => $matclaim->status
        ]);
        if($res1c['res']){
            $matclaim->id_1c = $res1c['id_1c'];
        } else {
            return redirect()->back()->with('msg_err', (array_key_exists('msg_err', $res1c))?$res1c['msg_err']:StrLib::ERR_1C);
        }
        if (!$matclaim->save()) {
            return redirect()->back()->with('msg_err', StrLib::ERR);
        }
        return redirect('matclaims')->with('msg_suc', StrLib::SUC_SAVED);
    }

    public function JSONtoXML($json) {
        $xml = new \SimpleXMLElement('<root/>');
        foreach($json as $item){
            $xml_item = $xml->addChild('item');
            $xml_item->addAttribute('material',$item["material"]);
            $xml_item->addAttribute('amount',$item["amount"]);
        }
        return $xml->asXML();
    }

}
