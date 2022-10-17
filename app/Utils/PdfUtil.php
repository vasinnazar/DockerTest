<?php

namespace App\Utils;

use mikehaertl\wkhtmlto\Pdf;
use Auth;
use Response;
use App\Utils\FileToPdfUtil;

class PdfUtil {

    static function getPdf($html, $opts = null, $tohtml = false) {
        if ($tohtml) {
            return $html;
        }
        if (is_null($opts)) {
            $opts = [];
        }
        $opts['encoding'] = 'UTF-8';
        if (config('app.centos') || config('app.dev')) {

            return view('contracteditor.pdfgen')->with('html', $html)->with('opts', json_encode($opts));

            $pdf = new Pdf($opts);
            $pdf->addPage($html);
            $res = $pdf->send();
            if (!$res) {
                abort(404);
            } else {
                return response($res, '200', ['Content-Type', 'application/pdf']);
            }
        } else {
            $pdf = new Pdf($opts);
            $pdf->binary = config('options.wkhtmltopdf_path');
            $pdf->addPage($html);
            $res = $pdf->send();
            if (!$res) {
                abort(404);
            } else {
                return response($res, '200', ['Content-Type', 'application/pdf']);
            }
        }
    }

    static function getPdfFromFile($output_file_name, $arMassTask = false) {
        if ($arMassTask && is_array($arMassTask)) {
            $massDir = storage_path() . '/app/public/postPdfTasks/' . $arMassTask['task_id'] . '/';

            if (!is_dir($massDir)) {
                mkdir(storage_path() . '/app/public/postPdfTasks/' . $arMassTask['task_id'], 0777);
            }
        }

        $tmpDir = FileToPdfUtil::getPathToTpl() . 'tmp/';
        $filepath = $tmpDir . $output_file_name;

        if ($arMassTask && is_array($arMassTask)) {
            exec("export HOME=/tmp && libreoffice --headless --invisible --norestore --convert-to pdf {$filepath} --outdir {$massDir}");
        } else {

            exec("export HOME=/tmp && libreoffice --headless --invisible --norestore --convert-to pdf {$filepath} --outdir {$tmpDir}");

            $arFilenameParts = explode(".", $output_file_name);
            $tmpPdf_filename = $arFilenameParts[0] . '.pdf';

            $content = file_get_contents($tmpDir . $tmpPdf_filename);
            header('Content-Type: application/pdf');
            header('Content-Length: ' . strlen($content));
            header('Content-disposition: inline; filename="' . $tmpPdf_filename . '"');
            header('Cache-Control: public, must-revalidate, max-age=0');
            header('Pragma: public');
            echo $content;

            if (is_file($filepath)) {
                unlink($filepath);
            }
            if (is_file($tmpDir . $tmpPdf_filename)) {
                unlink($tmpDir . $tmpPdf_filename);
            }
            
            die();
        }
    }

}
