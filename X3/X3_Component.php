<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * X3_Component
 *
 * @author soulman
 *
 * 21.11.2010 1:44:28
 */
class X3_Component {

    private static $_e;
    private static $_m;

    public function __construct($params = array()) {
        if (is_array($params))
            foreach ($params as $param => $value) {
                if ($param != '_e' && $param != '_m' && property_exists($this, $param))
                    $this->$param = $value;
                else
                    X3::log("Wrong parameter passed '$param' in '" . get_class($this) . "'");
            }
    }

    public function init() {
        
    }

    /**
     * Returns a property value, an event handler list or a behavior based on its name.
     * Do not call this method. This is a PHP magic method that we override
     * to allow using the following syntax to read a property or obtain event handlers:
     * <pre>
     * $value=$component->propertyName;
     * $handlers=$component->eventName;
     * </pre>
     * @param string the property name or event name
     * @return mixed the property value, event handlers attached to the event, or the named behavior (since version 1.0.2)
     * @throws CException if the property or event is not defined
     * @see __set
     */
    public function __get($name) {
        $getter = 'get' . $name;
        if (method_exists($this, $getter))
            return $this->$getter();
        else if (strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name)) {
            // duplicating getEventHandlers() here for performance
            $name = strtolower($name);
            if (!isset(self::$_e[$name]))
                self::$_e[$name] = null;
            return self::$_e[$name];
        }
        throw new X3_Exception('Property "' . get_class($this) . '.' . $name . '" is not defined.');
    }

    /**
     * Sets value of a component property.
     * Do not call this method. This is a PHP magic method that we override
     * to allow using the following syntax to set a property or attach an event handler
     * <pre>
     * $this->propertyName=$value;
     * $this->eventName=$callback;
     * </pre>
     * @param string the property name or the event name
     * @param mixed the property value or callback
     * @throws CException if the property/event is not defined or the property is read only.
     * @see __get
     */
    public function __set($name, $value) {
        $setter = 'set' . $name;
        if (method_exists($this, $setter))
            return $this->$setter($value);
        else if (strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name)) {
            // duplicating getEventHandlers() here for performance
            $name = strtolower($name);
            if (!isset($this->_e[$name]))
                $this->_e[$name] = $value;
            return $this->_e[$name];
        }
        else if (is_array($this->_m)) {
            foreach ($this->_m as $object) {
                if ($object->getEnabled() && (property_exists($object, $name) || $object->canSetProperty($name)))
                    return $object->$name = $value;
            }
        }
        if (method_exists($this, 'get' . $name))
            throw new X3_Exception('Свойство "' . get_class($this) . '.' . $name . '" только для чтения.');
        else
            throw new X3_Exception('Свойство "' . get_class($this) . '.' . $name . '" неопределено.');
    }

    /**
     * Checks if a property value is null.
     * Do not call this method. This is a PHP magic method that we override
     * to allow using isset() to detect if a component property is set or not.
     * @param string the property name or the event name
     * @since 1.0.1
     */
    public function __isset($name) {
        $getter = 'get' . $name;
        if (method_exists($this, $getter))
            return $this->$getter() !== null;
        else if (strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name)) {
            $name = strtolower($name);
            return isset($this->_e[$name]) && $this->_e[$name]->getCount();
        } else if (is_array($this->_m)) {
            if (isset($this->_m[$name]))
                return true;
            foreach ($this->_m as $object) {
                if ($object->getEnabled() && (property_exists($object, $name) || $object->canGetProperty($name)))
                    return true;
            }
        }
        return false;
    }

    /**
     * Sets a component property to be null.
     * Do not call this method. This is a PHP magic method that we override
     * to allow using unset() to set a component property to be null.
     * @param string the property name or the event name
     * @throws CException if the property is read only.
     * @since 1.0.1
     */
    public function __unset($name) {
        $setter = 'set' . $name;
        if (method_exists($this, $setter))
            $this->$setter(null);
        else if (strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name))
            unset($this->_e[strtolower($name)]);
        else if (is_array($this->_m)) {
            if (isset($this->_m[$name]))
                $this->detachBehavior($name);
            else {
                foreach ($this->_m as $object) {
                    if ($object->getEnabled()) {
                        if (property_exists($object, $name))
                            return $object->$name = null;
                        else if ($object->canSetProperty($name))
                            return $object->$setter(null);
                    }
                }
            }
        }
        else if (method_exists($this, 'get' . $name))
            throw new X3_Exception('Свойство "' . get_class($this) . '.' . $name . '" только для чтения.');
    }

    /**
     * Calls the named method which is not a class method.
     * Do not call this method. This is a PHP magic method that we override
     * to implement the behavior feature.
     * @param string the method name
     * @param array method parameters
     * @return mixed the method return value
     * @since 1.0.2
     */
    public function __call($name, $parameters) {
        if (!$this->fire($name, $parameters))
            throw new X3_Exception(get_class($this) . ' не имеет метода "' . $name . '"');
    }

    public function fire($method, $parameters = array()) {
        if (!empty(self::$_e[$method])) {
            foreach (self::$_e[$method] as $event)
                if (method_exists($event[0], $event[1])) {
                    call_user_func_array($event, $parameters);
                }
            return true;
        }
        return false;
    }

    public function addTrigger($method) {
        self::$_e[$method][] = array($this, $method);
    }
    
    public function stopBubbling($method) {
        if(isset(self::$_e[$method]))
            unset(self::$_e[$method]);
        else
            X3::log("No such function in bubbling stack");
    }

}