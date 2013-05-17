<?php

abstract class X3_File extends X3_Component {
    abstract public static function fromFile($filename);
    abstract public static function fromString($string);
}
?>
