<?php

namespace App;

use Carbon\Carbon;

/**
 * Строковые утилиты
 */
class StrUtils {

    static function hidePhone($phone) {
        return substr($phone, 0, 4) . '***' . substr($phone, 7);
    }

    static function stripWhitespaces($string) {
        $old_string = $string;
        $string = strip_tags($string);
        $string = preg_replace('/([^\pL\pN\pP\pS\pZ])|([\xC2\xA0])/u', '', $string);
        $string = str_replace('  ', ' ', $string);
        $string = trim($string);

        if ($string === $old_string) {
            return $string;
        } else {
            return StrUtils::stripWhitespaces($string);
        }
    }

    static function parseMoney($m) {
        return (int) str_replace(['.', ','], '', $m);
    }

    static function rubToKop($m) {
        $res = str_replace([','], '.', $m);
        $res = round(floatval($res) * 100);
        return $res;
    }

    static function parsePhone($str) {
        $res = preg_replace("/[^0-9]/", "", $str);
        return (strpos($res, '8') === 0) ? ('7' . substr($res, 1)) : $res;
    }

    static function beautifyPhone($str) {
        $str = StrUtils::parsePhone($str);
        return '+7(' . substr($str, 1, 3) . ')-' . substr($str, 4, 3) . '-' . substr($str, 7);
    }

    static function removeNonDigits($str) {
        return preg_replace("/[^0-9]/", "", $str);
    }

    static function kopToRub($m) {
        $m /= 100;
        $dotPos = strpos($m, '.');
        $mLength = strlen($m);
        if (!$dotPos) {
            return $m . '.00';
        } else if ($dotPos > $mLength - 3) {
            return $m . '0';
        } else if ($dotPos < $mLength - 3) {
            return substr($m, 0, $dotPos + 3);
        } else {
            return $m;
        }
    }

    /**
     * Возвращает числовое значение процентов прописью
     * @param type $num
     * @return string
     */
    static function percentsToStr($num) {
        $nul = 'ноль';
        $ten = array('', 'один ', 'два ', 'три ', 'четыре ', 'пять ', 'шесть ', 'семь ', 'восемь ', 'девять ');
        $a20 = array('десять ', 'одиннадцать ', 'двенадцать ', 'тринадцать ', 'четырнадцать ', 'пятнадцать ', 'шестнадцать ', 'семнадцать ', 'восемнадцать ', 'девятнадцать ');
        $tens = array('', '', 'двадцать ', 'тридцать ', 'сорок ', 'пятьдесят ', 'шестьдесят ', 'семьдесят ', 'восемьдесят ', 'девяносто ');
        $hundred = array('', 'сто ', 'двести ', 'триста ', 'четыреста ', 'пятьсот ', 'шестьсот ', 'семьсот ', 'восемьсот ', 'девятьсот ');
        $hundredDecimals = array('ноль ', 'сто ', 'двести ', 'триста ', 'четыреста ', 'пятьсот ', 'шестьсот ', 'семьсот ', 'восемьсот ', 'девятьсот ');
        $nums = explode('.', $num);
        $f = str_split($nums[0]);
        $str = '';
        if (count($f) == 3) {
            $str .= $hundred[$f[0]] . (($f[1] == '1') ? $a20[$f[1]] : $tens[$f[1]]) . $ten[$f[2]];
        } else if (count($f) == 2) {
            $str .= (($f[0] == '1') ? $a20[$f[0]] : $tens[$f[0]]) . $ten[$f[1]];
        } else if (count($f) == 1) {
            $str .= $ten[$f[0]];
        } else {
            $str .= $nul;
        }
        $str .= ' целых ';
        if (count($nums) > 1) {
            $f = str_split($nums[1]);
            if (count($f) == 3) {
                $str .= $hundredDecimals[$f[0]] . (($f[1] == '1') ? $a20[$f[1]] : $tens[$f[1]]) . $ten[$f[2]];
                $str .= ' тысячных ';
            } else if (count($f) == 2) {
                if ($f[0] == '0' && $f[1] == '0') {
                    $str .= 'ноль';
                } else {
//                    $str .= (($f[0] == '1') ? $a20[$f[0]] : $tens[$f[0]]) . $ten[$f[1]];
                    $str .= ($f[0] == '1') ? $a20[$f[1]] : ($tens[$f[0]] . $ten[$f[1]]);
                }
                $str .= ' сотых ';
            } else if (count($f) == 1) {
                if ($f[0] == '0') {
                    $str .= 'ноль';
                } else {
                    $str .= $ten[$f[0]];
                }
                $str .= ' десятых ';
            } else {
                $str .= $nul;
                $str .= ' сотых ';
            }
        } else {
            $str .= 'ноль тысячных';
        }
        if (strpos($str, 'целых') == 1) {
            $str = $nul . ' ' . $str;
        }
        return $str;
    }

