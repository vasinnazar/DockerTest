<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Rnko;
use Auth;
use XMLReader;
use App\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Image;
use App\Utils\HelperUtil;
use Exception;

//use Log;

class RnkoController extends BasicController {

    public $it = [506, 32, 517, 1];
    public $ovk = [189, 264, 497, 736, 181, 348, 739];
    public $cc = [34, 33, 39, 38, 40];
    public $seb = [223, 487];

    public function __construct() {
        
    }

    /**
     *
     * @var \App\Utils\EmailReader
     */
    public $emailReader;

    public function index() {
        if (in_array(Auth::user()->id, [1, 5, 189, 517])) {
            return redirect('reports/rnko/admin');
        }
//        if(Auth::user()->subdivision_id!=113){
//            return view('reports/rnko')
//                        ->with('rnko_left', '')
//                        ->with('photos', [])
//                        ->with('details', null)
//                        ->with('rnko', null);
//        }
//        if (!Auth::user()->isAdmin()) {
        $data = $this->getNextRnko();
//        } else {
//            $data = [
//                'rnko_left' => '',
//                'rnko' => null,
//                'photos' => []
//            ];
//        }
        $details = null;
        if (Auth::user()->isAdmin()) {
            $details = [
                'count' => Rnko::count(),
                'total' => Rnko::where('status', '>', 0)->count(),
                'checked' => Rnko::where('status', Rnko::STATUS_CHECKED)->count(),
                'edited' => Rnko::where('status', Rnko::STATUS_CHANGED)->count(),
                'nophoto' => Rnko::where('status', Rnko::STATUS_NOTFOUND)->count(),
                'autonophoto' => Rnko::where('comment', "AUTO")->count(),
                'uploaded' => Rnko::where('info', 5)->where('status', Rnko::STATUS_NEW)->whereNull('check_user_id')->count(),
            ];
        }
        return view('reports/rnko')
                        ->with('rnko_left', $data['rnko_left'])
                        ->with('photos', $data['photos'])
                        ->with('details', $details)
                        ->with('rnko', $data['rnko']);
    }

