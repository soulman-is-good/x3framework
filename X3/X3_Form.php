<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of X3_Form
 *
 * @author Soul_man
 */
class X3_Form extends X3_Renderer {

    public $attributes =array(
    'method' => 'post',
    'enctype'=> 'multipart/form-data'
    );

    public function __construct($class = null,$attributes = array()) {
        $this->attributes = array_extend($this->attributes,$attributes);
        parent::__construct($class);
    }

    public function start($attributes=array()) {
        $this->attributes = array_extend($this->attributes,$attributes);
        if(!isset($this->attributes['id']))
            $this->attributes['id'] = (string)X3_String::create(get_class($this->module) . '-form')->lcfirst();
        if(!isset($this->attributes['name']))
            $this->attributes['name'] = $this->attributes['id'];
        return X3_Html::open_tag('form',$this->attributes);
    }

    public function input($value='',$attributes=array()) {
        $attributes['type'] = !isset($attributes['type'])?'text':$attributes['type'];
        if((($this->module instanceof X3_Model) && isset($this->module[$value])) || (($this->module instanceof X3_Module_Table) && isset($this->module->table[$value]))){
            $attributes['name'] = !isset($attributes['name'])?get_class($this->module) . '[' . $value . ']':$attributes['name'];
            $attributes['id'] = !isset($attributes['id'])?get_class($this->module) . '_' . $value:$attributes['id'];
            $attributes['value'] = $this->module->$value;
        }else {
            $attributes['value'] = $value;
        }
        return X3_Html::form_tag('input', $attributes);
    }

    public function checkbox($checked=false,$attributes=array()) {
        $attributes['type'] = !isset($attributes['type'])?'checkbox':$attributes['type'];
        $value = "$checked";
        if((($this->module instanceof X3_Model) && isset($this->module[$value])) || (($this->module instanceof X3_Module_Table) && isset($this->module->table[$value]))){
            $attributes['name'] = !isset($attributes['name'])?get_class($this->module) . '[' . $checked . ']':$attributes['name'];
            $attributes['id'] = !isset($attributes['id'])?get_class($this->module) . '_' . $checked:$attributes['id'];
            if($this->module->$checked)
                $attributes['checked'] = "checked";
        }else {
            if($checked)
                $attributes['checked'] = "checked";
        }
        return X3_Html::form_tag('input', $attributes);
    }
	
    public function radio($checked=false,$attributes=array()) {
        $attributes['type'] = !isset($attributes['type'])?'radio':$attributes['type'];
        $value = "$checked";
        if((($this->module instanceof X3_Model) && isset($this->module[$value])) || (($this->module instanceof X3_Module_Table) && isset($this->module->table[$value]))){
            $attributes['name'] = !isset($attributes['name'])?get_class($this->module) . '[' . $checked . ']':$attributes['name'];
            $attributes['id'] = !isset($attributes['id'])?get_class($this->module) . '_' . $checked:$attributes['id'];
			if(!isset($attributes['value'])) $attributes['value'] = '1';
            if($this->module->$checked == $attributes['value'])
                $attributes['checked'] = "checked";
        }else {
            if($checked)
                $attributes['checked'] = "checked";
        }
        return X3_Html::form_tag('input', $attributes);
    }

    public function hidden($value='',$attributes=array()) {
        $attributes['type'] = 'hidden';
        return $this->input($value,$attributes);
    }

    public function textarea($text='',$attributes=array()) {
        $attributes['rows'] = !isset($attributes['rows'])?7:$attributes['rows'];
        $attributes['cols'] = !isset($attributes['cols'])?30:$attributes['cols'];
        $value = $text;
        if((($this->module instanceof X3_Model) && isset($this->module[$value])) || (($this->module instanceof X3_Module_Table) && isset($this->module->table[$value]))){
            $attributes['name'] = !isset($attributes['name'])?get_class($this->module) . '[' . $text . ']':$attributes['name'];
            $attributes['id'] = !isset($attributes['id'])?get_class($this->module) . '_' . $text:$attributes['id'];
            $attributes['%content'] = $this->module->$text;
        }
        else
            $attributes['%content'] = $text;
        return X3_Html::form_tag('textarea', $attributes);
    }

