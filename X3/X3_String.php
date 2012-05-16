<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of X3_String
 *
 * @author Soul_man
 */
class X3_String extends X3_Component {
    private $string = '';

    public function __construct($string) {
        $this->string = (string)$string;
    }

    public static function create($string){
        return new self($string);
    }

    public function numeral($number, $cases) {
        $case = array(2, 0, 1, 1, 1, 2);
        $this->string = sprintf($cases[ ($number%100>4 && $number%100<20)? 2 : $case[min($number%10, 5)] ], $number);
        return $this;
    }
    
    public function lcfirst() {
        $this->string[0] = strtolower($this->string[0]);
        return $this;
    }

    public function translit() {
        $tr = array(
            "Ґ"=>"G","Ё"=>"YO","Є"=>"E","Ї"=>"YI","І"=>"I",
            "і"=>"i","ґ"=>"g","ё"=>"yo","№"=>"#","є"=>"e",
            "ї"=>"yi","А"=>"A","Б"=>"B","В"=>"V","Г"=>"G",
            "Д"=>"D","Е"=>"E","Ж"=>"ZH","З"=>"Z","И"=>"I",
            "Й"=>"Y","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N",
            "О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T",
            "У"=>"U","Ф"=>"F","Х"=>"H","Ц"=>"TS","Ч"=>"CH",
            "Ш"=>"SH","Щ"=>"SCH","Ъ"=>"'","Ы"=>"YI","Ь"=>"",
            "Э"=>"E","Ю"=>"YU","Я"=>"YA","а"=>"a","б"=>"b",
            "в"=>"v","г"=>"g","д"=>"d","е"=>"e","ж"=>"zh",
            "з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
            "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
            "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
            "ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"'",
            "ы"=>"yi","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya"
        );
        $st = strtr($this->string, $tr);
        return str_replace(' ', '_', $st);
    }
    
    public function __toString() {        
        return (string)$this->string;
    }
}
?>