    public function admin() {
//        return $this->removeAllDuplicates();
//        return $this->getAllChecked();
//        return $this->getAllWithPhotos();
//        return $this->getEditStat();
//        return $this->refreshChecked();
        $data = [
            'rnko_left' => '',
            'rnko' => null,
            'photos' => []
        ];
        $today = Carbon::now()->format('Y-m-d');
        $yesterday = Carbon::now()->setTime(0, 0, 0)->subDay()->format('Y-m-d');
//        $stat = DB::select('select count(*) as asd,users.name as fio,users.id as uid from rnko left join users on users.id=rnko.check_user_id where end_check is not null group by users.id order by asd desc');
        $stat = DB::table('rnko')->select(DB::raw('count(*) as asd, users.name as fio, users.id as uid'))->leftJoin('users', 'users.id', '=', 'rnko.check_user_id')->groupBy('uid')->orderBy('asd', 'desc')->whereNotIn('users.id', $this->it)->get();
//        $today_stat = DB::select('select count(*) as asd,users.name as fio,users.id as uid from rnko left join users on users.id=rnko.check_user_id where end_check >' . $today . ' group by users.id order by asd desc');
        $today_stat = DB::table('rnko')->select(DB::raw('count(*) as asd, users.name as fio, users.id as uid'))->leftJoin('users', 'users.id', '=', 'rnko.check_user_id')->where('start_check', '>', $today)->groupBy('uid')->orderBy('asd', 'desc')->whereNotIn('users.id', $this->it)->get();
//        $yesterday_stat = DB::select('select count(*) as asd,users.name as fio,users.id as uid from rnko left join users on users.id=rnko.check_user_id where end_check >' . $yesterday . ' and end_check <' . $today . ' group by users.id order by asd desc');
        $yesterday_stat = DB::table('rnko')->select(DB::raw('count(*) as asd, users.name as fio, users.id as uid'))->leftJoin('users', 'users.id', '=', 'rnko.check_user_id')->where('start_check', '>', $yesterday)->where('end_check', '<', $today)->groupBy('uid')->orderBy('asd', 'desc')->whereNotIn('users.id', $this->it)->get();
        \PC::debug($stat, 'all');
        \PC::debug($today_stat, $today);
        \PC::debug($yesterday_stat, $yesterday);
        $statlist = [];
        $total_stat_today = 0;
        $total_stat_yesterday = 0;
        foreach ($stat as $s) {
            if ($s->fio == null) {
                continue;
            }
            $stat_item = ['all' => $s->asd, 'fio' => $s->fio, 'yesterday' => '', 'today' => ''];
            foreach ($today_stat as $ts) {
                if ($s->uid == $ts->uid) {
                    $stat_item['today'] = $ts->asd;
                    break;
                }
            }
            foreach ($yesterday_stat as $ys) {
                if ($s->uid == $ys->uid) {
                    $stat_item['yesterday'] = $ys->asd;
                    $total_stat_yesterday++;
                    break;
                }
            }
            $statlist[] = $stat_item;
        }
//        SELECT count(*) as IT FROM armf.rnko where end_check>'2016-10-03' and check_user_id in(506,32,517)
//        SELECT count(*) as OVK FROM armf.rnko where end_check>'2016-10-03' and check_user_id in(189,264,497,736,181,348)
//        SELECT count(*) as CC FROM armf.rnko where end_check>'2016-10-03' and check_user_id in(34,33,39)
//        SELECT count(*) as SALES FROM armf.rnko where (end_check>'2016-10-03' and check_user_id in(132,346,229,198,503,26,300,502,326,27)) or (user_id=25 and updated_at>'2016-10-03')
        $depstat = [
            'ovk' => Rnko::where('start_check', '>', Carbon::now()->format('Y-m-d'))->whereIn('check_user_id', $this->ovk)->count(),
            'sales' => Rnko::where('start_check', '>', Carbon::now()->format('Y-m-d'))->whereNotIn('check_user_id', array_merge($this->ovk, $this->cc, $this->it, $this->seb))->count()
        ];
        $details = [
            'count' => Rnko::count(),
            'total' => Rnko::whereNotNull('end_check')->count()+212,
            'checked' => Rnko::where('check_status', Rnko::STATUS_CHECKED)->count(),
            'edited' => Rnko::where('check_status', Rnko::STATUS_CHANGED)->count(),
            'nophoto' => Rnko::where('check_status', Rnko::STATUS_NOTFOUND)->count(),
            'autonophoto' => Rnko::where('comment', "AUTO")->count(),
            'uploaded' => Rnko::whereIn('info', [1, 5, 6])->whereNull('check_user_id')->count(),
            'stat' => $statlist,
            'depstat' => $depstat
//            'total_stat_today'=>
        ];
        return view('reports/rnko')
                        ->with('rnko_left', $data['rnko_left'])
                        ->with('photos', $data['photos'])
                        ->with('details', $details)
                        ->with('rnko', $data['rnko']);
    }

    public function getRnkoByCardNumber(Request $req) {
        $data = ['photos' => [], 'rnko' => null, 'rnko_left' => '', 'photos_warning' => '', 'details' => null];
        if ($req->has('card_number')) {
            $rnko = Rnko::where('card_number', $req->card_number)->first();
            if (is_null($rnko)) {
                return redirect('reports/rnko/number')->with('msg_err', 'Карта не найдена');
            }
//            $data['photos'] = $this->getPhotos($rnko);
            if (count($data['photos']) == 0) {
//                $data['photos'] = $this->getPhotos($rnko, false);
                if (count($data['photos']) > 0) {
                    $data['photos_warning'] = 'Внимание, фотографии взяты из других заявок';
                }
            }
            $data['rnko'] = $rnko;

            $passport = \App\Passport::where('series', $rnko->passport_series)->where('number', $rnko->passport_number)->first();
            if (!is_null($passport) && !is_null($passport->customer)) {
                $data['telephone'] = $passport->customer->telephone;
            } else {
                $res1c = \App\MySoap::passport(['series' => $rnko->passport_series, 'number' => $rnko->passport_number, 'old_series' => '', 'old_number' => '']);
                if (is_array($res1c) && array_key_exists('telephone', $res1c)) {
                    $data['telephone'] = \App\StrUtils::removeNonDigits($res1c['telephone']);
                }
            }

            if (is_null($rnko->start_check) || $rnko->start_check == '0000-00-00 00:00:00') {
                $rnko->start_check = Carbon::now()->format('Y-m-d H:i:s');
            }
            if (is_null($rnko->check_user_id)) {
                $rnko->check_user_id = Auth::user()->id;
            }
            $rnko->save();
        }
        return view('reports/rnko_number', $data);
//                        ->with('rnko_left', $data['rnko_left'])
//                        ->with('photos', $data['photos'])
//                        ->with('details', null)
//                        ->with('rnko', $data['rnko']);
    }