    public function file($text='',$attributes=array()) {
        $attributes['type'] = 'file';
        $value = $text;
        if((($this->module instanceof X3_Model) && isset($this->module[$value])) || (($this->module instanceof X3_Module_Table) && isset($this->module->table[$value]))){
            $attributes['name'] = !isset($attributes['name'])?get_class($this->module) . '[' . $text . ']':$attributes['name'];
            $attributes['id'] = !isset($attributes['id'])?get_class($this->module) . '_' . $text:$attributes['id'];
            $attributes['value'] = $this->module->$text;
            if(!isset($attributes['%content'])) $attributes['%content'] = "";
            $attributes['%content'] .= X3_Html::form_tag('input', array('type'=>'hidden','name'=>get_class($this->module) . '[' . $value . '_source]','value'=>$this->module->$text));
        }
        else
            $attributes['name'] = $text;
        return X3_Html::form_tag('input', $attributes);
    }

    public function select($options=array(),$attributes=array()) {        
        if(is_array($options)){
            $ops='';
            foreach($options as $key => $option){
	            $attrs = array('%content' => $option, 'value' => $key);
	            if (isset($attributes['%select']) && ($attributes['%select'] == $key)) {
		            $attrs['selected'] = 'selected';
            }
                $ops.=X3_Html::form_tag('option', $attrs);
            }
            $attributes['%content'] = $ops;
            return X3_Html::form_tag('select',$attributes);
        }elseif((($this->module instanceof X3_Model) && isset($this->module[$options])) || (($this->module instanceof X3_Module_Table) && isset($this->module->table[$options]))){
            $attributes['name'] = !isset($attributes['name'])?get_class($this->module) . '[' . $options . ']':$attributes['name'];
            $attributes['id'] = !isset($attributes['id'])?get_class($this->module) . '_' . $options:$attributes['id'];
            if(!isset($attributes['%select'])) {
                $attributes['%select'] = array('id','title');
            }
            $id = array_shift($attributes['%select']);
            $value = array_shift($attributes['%select']);
            $where = array_shift($attributes['%select']);
            $ops = '';
            if(isset($attributes['%options'])){
                $attrs = $attributes['%options'];
                unset($attributes['%options']);
            }else
                $attrs = array();
            if(isset($this->module->_fields[$options]['ref'])){
                $class = $this->module->_fields[$options]['ref'][0];
                $id = $this->module->_fields[$options]['ref'][1];
                $class = new $class();
                $class = $class->table->select("`$id`, `$value`")->where($where)->asObject();
                foreach($class as $m){
                    //TODO: OPTIMIZE references
                    //$module = $m->$options();
                    if($this->module->$options == $m->$id)
                        $attr['selected'] = "selected";
                    $attr['value'] = $m->$id;
                    $attr['%content'] = $m->$value;
                    $ops .= X3_Html::form_tag('option',$attr);
                }
            }
            unset($attributes['%select']);
            if(!isset($attributes['%content']))
                $attributes['%content'] = $ops;
            else
                $attributes['%content'] .= $ops;
            return X3_Html::form_tag('select',$attributes);
        }
    }
    
/*    public function render($field) {
        $fields = $this->module->_fields;
        $class = get_class($this->module);
        if(isset($this->module->template[$field])){
            $tpl = $this->module->template[$field];
            if(is_array($tpl))
                return X3_Html::open_tag($tpl['tag'], $tpl['attributes']).X3_Html::close_tag ($tpl['tag']);//TODO: more convinient
            else
                return $tpl;
        }
        if(isset($fields[$field])){
            //TODO: default templates
        }
        
    }*/

    public function end() {
        return X3_Html::close_tag('form');
    }
}
?>
