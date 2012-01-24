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
        $this->string = $string;
    }

    public static function create($string){
        return new self($string);
    }

    public function numeral($number, $cases) {
        $case = array(2, 0, 1, 1, 1, 2);
        $this->string = sprintf($cases[ ($number%100>4 && $number%100<20)? 2 : $case[min($number%10, 5)] ], $number);
        return $this;
    }

    public function __toString() {
        return $this->string;
    }
}
?>
