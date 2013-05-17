<?php

/**
 * X3_File_JSON
 * 
 * @author Maxim Savin <i@soulman.kz>
 */
class X3_File_JSON extends X3_File {
    public static function fromFile($filename, $asArray = false) {
        return json_decode(file_get_contents($filename),$asArray);
    }

    public static function fromString($string, $asArray = false) {
        return json_decode($string,$asArray);
    }
}
?>
