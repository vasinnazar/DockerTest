<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\HelpFile;
use Storage;
use Response;

class HelpController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    public function menu() {
        return view('help.helpmenu');
    }

    public function cert() {
        return view('help.cert');
    }

    public function docs() {
        return view('help.docs');
    }

    public function rules() {
        return view('help.rules');
    }

    public function addresses() {
        return view('help.addresses');
    }
    public function page($page){
        return view('help.'.$page);
    }
    public function instructions(){
        return view('help.instructions');
    }
    /**
     * Страница с видеоинструкциями
     * @param integer $id 
     * @return type
     */
    public function videos($id = null) {
        $data = [
            'videos' => HelpFile::where('type', HelpFile::T_VIDEO)->get()
        ];
        if (!is_null($id)) {
            $data['video'] = HelpFile::find($id);
        }
        return view('help.videos', $data);
    }
    /**
     * Вернуть файл по айдишнику в базе и расширению файла
     * @param integer $id
     * @param string $ext
     * @return type
     */
    public function getFile($id, $ext) {
        $file = HelpFile::find($id);
        $fileContents = Storage::disk('ftp222')->get($file->url . '.' . $ext);
        $response = Response::make($fileContents, 200);
        if ($ext == 'vtt') {
            $response->header('Content-Type', "text/vtt");
        } else if (in_array($ext, ['mp4', 'ogv', 'webm'])) {
            $response->header('Content-Type', "video/" . $ext);
        }
        return $response;
    }

}
