<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * X3_Interface_Session
 *
 * Session functions to override
 *
 * @author Soul_man
 */
interface X3_Interface_Session {
    
    /**
     * Initializes session, and else
     */
    public function open();

    /**
     * Closes session
     */
    public function close();

    /**
     * Reads session data by id
     * @param mixed $sid session id
     * @return mixed session data
     */
    public function read($sid);
    public function readOnce($key);
    public function readAjax($key);

    /**
     * Writes session data by id
     * @param mixed $sid session id
     * @param mixed $data value to acuire
     */
    public function write($sid, $data);
    public function writeOnce($key, $value);
    public function writeAjax($key, $value);

    /**
     * Destroys session by id
     * @param mixed $sid session id
     */
    public function destroy($sid);

    /**
     * Cleans a session if expired
     * @param int $expire timestamp
     */
    public function clean($expire);
}
?>