    public function getUncheckedRnko(Request $req) {
//        $rnko = Rnko::whereNull('check_user_id')->where('info', 1)->first();
        $rnko = Rnko::whereRaw('(check_user_id is null  or (check_user_id = ' . Auth::user()->id . ' and end_check is null)) and check_status=0 and info<>7')->first();
        if (is_null($rnko)) {
            return redirect('reports/rnko/number')->with('msg_err', 'Нет карты');
        } else {
            if (is_null($rnko->check_user_id)) {
                $rnko->check_user_id = Auth::user()->id;
                $rnko->save();
            }
            return redirect('reports/rnko/number?card_number=' . $rnko->card_number . '&check=1');
        }
    }

    public function skipRnko(Request $req) {
        $rnko = Rnko::whereRaw('(check_user_id is null  or (check_user_id = ' . Auth::user()->id . ' and end_check is null)) and check_status=0 and info<>7')->first();
        if (is_null($rnko)) {
            return redirect('reports/rnko/number')->with('msg_err', 'Нет карты');
        }
        if (is_null($rnko->check_user_id)) {
            $rnko->check_user_id = Auth::user()->id;
        }
        $rnko->info = 7;
        if (is_null($rnko->start_check)) {
            $rnko->start_check = Carbon::now()->format('Y-m-d H:i:s');
        }
        $rnko->end_check = Carbon::now()->format('Y-m-d H:i:s');
        $rnko->save();
        return redirect('reports/rnko/number')->with('msg_suc', 'Карта отложена.');
    }

    public function getNextRnko() {
//        if(is_null(Auth::user()))
//        $rnko_left = Rnko::where('status', Rnko::STATUS_NEW)->where('user_id', Auth::user()->id)->count();
        $rnko_left = '';
        $rnko = Rnko::whereRaw('status = ' . Rnko::STATUS_NEW . ' and (user_id is NULL or user_id=' . Auth::user()->id . ') and prev_user_id <> ' . Auth::user()->id . ' and (prev_user_id is not NULL or info=5 or info=6) and start_check is NULL')->first();
//        $last_rnko = Rnko::where('status', Rnko::STATUS_NEW)->where('user_id', Auth::user()->id)->orderBy('id', 'desc')->first();
        if (is_null($rnko)) {
            return [
                'rnko_left' => $rnko_left,
                'rnko' => $rnko,
                'photos' => []
            ];
        }
        if (is_null($rnko->user_id)) {
            $rnko->user_id = Auth::user()->id;
            $rnko->subdivision_id = Auth::user()->subdivision_id;
        }
        $rnko->save();
        return [
            'rnko_left' => $rnko_left,
            'rnko' => $rnko,
            'photos' => $this->getPhotos($rnko)
        ];
    }

    public function getPhotos(Rnko $rnko, $with_claim_date = true) {
        if ($rnko->info == 5) {
            return $this->getPhotos222($rnko, $with_claim_date);
        }
        if ($with_claim_date) {
            $folderName = 'images/' . $rnko->passport_series . $rnko->passport_number . '/' . $rnko->claim_date->format('Y-m-d');
        } else {
            $folderName = 'images/' . $rnko->passport_series . $rnko->passport_number;
        }
        $photos = [];
        if (HelperUtil::FtpFolderExists($folderName)) {
            if ($with_claim_date) {
                $photos = Storage::files($folderName);
            } else {
                $dirs = HelperUtil::FtpFolderList($folderName);
                foreach ($dirs as $dir) {
                    $d = str_replace('/armff.ru/local/storage/app/', '', $dir);
                    $photos = array_merge($photos, Storage::files($d));
                }
            }
        }
//        if (count($photos) == 0) {
//            $rnko->status = Rnko::STATUS_NOTFOUND;
//            $rnko->save();
//            return $this->getNextRnko();
//        }
        $photo_res = [];
        $i = 0;
        foreach ($photos as $p) {
            if ($i > 10) {
                break;
            }
            if (stripos($p, '.jpg') !== FALSE || stripos($p, '.png') !== FALSE || stripos($p, '.jpeg') !== FALSE) {
                if (HelperUtil::FtpFileExists('/' . $p)) {
                    try {
                        $file = Storage::get($p);
                    } catch (Exception $ex) {
                        $file = null;
                    }
//                    if(is_null($file)){
//                        $file = @HelperUtil::FtpGetFile($p);
//                        if(!$file){
//                            $file = null;
//                        }
//                    }
//                    \PC::debug($p);
                    if ($file != null && Storage::size($p) > 15000) {
                        Log::info('RnkoController.resizePhoto', ['photo' => $p, 'user' => Auth::user()->id]);
                        try {
                            $img = Image::make($file);
                            if (!is_null($img) && $img !== FALSE) {
                                $img->resize(1000, null, function ($constraint) {
                                    $constraint->aspectRatio();
                                });
                                $photo_res[] = "data:image/" . pathinfo(url($p), PATHINFO_EXTENSION) . ";base64," . base64_encode($img->stream());
                            }
                        } catch (\Intervention\Image\Exception\NotReadableException $e) {
                            $photo_res[] = "data:image/" . pathinfo(url($p), PATHINFO_EXTENSION) . ";base64," . base64_encode(Storage::get($p));
                        } catch (\Symfony\Component\Debug\Exception\FatalErrorException $e) {
                            
                        } catch (Exception $e) {
                            
                        }
//                        $photo_res[] = "data:image/" . pathinfo(url($p), PATHINFO_EXTENSION) . ";base64," . base64_encode(Storage::get($p));
                    }
                }
            }
            $i++;
        }
        return $photo_res;
    }

