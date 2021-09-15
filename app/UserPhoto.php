<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Storage;
use Image;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UserPhoto extends Model {

    protected $table = 'user_photos';

    static function getDirectoryPath($user) {
        return 'userphotos/' . $user->subdivision->name_id . '/';
    }

    static function upload($file, $user) {
        if (is_null($file)) {
            return null;
        }
        if ($file->getClientSize() > UploadedFile::getMaxFilesize() || $file->getClientSize() == 0) {
            return null;
        }

        $dir = UserPhoto::getDirectoryPath($user);

        //создаёт папку с именем по серии и номеру паспорта
        if (!Storage::exists($dir)) {
            if (!Storage::makeDirectory($dir)) {
                return null;
            }
        }
        $filename = Carbon::now()->format('YmdHis') . '.' . substr($file->getClientOriginalName(), stripos($file->getClientOriginalName(), '.') + 1);

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
        $img->text(Carbon::now()->format('d.m.Y H:i:s'), $img->width() - 150, $img->height() - 30, function($font) {
            $font->file(2);
            $font->size(40);
            $font->color('#b03b93');
        });
        if (Storage::put($dir . $filename, $img->stream())) {
            //добавляем запись о файле в бд
            $photo = new UserPhoto();
            $photo->path = $dir . $filename;
            $photo->user_id = $user->id;
            $photo->subdivision_id = $user->subdivision_id;
            if ($photo->save()) {
                return $photo;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

}
