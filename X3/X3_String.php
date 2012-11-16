<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * X3_String class
 * We created this calss for quick editing and converting string.
 * For example translit convertion or dmstring - sound ex realization
 * 
 * great thanx to <provocateur> http://habrahabr.ru/users/provocateur/
 * for the php realization of Daitch-Mokotoff algorithm
 *
 * @author Soul_man
 */
class X3_String extends X3_Component {

    private $string = '';
    private $encoding = 'UTF-8';
    private $length = false;

    public function __construct($string, $encoding = null) {
        $this->string = (string) $string;
        if ($encoding != null)
            $this->encoding = $encoding;
        elseif (isset(X3::app()->encoding)) {
            $this->encoding = X3::app()->encoding;
        }
    }

    public static function create($string, $encoding = null) {
        return new self($string, $encoding);
    }

    /**
     * 
     * @param string $string the whole string to cut
     * @param integer $count how many symbols to cut
     * @param mixed $find array or string - symbol(s) to orientate for cutting. the order in array means priority
     * @param string $endsWith cutted string will end with this symbol
     * @param bool $force force cutting if there no symbols found
     * @return X3_String returns new instance of cutted string
     */
    public static function cut($string, $count = 255, $find = array('.', ';', "\n", ' '), $endsWith = '&hellip;', $force = false) {
        return self::create($string)->carefullCut($count, $find, $endsWith, $force);
    }

    public function getLength() {
        if ($this->length === false)
            $this->length = mb_strlen($this->string, $this->encoding);
        return $this->length;
    }

    public function set($string) {
        $this->string = (string) $string;
        return $this;
    }