    public function getPhotos222(Rnko $rnko, $with_claim_date = true) {
        if ($with_claim_date) {
            $folderName = 'images/' . $rnko->passport_series . $rnko->passport_number . '/' . $rnko->claim_date->format('Y-m-d');
        } else {
            $folderName = 'images/' . $rnko->passport_series . $rnko->passport_number;
        }
        $photos = [];
        if (HelperUtil::FtpFolderExists($folderName, 'ftp222')) {
            if ($with_claim_date) {
                $photos = Storage::disk('ftp222')->files($folderName);
                \PC::debug('strange');
            } else {
                $dirs = HelperUtil::FtpFolderList($folderName, 'ftp222');
                foreach ($dirs as $dir) {
                    $d = str_replace('/armff.ru/local/storage/app/', '', $dir);
                    $photos = array_merge($photos, Storage::disk('ftp222')->files($d));
                }
            }
        }
        $photo_res = [];
        $i = 0;
        foreach ($photos as $p) {
            if ($i > 10) {
                break;
            }
            if (stripos($p, '.jpg') !== FALSE || stripos($p, '.png') !== FALSE || stripos($p, '.jpeg') !== FALSE) {
                if (HelperUtil::FtpFileExists('/' . $p, 'ftp222')) {
                    try {
                        $file = Storage::disk('ftp222')->get($p);
                    } catch (Exception $ex) {
                        $file = null;
                    }
                    if ($file != null && Storage::disk('ftp222')->size($p) > 15000) {
                        Log::info('RnkoController.resizePhoto', ['photo' => $p, 'user' => Auth::user()->id]);
                        try {
                            $img = Image::make($file);
                            if (!is_null($img) && $img !== FALSE) {
                                $img->resize(1000, null, function ($constraint) {
                                    $constraint->aspectRatio();
                                });
                                $photo_res[] = "data:image/" . pathinfo(url($p), PATHINFO_EXTENSION) . ";base64," . base64_encode($img->stream());
                            }
                        } catch (\Intervention\Image\Exception\NotReadableException $e) {
                            $photo_res[] = "data:image/" . pathinfo(url($p), PATHINFO_EXTENSION) . ";base64," . base64_encode($file);
                        } catch (\Symfony\Component\Debug\Exception\FatalErrorException $e) {
                            
                        } catch (Exception $e) {
                            
                        }
                    }
                }
            }
            $i++;
        }
        return $photo_res;
    }

    public function update(Request $req) {
        $rnko = Rnko::find(Input::get('id', null));
        if (is_null($rnko)) {
            return $this->backWithErr();
        }
        if (Input::get('status') == Rnko::STATUS_NEW && !$req->has('by_number')) {
            return $this->backWithErr('Поменяйте статус!');
        }
        if ($req->has('by_number')) {
            $rnko->check_user_id = Auth::user()->id;
            $rnko->end_check = Carbon::now()->format('Y-m-d H:i:s');
            $rnko->comment = $req->comment;
            $rnko->check_status = $req->check_status;
        } else {
            $rnko->fill(Input::all());
            $rnko->subdivision_id = Auth::user()->subdivision_id;
            $rnko->user_id = Auth::user()->id;
        }
        if (Auth::user()->subdivision_id == 113) {
            $rnko->check_user_id = Auth::user()->id;
        }
        $rnko->save();
        if ($req->has('by_number')) {
            return redirect('reports/rnko/number')->with('msg_suc', \App\Utils\StrLib::SUC);
        } else {
            return $this->backWithSuc();
        }
    }

