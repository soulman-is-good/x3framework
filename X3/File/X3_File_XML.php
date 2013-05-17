<?php

/**
 * X3_File_XML
 * 
 * @author Maxim Savin <i@soulman.kz>
 */
class X3_File_XML extends X3_File {
    public static function fromFile($filename) {
        return simplexml_load_file($filename);
    }

    public static function fromString($string) {
        return simplexml_load_string($string);
    }
}
?>