    private function dmword($string, $is_cyrillic = true) {
        static $codes = array(
    'A' => array(array(0, -1, -1),
        'I' => array(array(0, 1, -1)),
        'J' => array(array(0, 1, -1)),
        'Y' => array(array(0, 1, -1)),
        'U' => array(array(0, 7, -1))),
    'B' => array(array(7, 7, 7)),
    'C' => array(array(5, 5, 5), array(4, 4, 4),
        'Z' => array(array(4, 4, 4),
            'S' => array(array(4, 4, 4))),
        'S' => array(array(4, 4, 4),
            'Z' => array(array(4, 4, 4))),
        'K' => array(array(5, 5, 5), array(45, 45, 45)),
        'H' => array(array(5, 5, 5), array(4, 4, 4),
            'S' => array(array(5, 54, 54)))),
    'D' => array(array(3, 3, 3),
        'T' => array(array(3, 3, 3)),
        'Z' => array(array(4, 4, 4),
            'H' => array(array(4, 4, 4)),
            'S' => array(array(4, 4, 4))),
        'S' => array(array(4, 4, 4),
            'H' => array(array(4, 4, 4)),
            'Z' => array(array(4, 4, 4))),
        'R' => array(
            'S' => array(array(4, 4, 4)),
            'Z' => array(array(4, 4, 4)))),
    'E' => array(array(0, -1, -1),
        'I' => array(array(0, 1, -1)),
        'J' => array(array(0, 1, -1)),
        'Y' => array(array(0, 1, -1)),
        'U' => array(array(1, 1, -1))),
    'F' => array(array(7, 7, 7),
        'B' => array(array(7, 7, 7))),
    'G' => array(array(5, 5, 5)),
    'H' => array(array(5, 5, -1)),
    'I' => array(array(0, -1, -1),
        'A' => array(array(1, -1, -1)),
        'E' => array(array(1, -1, -1)),
        'O' => array(array(1, -1, -1)),
        'U' => array(array(1, -1, -1))),
    'J' => array(array(4, 4, 4)),
    'K' => array(array(5, 5, 5),
        'H' => array(array(5, 5, 5)),
        'S' => array(array(5, 54, 54))),
    'L' => array(array(8, 8, 8)),
    'M' => array(array(6, 6, 6),
        'N' => array(array(66, 66, 66))),
    'N' => array(array(6, 6, 6),
        'M' => array(array(66, 66, 66))),
    'O' => array(array(0, -1, -1),
        'I' => array(array(0, 1, -1)),
        'J' => array(array(0, 1, -1)),
        'Y' => array(array(0, 1, -1))),
    'P' => array(array(7, 7, 7),
        'F' => array(array(7, 7, 7)),
        'H' => array(array(7, 7, 7))),
    'Q' => array(array(5, 5, 5)),
    'R' => array(array(9, 9, 9),
        'Z' => array(array(94, 94, 94), array(94, 94, 94)), // special case
        'S' => array(array(94, 94, 94), array(94, 94, 94))), // special case
    'S' => array(array(4, 4, 4),
        'Z' => array(array(4, 4, 4),
            'T' => array(array(2, 43, 43)),
            'C' => array(
                'Z' => array(array(2, 4, 4)),
                'S' => array(array(2, 4, 4))),
            'D' => array(array(2, 43, 43))),
        'D' => array(array(2, 43, 43)),
        'T' => array(array(2, 43, 43),
            'R' => array(
                'Z' => array(array(2, 4, 4)),
                'S' => array(array(2, 4, 4))),
            'C' => array(
                'H' => array(array(2, 4, 4))),
            'S' => array(
                'H' => array(array(2, 4, 4)),
                'C' => array(
                    'H' => array(array(2, 4, 4))))),
        'C' => array(array(2, 4, 4),
            'H' => array(array(4, 4, 4),
                'T' => array(array(2, 43, 43),
                    'S' => array(
                        'C' => array(
                            'H' => array(array(2, 4, 4))),
                        'H' => array(array(2, 4, 4))),
                    'C' => array(
                        'H' => array(array(2, 4, 4)))),
                'D' => array(array(2, 43, 43)))),
        'H' => array(array(4, 4, 4),
            'T' => array(array(2, 43, 43),
                'C' => array(
                    'H' => array(array(2, 4, 4))),
                'S' => array(
                    'H' => array(array(2, 4, 4)))),
            'C' => array(
                'H' => array(array(2, 4, 4))),
            'D' => array(array(2, 43, 43)))),
    'T' => array(array(3, 3, 3),
        'C' => array(array(4, 4, 4),
            'H' => array(array(4, 4, 4))),
        'Z' => array(array(4, 4, 4),
            'S' => array(array(4, 4, 4))),
        'S' => array(array(4, 4, 4),
            'Z' => array(array(4, 4, 4)),
            'H' => array(array(4, 4, 4)),
            'C' => array(
                'H' => array(array(4, 4, 4)))),
        'T' => array(
            'S' => array(array(4, 4, 4),
                'Z' => array(array(4, 4, 4)),
                'C' => array(
                    'H' => array(array(4, 4, 4)))),
            'C' => array(
                'H' => array(array(4, 4, 4))),
            'Z' => array(array(4, 4, 4))),
        'H' => array(array(3, 3, 3)),
        'R' => array(
            'Z' => array(array(4, 4, 4)),
            'S' => array(array(4, 4, 4)))),
    'U' => array(array(0, -1, -1),
        'E' => array(array(0, -1, -1)),
        'I' => array(array(0, 1, -1)),
        'J' => array(array(0, 1, -1)),
        'Y' => array(array(0, 1, -1))),
    'V' => array(array(7, 7, 7)),
    'W' => array(array(7, 7, 7)),
    'X' => array(array(5, 54, 54)),
    'Y' => array(array(1, -1, -1)),
    'Z' => array(array(4, 4, 4),
        'D' => array(array(2, 43, 43),
            'Z' => array(array(2, 4, 4),
                'H' => array(array(2, 4, 4)))),
        'H' => array(array(4, 4, 4),
            'D' => array(array(2, 43, 43),
                'Z' => array(
                    'H' => array(array(2, 4, 4))))),
        'S' => array(array(4, 4, 4),
            'H' => array(array(4, 4, 4)),
            'C' => array(
                'H' => array(array(4, 4, 4))))));
        $length = strlen($string);
        $output = '';
        $i = 0;
        $previous = -1;
        while ($i < $length) {
            $current = $last = &$codes[$string[$i]];
            for ($j = $k = 1; $k < 7; $k++) {
                if (!isset($string[$i + $k]) || !isset($current[$string[$i + $k]]))
                    break;
                $current = &$current[$string[$i + $k]];
                if (isset($current[0])) {
                    $last = &$current;
                    $j = $k + 1;
                }
            }
            if ($i == 0)
                $code = $last[0][0];
            elseif (!isset($string[$i + $j]) || ($codes[$string[$i + $j]][0][0] != 0))
                $code = $is_cyrillic ? (isset($last[1]) ? $last[1][2] : $last[0][2]) : $last[0][2];
            else
                $code = $is_cyrillic ? (isset($last[1]) ? $last[1][1] : $last[0][1]) : $last[0][1];
            if (($code != -1) && ($code != $previous))
                $output .= $code;
            $previous = $code;
            $i += $j;
        }
        return str_pad(substr($output, 0, 6), 6, '0');
    }