    public function refreshNophotos() {
        $items = Rnko::where('status', Rnko::STATUS_NOTFOUND)->get();
        $count = 0;
        foreach ($items as $rnko) {
            $folderName = 'images/' . $rnko->passport_series . $rnko->passport_number . '/' . $rnko->claim_date->format('Y-m-d');
            if (HelperUtil::FtpFolderExists($folderName)) {
                $count++;
                $rnko->status = Rnko::STATUS_NEW;
                $rnko->comment = '';
                $rnko->user_id = null;
                $rnko->subdivision_id = null;
                $rnko->save();
            }
        }
//        \PC::debug($count);
    }

    public function refreshChecked() {
        $items = Rnko::whereIn('status', [Rnko::STATUS_CHANGED, Rnko::STATUS_CHECKED])->whereNotNull('user_id')->get();
        foreach ($items as $rnko) {
            $rnko->prev_comment = $rnko->comment;
            $rnko->prev_user_id = $rnko->user_id;
            $rnko->prev_status = $rnko->status;
            $rnko->status = Rnko::STATUS_NEW;
            $rnko->comment = '';
            $rnko->user_id = null;
            $rnko->subdivision_id = null;
            $rnko->save();
//            \PC::debug($rnko);
//            break;
        }
    }

    public function getEditStat() {
        $html = '<table>';
        $cols = ['users.name as name', 'rnko.card_number', 'rnko.fio', 'rnko.passport_series', 'rnko.passport_number', 'rnko.comment'];
        $items = Rnko::where('prev_status', Rnko::STATUS_CHANGED)->leftJoin('users', 'users.id', '=', 'rnko.prev_user_id')->select($cols)->orderBy('users.name')->get();
        \PC::debug(count($items));
        $rows = 0;
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<td>Специалист</td>';
        $html .= '<td>Номер карты</td>';
        $html .= '<td>ФИО</td>';
        $html .= '<td>Серия паспорта</td>';
        $html .= '<td>Номер паспорта</td>';
        $html .= '<td>Комментарий</td>';
        $html .= '<td>Предыдущий комментарий</td>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        foreach ($items as $item) {
            $rows++;
            $html .= '<tr>';
            $html .= '<td>' . $item->name . '</td>';
            $html .= '<td>/' . $item->card_number . '/</td>';
            $html .= '<td>' . $item->fio . '</td>';
            $html .= '<td>/' . $item->passport_series . '/</td>';
            $html .= '<td>/' . $item->passport_number . '/</td>';
            $html .= '<td>' . $item->comment . '</td>';
            $html .= '<td>' . $item->prev_comment . '</td>';
            $html .= '</tr>';
//            if ($rows == 1000) {
//                break;
//            }
        }
        $html.='</tbody>';
        $html.='</table>';
        return $html;
    }

    public function uploadXml() {
//        return $this->refreshNophotos();
//        return $this->uploadFromARM();
        $reader = new XMLReader();
        $reader->open("./mailfiles/customers.xml");
        $r = 0;
        $u = 0;
        $u_id = 0;
        $users = User::where('last_login', '>=', Carbon::now()->subDays(5)->format('Y-m-d'))->where('group_id', '1')->where('subdivision_id', '<>', '113')->get();
        $u_count = count($users);
        $xml_count = 42167;
        $xml_per_user = round($xml_count / $u_count);

        while ($reader->read()) {
            switch ($reader->nodeType) {
                case (XMLREADER::ELEMENT):
                    if ($reader->localName == "i") {
                        $r++;
                        if ($reader->getAttribute('c') != "" && !is_null($reader->getAttribute('c'))) {
                            $u++;
                            $item = new Rnko();
                            $item->passport_series = $reader->getAttribute('s');
                            $item->passport_number = $reader->getAttribute('n');
                            $item->fio = $reader->getAttribute('f');
                            $item->claim_date = with(Carbon::createFromFormat('dmY', $reader->getAttribute('d')))->format('Y-m-d H:i:s');
                            $item->user_id = $users[$u_id]->id;
                            $item->subdivision_id = $users[$u_id]->subdivision_id;
                            $item->card_number = $reader->getAttribute('c');
                            $item->save();
                        }
                        if ($u > $xml_per_user) {
                            $u = 0;
                            $u_id++;
                        }
                    }
                    break;
            }
//            if ($r == 10) {
//                break;
//            }
        }
    }

    public function uploadFromARM() {
        $r = 0;
        $u = 0;
        $u_id = 0;
        $cards = \App\Loan::whereNotNull('card_id')
                ->select('cards.card_number as card_number', 'passports.series as pseries', 'passports.number as pnumber', 'claims.created_at as claim_date', 'passports.fio as fio')
                ->where('loans.created_at', '>', '2015-11-01')
                ->leftJoin('cards', 'cards.id', '=', 'loans.card_id')
                ->leftJoin('claims', 'claims.id', '=', 'loans.claim_id')
                ->leftJoin('passports', 'passports.id', '=', 'claims.passport_id')
                ->whereNotIn('cards.card_number', Rnko::lists('card_number'))
                ->get();
        $cards_count = count($cards);

        $users = User::where('last_login', '>=', Carbon::now()->subDays(5)->format('Y-m-d'))
                ->where('subdivision_id', '<>', '113')
                ->whereNotIn('id', Rnko::groupBy('user_id')->lists('user_id'))
                ->get();
        $u_count = count($users);
        $xml_per_user = round($cards_count / $u_count);
        foreach ($cards as $card) {
            $r++;
            $u++;
            $user = $users[$u_id];
            $rnko = new Rnko();
            $rnko->card_number = $card->card_number;
            $rnko->passport_series = $card->pseries;
            $rnko->passport_number = $card->pnumber;
            $rnko->claim_date = $card->claim_date;
            $rnko->fio = $card->fio;
//            $rnko->user_id = 5;
            $rnko->user_id = $user->id;
            $rnko->subdivision_id = $user->subdivision_id;
            $rnko->comment = 'ARM';
            $rnko->save();
            if ($u > $xml_per_user) {
                $u = 0;
                $u_id++;
            }
//            if($r==1){
//                break;
//            }
        }
        foreach ($users as $user) {
            \PC::debug($user->toArray(), $user->id);
        }
        echo '<br>' . $cards_count;
        echo '<br>' . $xml_per_user;
        echo '<br>' . $u_count;
    }

    public function phpinfo() {
        return view('reports/phpinfo');
    }

    public function getAllWithPhotos() {
        $html = '<table>';
        $cols = ['rnko.card_number', 'rnko.fio', 'rnko.comment', 'rnko.passport_series', 'rnko.passport_number', 'rnko.claim_date'];
        $items = Rnko::whereRaw('(prev_status is null or prev_status in(0,3)) and info=1 and check_user_id is null')->select($cols)->get();
        \PC::debug(count($items));
        \PC::debug(Carbon::now()->format('Y-m-d H:i:s'));
        $rows = 0;
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<td>Специалист</td>';
        $html .= '<td>Номер карты</td>';
        $html .= '<td>ФИО</td>';
        $html .= '<td>Комментарий</td>';
        $html .= '<td>Предыдущий комментарий</td>';
        $html .= '<td>Папка с фото</td>';
        $html .= '<td>Дата заявки</td>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        $absent = 0;
        foreach ($items as $item) {
            $folder = 'images/' . $item->passport_series . $item->passport_number . '/' . with(new Carbon($item->claim_date))->format('Y-m-d');
            $folder2 = '\\\\192.168.1.123\\' . str_replace('/', '\\', $folder);
            $folder3 = 'images/' . $item->passport_series . $item->passport_number;
            $folder31 = '\\\\192.168.1.123\\' . str_replace('/', '\\', $folder3);
            if (HelperUtil::FtpFolderExists($folder)) {
                $rows++;
                $html .= '<tr>';
//                $html .= '<td>' . $item->name . '</td>';
                $html .= '<td></td>';
                $html .= '<td>/' . $item->card_number . '/</td>';
                $html .= '<td>' . $item->fio . '</td>';
//                $html .= '<td>/' . $item->passport_series . '/</td>';
//                $html .= '<td>/' . $item->passport_number . '/</td>';
                $html .= '<td>' . $item->comment . '</td>';
                $html .= '<td>' . $item->prev_comment . '</td>';
                $html .= '<td>' . $folder2 . '</td>';
                $html .= '<td>' . with(new Carbon($item->claim_date))->format('Y-m-d') . '</td>';
                $html .= '</tr>';
            } else {
                $absent++;
                if ($absent < 50) {
                    \PC::debug($folder2);
                }
            }
//            if ($rows == 500) {
//                break;
//            }
        }
        \PC::debug(Carbon::now()->format('Y-m-d H:i:s'));
        \PC::debug($absent);
        $html.='</tbody>';
        $html.='</table>';
        return $html;
    }

    public function getAllChecked() {
        $html = '<table>';
        $cols = ['rnko.card_number', 'rnko.fio', 'rnko.comment', 'users.name as checker', 'rnko.prev_comment', 'rnko.prev_status', 'rnko.prev_user_id', 'rnko.status', 'rnko.check_status', 'rnko.user_id'];
        $items = Rnko::whereNotNull('check_user_id')->leftJoin('users', 'users.id', '=', 'rnko.check_user_id')->select($cols)->orderBy('rnko.card_number')->get();
        $rows = 0;
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<td>Номер карты</td>';
        $html .= '<td>ФИО</td>';
        $html .= '<td>Комментарий, оставленный последним специалистом или проверяющим</td>';
        $html .= '<td>Проверяющий</td>';
        $html .= '<td>Статус, выставленный проверяющим</td>';
        $html .= '<td>Комментарий, оставленный первым специалистом</td>';
        $html .= '<td>Статус, выставленный первым специалистом</td>';
        $html .= '<td>Специалист, проверявший первым</td>';
        $html .= '<td>Специалист, проверявший последним</td>';
        $html .= '<td>Статус, выставленный последним специалистом</td>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        $absent = 0;
        \PC::debug(Carbon::now()->format('Y-m-d H:i:s'), 'started');
        foreach ($items as $item) {
            $rows++;
            $html .= '<tr>';
            $html .= '<td>' . $item->card_number . '</td>';
            $html .= '<td>' . $item->fio . '</td>';
            $html .= '<td>' . $item->comment . '</td>';
            $html .= '<td>' . $item->checker . '</td>';
            $html .= '<td>' . @Rnko::getStatusList()[$item->check_status] . '</td>';
            $html .= '<td>' . $item->prev_comment . '</td>';
            $html .= '<td>' . @Rnko::getStatusList()[$item->prev_status] . '</td>';
            $html .= '<td>' . ((!is_null($item->prev_user_id)) ? with(User::where('id', $item->prev_user_id)->select('name')->first())->name : '') . '</td>';
            $html .= '<td>' . ((!is_null($item->user_id)) ? with(User::where('id', $item->user_id)->select('name')->first())->name : '') . '</td>';
            $html .= '<td>' . @Rnko::getStatusList()[$item->status] . '</td>';
            $html .= '</tr>';
        }
        \PC::debug(Carbon::now()->format('Y-m-d H:i:s'), 'finished');
        \PC::debug($rows, 'rows');
        $html.='</tbody>';
        $html.='</table>';
        return $html;
    }

    public function removeAllDuplicates() {
        $sql = 'SELECT id,i.card_number,fio, check_status, check_user_id, end_check
                FROM rnko i
                INNER JOIN (
                 SELECT card_number
                    FROM rnko
                    GROUP BY card_number
                    HAVING COUNT( id ) > 1
                ) j ON i.card_number=j.card_number
                order by i.card_number,end_check desc';
        $rows = DB::select($sql);
        $prev_card = '';
        $prev_card_status = '';
        $i = 0;
        foreach ($rows as $r) {
            if ($r->card_number == $prev_card) {
                $i++;
                echo $r->id . '|' . $r->card_number . ' ' . $r->fio . '|' . $r->check_user_id . '|' . $r->end_check . '<br>';
                DB::table('rnko')->where('id', $r->id)->delete();
            }
            $prev_card = $r->card_number;
//            $prev_card_status = $r->check_status;
        }
        echo $i;
    }

    public function openAllPhotos(Request $req) {
        $rnko = Rnko::find($req->id);
        if (is_null($rnko)) {
            return view('reports.rnko_all_photos', ['photos' => []])->with('msg_err', 'Неверный номер');
        }
        return view('reports.rnko_all_photos', ['photos' => $this->getPhotos($rnko, false)]);
    }

    public function getReportByDays(Request $req) {
        if (!in_array(Auth::user()->id, [1, 5, 189])) {
            return redirect('reports/rnko/admin')->with('msg_err', \App\Utils\StrLib::ERR_NOT_ADMIN);
        }
        $startDate = ($req->has('date_start')) ? with(new Carbon($req->date_start)) : Carbon::now()->setTime(0, 0, 0);
        if ($startDate->gt(Carbon::now())) {
            return redirect('reports/rnko/admin')->with('msg_err', 'Неверная дата начала');
        }
        $endDate = Carbon::now()->addDay()->setTime(0, 0, 0);
        if ($req->has('end_date')) {
            $endDate = with(new Carbon($req->end_date))->addDay()->setTime(0, 0, 0);
        }
//        $startDate = with(new Carbon('2016-10-10 00:00:00'));
//        $endDate = with(new Carbon('2016-10-10 08:00:00'));

        $diffInDates = $startDate->diffInDays($endDate);

        $stat = DB::table('rnko')
                ->select(DB::raw('count(*) as asd, users.name as fio, users.id as uid'))
                ->leftJoin('users', 'users.id', '=', 'rnko.check_user_id')
                ->whereNotNull('rnko.check_user_id')
                ->groupBy('uid')
                ->orderBy('asd', 'desc')
                ->get();
        if (count($stat) == 0) {
            return redirect('reports/rnko/admin')->with('msg_err', 'Не нашлось данных');
        }

        $it = $this->it;
        $ovk = $this->ovk;
        $cc = $this->cc;
        $seb = $this->seb;

        foreach ($stat as $user) {
            if ($req->has('dept')) {
                if ($req->dept == 'sales') {
                    if (in_array($user->uid, $ovk) || in_array($user->uid, $cc) || in_array($user->uid, $it) || in_array($user->uid, $seb)) {
                        $user->hide = 1;
                    }
                } else if ($req->dept == 'ovk') {
                    if (!in_array($user->uid, $ovk)) {
                        $user->hide = 1;
                    }
                } else if ($req->dept == 'it') {
                    if (!in_array($user->uid, $it)) {
                        $user->hide = 1;
                    }
                } else if ($req->dept == 'cc') {
                    if (!in_array($user->uid, $cc)) {
                        $user->hide = 1;
                    }
                } else if ($req->dept == 'seb') {
                    if (!in_array($user->uid, $seb)) {
                        $user->hide = 1;
                    }
                }
            }
            $user->days = [];
            $user->total = 0;
            $user->totalAfterSix = 0;
            $user->totalWeekend = 0;
            for ($i = 0; $i < $diffInDates; $i++) {
//            for ($i = 0; $i < 1; $i++) {
                $date = ($i == 0) ? $startDate : with(new Carbon($startDate))->addDays($i);
                $minDate9 = with(new Carbon($date))->setTime(9, 0, 0)->format('Y-m-d H:i:s');
                $minDate18 = with(new Carbon($date))->setTime(18, 0, 0)->format('Y-m-d H:i:s');
                $minDate0 = with(new Carbon($date))->setTime(0, 0, 0)->format('Y-m-d H:i:s');
                $maxDate24 = with(new Carbon($date))->setTime(23, 59, 59)->format('Y-m-d H:i:s');
//                $maxDate9 = with(new Carbon($date))->setTime(9, 0, 0)->format('Y-m-d H:i:s');
                $key = $date->format('d.m.y');
                $user->days[$key] = [
                    '0' => DB::select('SELECT count(*) as num FROM armf.rnko where check_user_id=? and ((start_check>=? and start_check<=?))', [$user->uid, $minDate9, $minDate18])[0]->num,
//                    '0' => Rnko::where('check_user_id', $user->uid)
//                            ->whereRaw('(start_check >= "' . $minDate9 . '" and start_check < "' . $minDate18 . '")')
//                            ->count(),
                    '1' => DB::select('SELECT count(*) as num FROM armf.rnko where check_user_id=? and ((start_check>=? and start_check<=?) or (start_check>=? and start_check<=?))', [$user->uid, $minDate0, $minDate9, $minDate18, $maxDate24])[0]->num,
//                    Rnko::where('check_user_id', $user->uid)
//                            ->whereRaw('((start_check >= "' . $minDate0 . '" and start_check < "' . $minDate9 . '")) or ((start_check >= "' . $minDate18 . '" and start_check <= "' . $maxDate24 . '"))')
//                            ->count(),
                    '2' => (in_array($date->dayOfWeek, [6, 0]))
                ];
                if ($user->uid == 517) {
                    if($key=='16.11.16'){
                        $user->days[$key][0] = 14;
                    }
                    if($key=='17.11.16'){
                        $user->days[$key][0] = 83;
                    }
                }
                if($user->uid == 32){
                    if($key=='16.11.16'){
                        $user->days[$key][0] += 50;
                    }
                    if($key=='17.11.16'){
                        $user->days[$key][0] += 65;
                    }
                }
                if (!$user->days[$key][2]) {
                    $user->totalAfterSix += $user->days[$key][1];
                }
                if (in_array($date->dayOfWeek, [6, 0])) {
                    $user->totalWeekend += $user->days[$key][0] + $user->days[$key][1];
                } else {
                    $user->total += $user->days[$key][0];
                }
            }
        }
        return view('reports.rnko_by_dates', ['stat' => $stat, 'date_start' => $startDate->format('d.m.Y'), 'today' => Carbon::now()->format('d.m.Y')]);
    }

}
