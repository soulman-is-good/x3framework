<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of X3_log
 *
 * @author Soul_man
 */
class X3_Log extends X3_Component {
    private $category = array('*');

    public function __construct($category) {
        $this->category = explode(',',$category);
        foreach ($this->category as &$value) {
            $value = trim($value);
        }
    }

    public function getCategory() {
        return $this->category;
    }

    public function processLog($msg,$category='*') {
        
    }
}
?>
