<?php

namespace App\Utils;

use App\Claim;

class HtmlHelper {

    static function Buttton($url, $params = null) {
        $restrictedParams = ['class', 'blockScreen', 'disabled', 'onclick', 'glyph', 'text'];
        $html = '';
        $html .= (is_null($url) || (array_key_exists('disabled', $params) && $params['disabled'])) ? '<button ' : ('<a href="' . $url . '" ');
        if (is_null($params)) {
            $params = [];
        }
        if (!is_null($url) && !array_key_exists('blockScreen', $params)) {
            $params['blockScreen'] = true;
        }

        if (!array_key_exists('class', $params)) {
            $params['class'] = 'btn btn-default';
        }
        if (array_key_exists('size', $params)) {
            $params['class'].=' btn-' . $params['size'];
        }
        $html .= ' class="' . $params['class'] . '" ';

        if (array_key_exists('blockScreen', $params) && $params['blockScreen']) {
            if (array_key_exists('onclick', $params)) {
                $html .= 'onclick="$.app.blockScreen(true); ' . $params['onclick'] . '"';
            }
        } else if (array_key_exists('onclick', $params)) {
            $html .= 'onclick="' . $params['onclick'] . '"';
        }

        if (array_key_exists('disabled', $params) && $params['disabled']) {
            $html .= ' disabled ';
        }
        foreach ($params as $k => $v) {
            if (!in_array($k, $restrictedParams)) {
                $html .= ' ' . $k . '="' . $v . '" ';
            }
        }
        $html .='>';
        if (array_key_exists('glyph', $params)) {
            $html .= '<span class="glyphicon glyphicon-' . $params['glyph'] . '"></span>';
        }
        if (array_key_exists('text', $params)) {
            $html .= $params['text'];
        }
        $html .= (!is_null($url)) ? '</a>' : '</button>';
        return $html;
    }

    static function Label($text, $params = null) {
        $html = '<span ';
        $class = ' class="label ';
        if (!is_null($params)) {
            foreach ($params as $k => $v) {
                if ($k == 'class') {
                    $class .= $v;
                } else {
                    $html.= ' ' . $k . '="' . $v . '" ';
                }
            }
        }
        return $html . $class . '"' . '>' . $text . '</span>';
    }

    static function StatusLabel($status) {
        switch ($status) {
            case Claim::STATUS_NEW:
                $res = '<span class="label label-default">Новая';
                break;
            case Claim::STATUS_ONCHECK:
                $res = '<span class="label label-warning">На проверке';
                break;
            case Claim::STATUS_ONEDIT:
                $res = '<span class="label label-primary">Поправить!';
                break;
            case Claim::STATUS_DECLINED:
                $res = '<span class="label label-danger">Отказано';
                break;
            case Claim::STATUS_ACCEPTED:
                $res = '<span class="label label-success">Одобрено';
                break;
            case Claim::STATUS_CREDITSTORY:
                $res = '<span class="label label-info">Исправление КИ';
                break;
            default:
                $res = '<span class="label label-info">' . Claim::getStatusName($status);
                break;
        }
        $res .= '</span>';
        return $res;
    }

    static function DropDown($text = null, $items = null, $params = null) {
        $html = '<div ';
        $class = ' class="btn-group btn-group-sm remove-dropdown ';
        if (!is_null($params)) {
            foreach ($params as $k => $v) {
                if ($k == 'class') {
                    $class .= $v;
                } else {
                    $html.= ' ' . $k . '="' . $v . '" ';
                }
            }
        }
        $html .= $class . '"';
        $html .= '><button type="button" class="btn btn-default dropdown-toggle" 
                            data-toggle="dropdown">' . $text . ' <span class="caret"></span></button>
                            <ul class="dropdown-menu" role="menu">';
        if (!is_null($items)) {
            foreach ($items as $item) {
                $html .= $item;
            }
        }
        $html .= '</ul></div>';
        return $html;
    }

    static function DropDownItem($text = null, $params = null) {
        $html = '<li>';
        if (!is_null($params)) {
            if (array_key_exists('href', $params)) {
                $html .= '<a ';
                $closeTag = '</a>';
            } else {
                $html .= '<button ';
                $closeTag = '</button>';
            }
            foreach ($params as $k => $v) {
                $html.= ' ' . $k . '="' . $v . '" ';
            }
            $html .= '>';
        }
        return $html . $text . '</li>';
    }

    static function OpenDropDown($btnHtml) {
        $html = '<div class="btn-group btn-group-sm">';
        $html .= $btnHtml;
        $html.= '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                        <span class="caret"></span>
                                        <span class="sr-only">Меню с переключением</span>
                                    </button>
                                    <ul class="dropdown-menu" role="menu">';
        return $html;
    }

    static function CloseDropDown() {
        return '</ul></div>';
    }
    /**
     * Получает массив годов, для заполнения селектов
     * @param int $min минимальный год
     * @param int $max максимальный год
     * @return type
     */
    static function GetYearSelectData($min = 2012, $max = null) {
        if (is_null($max)) {
            $max = \Carbon\Carbon::now()->year;
        }
        if ($min > $max) {
            $min = $max;
        }
        $res = [];
        for ($i = $min; $i < $max + 1; $i++) {
            $res[$i] = $i;
        }
        return $res;
    }
    /**
     * Получает массив месяцев для заполнения селектов
     * @param boolean $inNames словами или цифрами
     * @return type
     */
    static function GetMonthSelectData($inNames = true) {
        $res = [];
        $mNames = ['Январь', 'Февраль', "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"];
        for ($i = 0; $i < 12; $i++) {
            $m = $i + 1;
            $res[$m] = ($inNames)?$mNames[$i]:$m;
        }
        return $res;
    }

}