    public function dmstring() {
        $is_cyrillic = false;
        $string = $this->string;
        if (preg_match('/[А-Яа-я]/iu', $string) === 1) {
            $string = $this->translit($string);
            $is_cyrillic = true;
        }
        $string = preg_replace(array('/[^\w\s]|\d/iu', '/\b[^\s]{1,3}\b/iu', '/\s{2,}/iu', '/^\s+|\s+$/iu'), array('', '', ' '), strtoupper($string));
        if (!isset($string[0]))
            return null;
        $matches = explode(' ', $string);
        foreach ($matches as $key => $match)
            $matches[$key] = $this->dmword($match, $is_cyrillic);
        return implode('_', $matches);
    }

    public function numeral($number, $cases) {
        $case = array(2, 0, 1, 1, 1, 2);
        $this->string = sprintf($cases[($number % 100 > 4 && $number % 100 < 20) ? 2 : $case[min($number % 10, 5)]], $number);
        return $this;
    }

    public function lcfirst() {
        if ($this->getLength() > 0)
            $this->string[0] = strtolower($this->string[0]);
        return $this;
    }

    public function translit($backward = false, $stripchars = array()) {
        static $ru = array(
    'А', 'а', 'Б', 'б', 'В', 'в', 'Г', 'г', 'Д', 'д', 'Е', 'е',
    'Ё', 'ё', 'Ж', 'ж', 'З', 'з', 'И', 'и', 'Й', 'й', 'К', 'к',
    'Л', 'л', 'М', 'м', 'Н', 'н', 'О', 'о', 'П', 'п', 'Р', 'р',
    'С', 'с', 'Т', 'т', 'У', 'у', 'Ф', 'ф', 'Х', 'х', 'Ц', 'ц',
    'Ч', 'ч', 'Ш', 'ш', 'Щ', 'щ', 'Ъ', 'ъ', 'Ы', 'ы', 'Ь', 'ь',
    'Э', 'э', 'Ю', 'ю', 'Я', 'я'
        );
        static $en = array(
    'A', 'a', 'B', 'b', 'V', 'v', 'G', 'g', 'D', 'd', 'E', 'e',
    'E', 'e', 'Zh', 'zh', 'Z', 'z', 'I', 'i', 'J', 'j', 'K', 'k',
    'L', 'l', 'M', 'm', 'N', 'n', 'O', 'o', 'P', 'p', 'R', 'r',
    'S', 's', 'T', 't', 'U', 'u', 'F', 'f', 'H', 'h', 'C', 'c',
    'Ch', 'ch', 'Sh', 'sh', 'Sch', 'sch', '\'', '\'', 'Y', 'y', '\'', '\'',
    'E', 'e', 'Ju', 'ju', 'Ja', 'ja'
        );
        if (is_string($stripchars))
            $stripchars = array($stripchars);
        if ($backward)
            $string = str_replace($en, $ru, $this->string);
        else
            $string = str_replace($ru, $en, $this->string);
        foreach ($stripchars as $char) {
            $string = str_replace($char, '', $string);
        }
        return str_replace(' ', '_', $string);
    }

    /**
     * 
     * Converts price to currency format
     * 
     * @return string formatted string
     */
    public function currency($with_cent = false, $round = 2) {
        $price = str_replace(',', '.', $this->string);
        $whole_price = floor($price);
        $l = mb_strlen($whole_price, $this->encoding);
        for ($i = 0; $i < $l; $i++)
            $price_array[] = mb_substr($whole_price, $i, 1, $this->encoding);

        $new_price = '';
        for ($i = ceil($l / 3) - 1; $i >= 0; $i--) {
            $new_price .= (($i == ceil($l / 3 - 1) ? '' : ' ')) . $price_array[$l - $i * 3 - 3] . $price_array[$l - $i * 3 - 2] . $price_array[$l - $i * 3 - 1];
        }

        $double_price = explode('.', $price);
        $double_price = round('0.' . $double_price[1], $round);
        $double_price = substr((string) $double_price, 1);
        $str = (!$with_cent) ? $new_price : ($new_price . $double_price);
        if (!$with_cent)
            $str = str_replace('.' . str_repeat('0', $round), '', $str);
        return $str;
    }

