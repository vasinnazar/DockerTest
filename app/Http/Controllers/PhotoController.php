<?php

namespace App\Http\Controllers;

use App\Claim,
    Illuminate\Support\Facades\DB,
    Image,
    Auth,
    Illuminate\Http\Request,
    Input,
    Validator,
    Redirect,
    App\Customer,
    App\Photo,
    \Illuminate\Support\Facades\File,
    \Symfony\Component\HttpFoundation\File\UploadedFile,
    App\Spylog\Spylog,
    Illuminate\Support\Facades\Storage,
    App\Utils\StrLib,
    Log,
    Carbon\Carbon,
    App\Passport;

class PhotoController extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    public function upload_photo($claim_id) {
        $claim = Claim::find($claim_id);
        $client = Customer::find($claim->customer_id);
        $fio = $claim->passport->fio;
        $photos = Photo::where('claim_id', $claim_id)->get();
        return view('upload_photo', ['client' => $client, 'claim' => $claim, 'photos' => $photos, 'fio' => $fio]);
    }

    /**
     * загрузка фотографий
     * @param Request $request
     * @return type
     */
    public function uploads(Request $request) {
        (string) $strput = "нет";

        $files = array('image' => Input::file('file'));
        $rules = array('image' => 'required',); //mimes:jpeg,bmp,png and for max size max:10000
        $validator = Validator::make($files, $rules);
        if (!$validator->passes()) {
            return Redirect::to('uploads')->withInput()->withErrors($validator);
        }
        if (!is_numeric($request->claim_id)) {
            return Redirect::to('/')->with('msg', 'Не верный номер займа')->with('class', 'alert-danger');
        }
        if (!is_numeric($request->customer_id)) {
            return Redirect::to('/')->with('msg', 'Клиент не найден')->with('class', 'alert-danger');
        }
        $passport = Passport::where('customer_id', $request->customer_id)->first();
        if (!(isset($passport->series)) || !(isset($passport->number))) {
            return Redirect::route('photos.add', array('claim_id' => $request->claim_id))
                            ->with('msg', 'Не найдены паспортные данные заёмщика')
                            ->with('class', 'alert-danger');
        }
        //проверяем все файлы на максимальный размер
        foreach (Input::file('file') as $file) {
            if ($file->getClientSize() > UploadedFile::getMaxFilesize() || $file->getClientSize() == 0) {
                return Redirect::route('photos.add', ['claim_id' => $request->claim_id])
                                ->with('msg', 'Размер файла ' . $file->getClientOriginalName()
                                        . ' больше чем максимально допустимый - '
                                        . (UploadedFile::getMaxFilesize() / 1048576) . ' Мб')
                                ->with('class', 'alert-danger');
            }
        }
        //создаёт папку с именем по серии и номеру паспорта
        $dir = 'images/' . (string) $passport->series . (string) $passport->number . '/' . (date("Y-m-d")) . '/';
        if (!Storage::exists($dir)) {
            if (!Storage::makeDirectory($dir)) {
                return Redirect::route('photos.add', ['claim_id' => $request->claim_id])
                                ->with('msg', 'Ошибка создания каталога')->with('class', 'alert-danger');
            }
        }

        //true если у заявки уже есть главный файл, false если нет
        $hasMain = (count(Photo::whereRaw('claim_id=? and is_main=?', [$request->claim_id, 1])->get()) > 0) ? true : false;
        foreach (Input::file('file') as $file) {
            if (is_null($file)) {
                continue;
            }
            //переносим файл в папку
            $filename = $file->getClientOriginalName();
            //переименовываем файл, если с таким именем в папке уже есть
            if (Storage::exists($dir . $filename)) {
                $filename = uniqid() . $file->getClientOriginalName();
            }
            $img = Image::make(file_get_contents($file));
            if ($img->width() > $img->height()) {
                $img->resize(1000, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
            } else {
                $img->resize(null, 1000, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }
            if (Storage::put($dir . $filename, $img->stream())) {
                //добавляем запись о файле в бд
                $photo = new Photo();
                $photo->claim_id = $request->claim_id;
                $photo->path = $dir . $filename;
                if (!$hasMain) {
                    $photo->is_main = 1;
                    $hasMain = true;
                }
                if ($photo->save()) {
                    Spylog::logModelAction(Spylog::ACTION_CREATE, 'photos', $photo);
                }
            }
        }
        return Redirect::route('photos.add', array('claim_id' => $request->claim_id));
    }

    public function ajaxUpload(Request $req) {
        \PC::debug($req->all());
//        (string) $strput = "нет";
        $file = Input::file('file');
        $files = array('image' => Input::file('file'));
        $rules = array('image' => 'required',); //mimes:jpeg,bmp,png and for max size max:10000
        $validator = Validator::make($files, $rules);
        if (!$validator->passes()) {
            return ['error' => 'Файлы не найдены или не являются изображениями'];
        }
        if (!is_numeric($req->claim_id)) {
            return ['error' => 'Не верный номер займа'];
        }
        if (!is_numeric($req->customer_id)) {
            return ['error' => 'Клиент не найден'];
        }
        $claim = Claim::find($req->claim_id);
        if (is_null($claim)) {
            return ['error' => 'Не верный номер займа'];
        }
//        $passport = Passport::where('customer_id', $req->customer_id)->first();
        $passport = Passport::find($claim->passport_id);
        if (!(isset($passport->series)) || !(isset($passport->number))) {
            return ['error' => 'Не найдены паспортные данные заёмщика'];
        }
        //проверяем все файлы на максимальный размер
        if ($file->getClientSize() > UploadedFile::getMaxFilesize() || $file->getClientSize() == 0) {
            return ['error' => 'Размер файла ' . $file->getClientOriginalName() . ' больше чем максимально допустимый - ' . (UploadedFile::getMaxFilesize() / 1048576) . ' Мб'];
        }
        //создаёт папку с именем по серии и номеру паспорта
//        $dir = 'images/' . (string) $passport->series . (string) $passport->number . '/' . (date("Y-m-d")) . '/';
        $dir = 'images/' . (string) $passport->series . (string) $passport->number . '/' . $claim->created_at('Y-m-d') . '/';
        if (!is_dir($dir)) {
            if (!Storage::makeDirectory($dir)) {
                return ['error' => 'Ошибка создания каталога'];
            }
        }

        //true если у заявки уже есть главный файл, false если нет
        $hasMain = (count(Photo::whereRaw('claim_id=? and is_main=?', [$req->claim_id, 1])->get()) > 0) ? true : false;

        //переносим файл в папку
        $filename = $file->getClientOriginalName();
        //переименовываем файл, если с таким именем в папке уже есть
        if (Storage::exists($dir . $filename)) {
            $filename = uniqid() . $file->getClientOriginalName();
        }
        $img = Image::make(file_get_contents($file));
        if ($img->width() > $img->height()) {
            $img->resize(1000, null, function ($constraint) {
                $constraint->aspectRatio();
            });
        } else {
            $img->resize(null, 1000, function ($constraint) {
                $constraint->aspectRatio();
            });
        }
        if (Storage::put($dir . $filename, $img->stream())) {
            //добавляем запись о файле в бд
            $photo = new Photo();
            $photo->claim_id = $req->claim_id;
            $photo->path = $dir . $filename;
            if (!$hasMain) {
                $photo->is_main = 1;
                $hasMain = true;
            }
            if ($photo->save()) {
                Spylog::logModelAction(Spylog::ACTION_CREATE, 'photos', $photo);
            }
        }
        return [
//            "append" => true,
            "initialPreview" => [
                '<div ' . (($photo->is_main) ? 'class="main-photo"' : '') . '><img src="data:image/' . pathinfo(url($photo->path), PATHINFO_EXTENSION)
                . ';base64,' . base64_encode(Storage::get($photo->path)) . '" class="file-preview-image" alt="' . $filename . '" title="' . $filename . '"></div>'
            ],
            "initialPreviewConfig" => [
                [
                    "caption" => $filename,
                    "width" => '120px',
                    "url" => url('photos/ajax/remove'),
                    "key" => $photo->id,
                    "extra" => ["id" => $photo->id]
                ]
            ]
        ];
    }

//синхронная загрузка фотографий
    public function ajaxUpload2(Request $req) {
//        (string) $strput = "нет";
//        $file = Input::file('file');
        $files = array('image' => Input::file('file'));
        $rules = array('image' => 'required',); //mimes:jpeg,bmp,png and for max size max:10000
        $validator = Validator::make($files, $rules);
        if (!$validator->passes()) {
            return ['error' => 'Файлы не найдены или не являются изображениями'];
        }
        if (!is_numeric($req->claim_id)) {
            return ['error' => 'Не верный номер займа'];
        }
        if (!is_numeric($req->customer_id)) {
            return ['error' => 'Клиент не найден'];
        }
        $claim = Claim::find($req->claim_id);
        if (is_null($claim)) {
            return ['error' => 'Не верный номер займа'];
        }
        $passport = Passport::find($claim->passport_id);
        if (!(isset($passport->series)) || !(isset($passport->number))) {
            return ['error' => 'Не найдены паспортные данные заёмщика'];
        }
        //true если у заявки уже есть главный файл, false если нет
        $hasMain = (count(Photo::whereRaw('claim_id=? and is_main=?', [$req->claim_id, 1])->get()) > 0) ? true : false;
        $res = [
            'initialPreview' => [],
            'initialPreviewConfig' => []
        ];
        $photos = [];
        $dir = $claim->getPhotosFolderPath(false, true, false);
        foreach (Input::file('file') as $file) {
            $p = $this->fileUpload($file, $req, $dir, $hasMain);
            $photos[] = $p;
        }
        foreach ($photos as $p) {
            if (!is_null($p)) {
                $pname = substr($p->description, strrpos($p->description, '/') + 1);
                $res['initialPreview'][] = '<div ' . (($p->is_main) ? 'class="main-photo"' : '') . '><img src="data:image/' . pathinfo(url($p->path), PATHINFO_EXTENSION)
                        . ';base64,' . base64_encode(Storage::get($p->path)) . '" class="file-preview-image" alt="' . $pname . '" title="' . $pname . '"></div>';
                $res['initialPreviewConfig'][] = [
                    "caption" => $p->description,
                    "width" => '120px',
                    "url" => url('photos/ajax/remove'),
                    "key" => $p->id,
                    "extra" => ["id" => $p->id]
                ];
            }
        }
        return $res;
    }

    function fileUpload($file, $request, $dir, $hasMain, $addTimestamp = false) {
        if (is_null($file)) {
            return null;
        }
        if ($file->getClientSize() > UploadedFile::getMaxFilesize() || $file->getClientSize() == 0) {
            return null;
        }
        //создаёт папку с именем по серии и номеру паспорта
        if (!Storage::exists($dir)) {
            if (!Storage::makeDirectory($dir)) {
                return null;
            }
        }

        //переносим файл в папку
//        $filename = $file->getClientOriginalName();
        //переименовываем файл, если с таким именем в папке уже есть
//        if (Storage::exists($dir . $filename)) {
        $filename = uniqid() . '.' . substr($file->getClientOriginalName(), stripos($file->getClientOriginalName(), '.') + 1);
//        }
        $img = Image::make(file_get_contents($file));
        if ($img->width() > $img->height()) {
            $img->resize(1000, null, function ($constraint) {
                $constraint->aspectRatio();
            });
        } else {
            $img->resize(null, 1000, function ($constraint) {
                $constraint->aspectRatio();
            });
        }
        if ($addTimestamp) {
            $img->text(Carbon::now()->format('d.m.Y H:i:s'), $img->width() - 150, $img->height() - 30, function($font) {
                $font->file(2);
                $font->size(40);
                $font->color('#b03b93');
            });
        }
        if (Storage::put($dir . $filename, $img->stream())) {
            //добавляем запись о файле в бд
            $photo = new Photo();
            $photo->claim_id = $request->claim_id;
            $photo->path = $dir . $filename;
            $photo->is_main = 0;
            if ($request->has('customer_id')) {
                $photo->customer_id = (int) $request->get('customer_id');
            }
            if ($photo->save()) {
                Spylog::logModelAction(Spylog::ACTION_CREATE, 'photos', $photo);
                return $photo;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    public function ajaxRemove(Request $req) {
        if (!Auth::user()->isAdmin() && !Auth::user()->isCC()) {
            return ['error' => StrLib::ERR_NOT_ADMIN];
        }
        if (!$req->has('id')) {
            return ['error' => StrLib::ERR_NO_PARAMS];
        }
        $photo = Photo::find($req->id);
        if (is_null($photo)) {
            return ['error' => StrLib::ERR_NULL];
        }
        if (Storage::delete($photo->path)) {
            Spylog::logModelAction(Spylog::ACTION_DELETE, 'photos', $photo);
            if ($photo->delete()) {
                return [];
            } else {
                return ['error' => StrLib::ERR_CANT_DELETE];
            }
        }
    }
    /**
     * Устанавливает фотографию главной (показывает первой при просмотре)
     * если запрашивается в карточке должника то отправляется пост запрос на продажный арм
     * @param Request $req
     * @return type
     */
    public function setMain(Request $req) {
        if (config('app.version_type') == 'debtors') {
            return \App\Utils\HelperUtil::SendPostByCurl(config('services.arm.url').'/debtors/photos/main', ['main_id' => $req->get('main_id','')]);
        }
        $photo = Photo::where('claim_id', $req->get('claim_id'))->where('id', $req->get('main_id'))->first();
        if(is_null($photo)){
            return 0;
        }
        return ($photo->setMain()) ? 1 : 0;
    }

    public function getPhotos(Request $req) {
        if (!$req->has('claim_id')) {
            return ['error' => StrLib::ERR_NO_PARAMS];
        }
        $res = ["initialPreview" => [], "initialPreviewConfig" => []];
//        initialPreview.push('<img src="' + data[p]["src"] + '" class="file-preview-image" alt="' + data[p]["filename"] + '" title="' + data[p]["filename"] + '">');
//        initialPreviewConfig.push({caption: data[p]["filename"], url: data[p]["remove_url"], extra: {id: data[p]["id"]}, width: '20px'});
        $claim = Claim::find($req->claim_id);
        $disk = 'ftp31';
        
        if (!is_null($claim) && $claim->subdivision->is_terminal) {
            $photos = Photo::where('claim_id', $req->claim_id)->orWhere('customer_id', $claim->customer_id);
        } else {
            $photos = Photo::where('claim_id', $req->claim_id);
        }

        if ($req->has('id')) {
            $photos->where('id', $req->id);
        }
        $photos = $photos->orderBy('created_at', 'desc')->limit(30)->get();
        if(count($photos)==0){
//            $folderPath0 = $claim->passport->series.$claim->passport->number;
            $folderPath1 = $claim->getPhotosFolderPath(false, true, false);
//            if(!Storage::exists($folderPath0)){
//                Storage::makeDirectory($folderPath0);
//            }
            if(!Storage::exists($folderPath1)){
                Storage::makeDirectory($folderPath1);
            }
        }
        foreach ($photos as $photo) {
            $disk = 'ftp31';
            if($photo->created_at->lt(new Carbon('2017-12-22 16:00:00'))){
                $disk = 'ftp31_999';
            } else if($photo->created_at->lt(new Carbon('2018-06-14 16:20:00'))){
                $disk = 'ftp31_111';
            }
            if (!Storage::disk($disk)->exists($photo->path)) {
                continue;
            }
            $file = Storage::disk($disk)->get($photo->path);
            $filename = substr($photo->path, strrpos($photo->path, '/') + 1);
//            $filename = $photo->path;
            $res["initialPreview"][] = '<div ' . (($photo->is_main) ? 'class="main-photo"' : '') . '><img src="'
                    . 'data:image/' . pathinfo($photo->path, PATHINFO_EXTENSION)
                    . ';base64,' . base64_encode($file)
                    . '" class="file-preview-image" alt="' . $filename
                    . '" title="' . $filename . '" data-is-main="' . $photo->is_main . '" data-id="' . $photo->id . '" data-claim-id="' . $photo->claim_id . '"></div>';
            $res["initialPreviewConfig"][] = [
                "caption" => $filename,
                "extra" => ["id" => $photo->id],
                "width" => "20px",
                "showBrowse" => true
            ];
            if (Auth::user()->isAdmin() || Auth::user()->isCC()) {
                $res["initialPreviewConfig"][count($res["initialPreviewConfig"]) - 1]["url"] = url('photos/ajax/remove?id=' . $photo->id);
            }
        }
        return $res;
    }

    /**
     * сохранение изменений в добавлении фотографий: 
     * устанавливает главное фото, удаляет помеченные на удаление.
     * Помеченные на удаление  приходят строкой айдишников, разделённых запятой
     * @param Request $request
     * @return type
     */
    public function editPhotos(Request $request) {
        if (!is_numeric($request->claim_id)) {
            return Redirect::to('/')->with('msg', 'Не верный номер займа')->with('class', 'alert-error');
        }
        //удаление
        if (!is_null($request->toRemoveIDs) && Auth::user()->isAdmin()) {
            $remIDs = explode(',', $request->toRemoveIDs);
            $photos = Photo::whereIn('id', $remIDs)->get();
            foreach ($photos as $photo) {
                if (Storage::delete($photo->path)) {
                    Spylog::logModelAction(Spylog::ACTION_DELETE, 'photos', $photo);
                    $photo->delete();
                }
            }
        }
        //установка главной
        if (is_numeric($request->mainID)) {
            $photos = Photo::whereRaw('(claim_id=? and is_main=?) or id=?', array($request->claim_id, 1, $request->mainID))->get();
            foreach ($photos as $photo) {
                $photo->is_main = ($photo->id == $request->mainID) ? 1 : 0;
                if ($photo->save()) {
                    Spylog::logModelAction(Spylog::ACTION_UPDATE, 'photos', $photo);
                }
            }
        }
        return Redirect::route('photos.add', array('claim_id' => $request->claim_id))
                        ->with('msg', 'Изменения сохранены')
                        ->with('class', 'alert-success');
    }

    public function viewPhoto(Request $req) {
        $photo = Photo::find($req->id);
        if (is_null($photo)) {
            abort(404);
        }
        $disk = 'ftp31';
        if($photo->created_at->lt(new Carbon('2017-12-22 16:00:00'))){
            $disk = 'ftp31_999';
        } else if($photo->created_at->lt(new Carbon('2018-06-14 16:20:00'))){
            $disk = 'ftp31_111';
        }
        return view('photo_view')->with('photo', $photo)->with('photo_img', 'data:image/' . pathinfo(url($photo->path), PATHINFO_EXTENSION)
                        . ';base64,' . base64_encode(Storage::disk($disk)->get($photo->path)));
    }

    public function webcamUpload(Request $req) {
        $claim = Claim::find($req->get('claim_id'));
        $customer = Customer::find($req->get('customer_id'));
        if (is_null($claim) || is_null($customer)) {
            return response('', 404);
        }
        $dir = $claim->getPhotosFolderPath(false, true, false);
        if (!is_null($this->fileUpload(Input::file('webcam'), $req, $dir, false, true))) {
            return response('', 200);
        } else {
            return response('', 500);
        }
    }

    public function removeAllForClaim(Request $req) {
        if ($req->has('claim_id') && Auth::user()->isAdmin()) {
            $photos = Photo::where('claim_id', (int) $req->claim_id)->get();
            foreach ($photos as $photo) {
                if (Storage::delete($photo->path)) {
                    Spylog::logModelAction(Spylog::ACTION_DELETE, 'photos', $photo);
                    $photo->delete();
                }
            }
            return 1;
        } else {
            return 0;
        }
    }

}
