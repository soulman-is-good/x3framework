<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author Soul_man
 */
interface X3_Interface_Query {
    public function select($query);

    public function limit($query);

    public function offset($query);

    public function order($query);

    public function group($query);

    public function page($query);

    public function where($query);

    public function update($field,$value = null);

    /**
     *
     * @param array $fields e.g. array('title','date','text')
     * @param array $values e.g. array('hello','9.07.2011','world')
     */
    public function insert($fields,$values=null);

    public function delete();

    public function  render($view, $data = null, $return = false);

    public function asArray($single = false);

    public function asObject($single = false);

    public function asJSON($single = false);

    public function asNumeric();

    public function count();

    public function execute();
}
?>
