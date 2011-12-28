<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author Soul_man
 */
interface X3_Interface_Controller {
    /**
     * defines array of allowed role-actions
     */
    public function filter();
    /**
     * returns array of routes for not found actions or uri
     */
    public function route();
    /**
     * defines array of cached actions
     */
    public function cache();
}
?>