    /**
     * Checks if there is no protocol prefix then add it
     * @param string $protocol protocol name
     * @return string
     */
    public function check_protocol($protocol = 'http', $checkonly = true) {
        $url = trim($this->string);
        $l = mb_strlen($protocol, $this->encoding) + 3;
        if($checkonly)
            return (mb_substr($url, 0, $l, $this->encoding) == "$protocol://") ? true : false;
        else
            return (mb_substr($url, 0, $l, $this->encoding) == "$protocol://" || empty($url)) ? $url : ("$protocol://" . $url);
    }

    public function strip_regexp() {
        return stripslashes(trim($this->string, '/#~$^'));
    }

    /**
     * 
     * @param type $needle
     * @param integer $type 0 - strpos 1 - strrpos 2 - stripos 3 - strripos
     * @return mixed first found needle position false otherwise
     */
    public function strpos($needle, $type = 0, $offset = 0) {
        $haystack = $this->string;
        if (!is_array($needle))
            $needle = array($needle);
        switch ($type) {
            case 0:
                $func = "mb_strpos";
                break;
            case 1:
                $func = "mb_strrpos";
                break;
            case 2:
                $func = "mb_stripos";
                break;
            case 3:
                $func = "mb_strripos";
                break;
            default:
                $func = "mb_strpos";
        }
        foreach ($needle as $what) {
            if (($pos = call_user_func_array($func, array($haystack, $what, $offset, $this->encoding))) !== false)
                return $pos;
        }
        return false;
    }

    /**
     * 
     * @param integer $count how many symbols to cut
     * @param mixed $find array or string - symbol(s) to orientate for cutting. the order in array means priority
     * @param string $endsWith cutted string will end with this symbol
     * @param bool $force force cutting if there no symbols found
     * @return X3_String returns new instance of cutted string
     */
    public function carefullCut($count = 255, $find = array('.', ';', "\n", ' '), $endsWith = '&hellip;', $force = false) {
        $text = self::create(mb_substr($this->string, 0, $count, $this->encoding));

        if (($this->getLength() <= $count) || (!$force && $text->strpos($find, 1) === false))
            return $text;
        else {
            $text->set(mb_substr($text->string, 0, $text->strpos($find, 1,1), $this->encoding));
        }
        return $text->set($text->string . $endsWith);
    }

    /**
     * Searches and replaces needed string
     * 
     * @param string $search search string
     * @param string $replace replace string
     * @return string
     */
    public function replace($search, $replace) {
        $subject = $this->string;
        foreach ((array) $search as $key => $s) {
            if ($s == '') {
                continue;
            }
            $r = !is_array($replace) ? $replace : (array_key_exists($key, $replace) ? $replace[$key] : '');
            $pos = $this->strpos($s);
            while ($pos !== false) {
                $subject = mb_substr($subject, 0, $pos, $this->encoding) . $r . mb_substr($subject, $pos + mb_strlen($s, $this->encoding), 65535, $this->encoding);
                $pos = $this->strpos($s, $pos + mb_strlen($r, $this->encoding));
            }
        }
        return $subject;
    }

    public function trim($chars = "", $chars_array = array()) {
        $string = $this->string;
        for ($x = 0; $x < iconv_strlen($chars, $this->encoding); $x++)
            $chars_array[] = preg_quote(iconv_substr($chars, $x, 1, $this->encoding));
        $encoded_char_list = implode("|", array_merge(array("\s", "\t", "\n", "\r", "\0", "\x0B"), $chars_array));

        $string = preg_replace("~^($encoded_char_list)*~", "", $string);
        $string = preg_replace("~($encoded_char_list)*$~", "", $string);
        return $string;
    }
    
    public function toLowerCase() {
        if(extension_loaded('mbstring'))
            return mb_strtolower($this->string,$this->encoding);
        return strtolower($this->string);
    }
    
    public function toUpperCase() {
        if(extension_loaded('mbstring'))
            return mb_strtoupper($this->string,$this->encoding);
        return strtoupper($this->string);
    }

    public function __toString() {
        return (string) $this->string;
    }

}


if(!function_exists('mb_strpos')){
    function mb_strpos($haystack,$needle,$offset=0,$encoding='UTF-8'){
        return strpos($haystack, $needle,$offset);
    }
}

if(!function_exists('mb_substr')){
    function mb_substr($string, $start, $length=null,$encoding = 'UTF-8'){
        return substr($string, $start, $length);
    }
}
