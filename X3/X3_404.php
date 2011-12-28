<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * X3_404
 *
 * @author soulman
 *
 * 22.11.2010 23:00:08
 */
class X3_404 extends X3_Exception {

    public function __construct() {
        parent::__construct(X3::translate('Страница не найдена.'), 404);
    }
}
?>