    /**
     * Возвращает сумму прописью
     * @author runcore
     * @uses morph(...)
     */
    static function num2str($num, $addUnits = false) {
        $nul = 'ноль';
        $ten = array(
            array('', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'),
            array('', 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'),
        );
        $a20 = array('десять', 'одиннадцать', 'двенадцать', 'тринадцать', 'четырнадцать', 'пятнадцать', 'шестнадцать', 'семнадцать', 'восемнадцать', 'девятнадцать');
        $tens = array(2 => 'двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят', 'восемьдесят', 'девяносто');
        $hundred = array('', 'сто', 'двести', 'триста', 'четыреста', 'пятьсот', 'шестьсот', 'семьсот', 'восемьсот', 'девятьсот');
        $unit = array(// Units
            array('копейка', 'копейки', 'копеек', 1),
            array('рубль', 'рубля', 'рублей', 0),
            array('тысяча', 'тысячи', 'тысяч', 1),
            array('миллион', 'миллиона', 'миллионов', 0),
            array('миллиард', 'милиарда', 'миллиардов', 0),
        );
        //
        list($rub, $kop) = explode('.', sprintf("%015.2f", floatval($num)));
        $out = [];
        if (intval($rub) > 0) {
            foreach (str_split($rub, 3) as $uk => $v) { // by 3 symbols
                if (!intval($v)) {
                    continue;
                }
                $uk = sizeof($unit) - $uk - 1; // unit key
                $gender = $unit[$uk][3];
                list($i1, $i2, $i3) = array_map('intval', str_split($v, 1));
                // mega-logic
                $out[] = $hundred[$i1]; # 1xx-9xx
                if ($i2 > 1) {
                    $out[] = $tens[$i2] . ' ' . $ten[$gender][$i3];
                }# 20-99
                else {
                    $out[] = $i2 > 0 ? $a20[$i3] : $ten[$gender][$i3];
                }# 10-19 | 1-9
                // units without rub & kop
                if ($uk > 1) {
                    $out[] = StrUtils::morph($v, $unit[$uk][0], $unit[$uk][1], $unit[$uk][2]);
                }
            } //foreach
        } else {
            $out[] = $nul;
        }
        if ($addUnits) {
            $out[] = StrUtils::morph(intval($rub), $unit[1][0], $unit[1][1], $unit[1][2]); // rub
            $out[] = $kop . ' ' . StrUtils::morph($kop, $unit[0][0], $unit[0][1], $unit[0][2]); // kop
        }
        return trim(preg_replace('/ {2,}/', ' ', join(' ', $out)));
    }

    /**
     * Склоняем словоформу
     * @ author runcore
     */
    static function morph($n, $f1, $f2, $f5) {
        $n = abs(intval($n)) % 100;
        if ($n > 10 && $n < 20)
            return $f5;
        $n = $n % 10;
        if ($n > 1 && $n < 5)
            return $f2;
        if ($n == 1)
            return $f1;
        return $f5;
    }

    static function addSpaces($str, $num) {
        $len = strlen($str);
        if ($len < $num) {
            for ($i = $len; $i < $num; $i++) {
                $str.=' ';
            }
        }
        return $str;
    }

    static function addChars($str, $num, $char = ' ', $inTheEnd = false) {
        $len = strlen($str);
        if ($len < $num) {
            for ($i = $len; $i < $num; $i++) {
                if ($inTheEnd) {
                    $str.=$char;
                } else {
                    $str = $char . $str;
                }
            }
        }
        return $str;
    }

    static function parseDate($str) {
        return with(new Carbon(str_replace(',', '.', $str)))->format('Y-m-d H:i:s');
    }

    static function dateToStr($str) {
        $arMonths = [
            '01' => 'января',
            '02' => 'февраля',
            '03' => 'марта',
            '04' => 'апреля',
            '05' => 'мая',
            '06' => 'июня',
            '07' => 'июля',
            '08' => 'августа',
            '09' => 'сентября',
            '10' => 'октября',
            '11' => 'ноября',
            '12' => 'декабря'
        ];

        $date = with(new Carbon($str))->format('d.m.Y');

        $arDate = explode('.', $date);

        return $arDate[0] . ' ' . $arMonths[$arDate[1]] . ' ' . $arDate[2] . ' г.';
    }

    static function sumToRubAndKop($value) {
        $arVal = explode('.', $value);
        return $arVal[0] . ' руб. ' . ((count($arVal) > 1) ? $arVal[1] : '00') . ' коп.';
    }

    static function getMonthNames() {
        return [
            'Январь',
            'Февраль',
            'Март',
            'Апрель',
            'Май',
            'Июнь',
            'Июль',
            'Август',
            'Сентябрь',
            'Октябрь',
            'Ноябрь',
            'Декабрь'
        ];
    }

    static function translit($string, $gost = false) {
        if ($gost) {
            $replace = array("А" => "A", "а" => "a", "Б" => "B", "б" => "b", "В" => "V", "в" => "v", "Г" => "G", "г" => "g", "Д" => "D", "д" => "d",
                "Е" => "E", "е" => "e", "Ё" => "E", "ё" => "e", "Ж" => "Zh", "ж" => "zh", "З" => "Z", "з" => "z", "И" => "I", "и" => "i",
                "Й" => "I", "й" => "i", "К" => "K", "к" => "k", "Л" => "L", "л" => "l", "М" => "M", "м" => "m", "Н" => "N", "н" => "n", "О" => "O", "о" => "o",
                "П" => "P", "п" => "p", "Р" => "R", "р" => "r", "С" => "S", "с" => "s", "Т" => "T", "т" => "t", "У" => "U", "у" => "u", "Ф" => "F", "ф" => "f",
                "Х" => "Kh", "х" => "kh", "Ц" => "Tc", "ц" => "tc", "Ч" => "Ch", "ч" => "ch", "Ш" => "Sh", "ш" => "sh", "Щ" => "Shch", "щ" => "shch",
                "Ы" => "Y", "ы" => "y", "Э" => "E", "э" => "e", "Ю" => "Iu", "ю" => "iu", "Я" => "Ia", "я" => "ia", "ъ" => "", "ь" => "");
        } else {
            $arStrES = array("ае", "уе", "ое", "ые", "ие", "эе", "яе", "юе", "ёе", "ее", "ье", "ъе", "ый", "ий");
            $arStrOS = array("аё", "уё", "оё", "ыё", "иё", "эё", "яё", "юё", "ёё", "её", "ьё", "ъё", "ый", "ий");
            $arStrRS = array("а$", "у$", "о$", "ы$", "и$", "э$", "я$", "ю$", "ё$", "е$", "ь$", "ъ$", "@", "@");

            $replace = array("А" => "A", "а" => "a", "Б" => "B", "б" => "b", "В" => "V", "в" => "v", "Г" => "G", "г" => "g", "Д" => "D", "д" => "d",
                "Е" => "Ye", "е" => "e", "Ё" => "Ye", "ё" => "e", "Ж" => "Zh", "ж" => "zh", "З" => "Z", "з" => "z", "И" => "I", "и" => "i",
                "Й" => "Y", "й" => "y", "К" => "K", "к" => "k", "Л" => "L", "л" => "l", "М" => "M", "м" => "m", "Н" => "N", "н" => "n",
                "О" => "O", "о" => "o", "П" => "P", "п" => "p", "Р" => "R", "р" => "r", "С" => "S", "с" => "s", "Т" => "T", "т" => "t",
                "У" => "U", "у" => "u", "Ф" => "F", "ф" => "f", "Х" => "Kh", "х" => "kh", "Ц" => "Ts", "ц" => "ts", "Ч" => "Ch", "ч" => "ch",
                "Ш" => "Sh", "ш" => "sh", "Щ" => "Shch", "щ" => "shch", "Ъ" => "", "ъ" => "", "Ы" => "Y", "ы" => "y", "Ь" => "", "ь" => "",
                "Э" => "E", "э" => "e", "Ю" => "Yu", "ю" => "yu", "Я" => "Ya", "я" => "ya", "@" => "y", "$" => "ye");

            $string = str_replace($arStrES, $arStrRS, $string);
            $string = str_replace($arStrOS, $arStrRS, $string);
        }

        return iconv("UTF-8", "UTF-8//IGNORE", strtr($string, $replace));
    }
    /**
     * Использовалась для генерации адресов почты
     * @param type $string
     * @return type
     */
    static function translit2($string) {
        $roman = array("Sch", "sch", 'Yo', 'Zh', 'Kh', 'Ts', 'Ch', 'Sh', 'Yu', 'ya', 'yo', 'zh', 'kh', 'ts', 'ch', 'sh', 'yu', 'ya', 'A', 'B', 'V', 'G', 'D', 'E', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', '', 'Y', '', 'E', 'a', 'b', 'v', 'g', 'd', 'e', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', '', 'y', '', 'e');
        $cyrillic = array("Щ", "щ", 'Ё', 'Ж', 'Х', 'Ц', 'Ч', 'Ш', 'Ю', 'Я', 'ё', 'ж', 'х', 'ц', 'ч', 'ш', 'ю', 'я', 'А', 'Б', 'В', 'Г', 'Д', 'Е', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Ь', 'Ы', 'Ъ', 'Э', 'а', 'б', 'в', 'г', 'д', 'е', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'ь', 'ы', 'ъ', 'э');
        return str_replace($cyrillic, $roman, $string);
    }
    /**
     * Генерация случайного пароля
     * @return type
     */
    static function randomPassword($strcount=8) {
        $alphabet = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ123456789';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < $strcount; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }

}
