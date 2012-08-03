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
    
    public $requiredLabel = '<span class="x3-required">*</span>';
    
    private $scripts = array();
    
    public $defaultScripts = array();
    
    public $defaultWrapper = array(
        'row'=>"<tr><td>%label</td><td>%field</td><td>%required</td></tr>",
        'wraper'=>"<table>%rows<tr><td colspan=\"3\">%submit</td></tr></table>"
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
            $file = 'uploads/'.  get_class($this->module) .'/'.$this->module->$text;
            if(is_file($file)){
                $attributes['%content'] .= "<br />" . X3_Html::open_tag('a', array('href'=>'/'.$file,'target'=>'_blank')) . $this->module->$text . X3_Html::close_tag('a');
                if((isset($this->module->_fields[$text]['default']) && $this->module->_fields[$text]['default']=='NULL') || (in_array('null', $this->module->_fields[$text]))){
                    $attributes['%content'] .= '<br/>'.X3_Html::form_tag('input', array('type'=>'checkbox','name'=>get_class($this->module) . '[' . $value . '_delete]')).'Удалить?';
                }
            }
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
                $attributes['%select'] = array($this->module->table->getPK(),'title');
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
                if(isset($this->module->_fields[$options]['ref'][0])){
                    $class = $this->module->_fields[$options]['ref'][0];
                    $id = $this->module->_fields[$options]['ref'][1];
                }else{
                    $k = key($this->module->_fields[$options]['ref']);
                    $class = $k;
                    $id = $this->module->_fields[$options]['ref'][$k];
                }
                //get default field for value if defined
                if(isset($this->module->_fields[$options]['ref']['default']))
                    $value = $this->module->_fields[$options]['ref']['default'];
                $query = array();
                if(isset($this->module->_fields[$options]['ref']['query']))
                    $query = $this->module->_fields[$options]['ref']['query'];
                
                $class = X3_Module_Table::get($query,0,$class);
                //make it possible to define high level if ther is one
                if(isset($this->module->_fields[$options]['default']) && ($this->module->_fields[$options]['default'] == 'NULL'||is_null($this->module->_fields[$options]['default'])) 
                        ||
                   in_array('null',$this->module->_fields[$options]))
                        $ops .= X3_Html::form_tag('option',array('%content'=>'<Не выбран>','value'=>''));
                foreach($class as $m){
                    //TODO: OPTIMIZE references
                    //$module = $m->$options();
                    $attr = array();
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
    
    public function render($withLangs = true) {
        if($this->module == null) return '';
        $_html = '';
        if(!$this->module->table->getIsNewRecord()){
            $_html .= $this->hidden($this->module->table->getPK());
        }
        //TODO: make render function that renders whole form
        $fields = $this->module->fieldNames();
        if(isset(X3::app()->languages)){
            $langs = X3::app()->languages;
            foreach ($fields as $name => $value) {
                if(in_array('language',$this->module->_fields[$name])){
                    foreach($langs as $lang){
                        $attr = $name . "_" . $lang;
                        $val = $value . " " . strtoupper($lang);
                        array_insert(array($attr=>$val), $fields,$name);
                    }
                }
            }
        }
        if(is_array($this->defaultWrapper)){
            $wrapper = $this->defaultWrapper['wraper'];
            $_html .= str_replace('%rows', $this->renderPartial($fields), $wrapper);
            $_html = str_replace('%submit', X3_Html::form_tag('button',array('%content'=>'Сохранить','type'=>'submit')), $_html);
        }else{
            $_html .= "<table>";
            $_html .= $this->renderPartial($fields);
            $_html .= '<tr><td colspan="3">'.X3_Html::form_tag('button',array('%content'=>'Сохранить','type'=>'submit')).'</td></tr>';
            $_html.="</table>";
        }
        return $_html;
    }
    
    public function renderPartial($_fields = array(),$wrapper=null) {
        if(empty($_fields)){
            $fields = $this->module->fieldNames();
        }elseif(is_numeric(key($_fields))){
            $_fields = array_combine($_fields, array_fill(0, count($_fields), ''));
            $fields = array_intersect_key($this->module->fieldNames(), $_fields);
        }else{
            $fields = $_fields;
        }
        if($wrapper==null){
            if(is_array($this->defaultWrapper))
                $wrapper = $this->defaultWrapper['row'];
            else
                $wrapper = $this->defaultWrapper;
        }
        $class = get_class($this->module);
        $_html = '';
        foreach ($fields as $name => $field){
            if(!isset($this->module->_fields[$name])){
                X3::log("No such field '$name' in '$class' but there is a label!");
                continue;
            }
            $flds = $this->module->_fields[$name];
            $matches = array();
            $type = $flds[0];
            if (preg_match('/\[(.+?)\]/', $type, $matches) > 0) {
                $rep = array_shift($matches);
                $arg = array_shift($matches);
                $type = str_replace($rep, "", $type);
            }
            switch ($type){
                case "enum":
                    $arg = explode(',',$arg);
                    $options = array();
                    foreach ($arg as &$a) {
                        $a = trim($a,'"\'');
                        $options[$a] = $a;
                    }
                    $tmp = $this->select($options,array('id'=>"{$class}_{$name}",'name'=>"{$class}[{$name}]",'%select'=>$this->module->$name));
                    break;
                case "integer":
                case "string":
                    if(isset($flds['ref']))
                        $tmp = $this->select($name);
                    elseif(in_array('password', $flds))
                        $tmp = X3_Html::form_tag ('input', array('id'=>"{$class}_{$name}",'name'=>"{$class}[{$name}]",'value'=>'','type'=>'password'));
                    else
                        $tmp = $this->input($name);
                    break;
                case "datetime":
                        $val = $this->module->$name;
                        $format = 'd.m.Y';
                        if(isset($flds['format']))
                            $format = $flds['format'];
                        if($val == 0)
                            $val = time();
                        $tmp = X3_Html::form_tag('input',array('type'=>'text','id'=>"{$class}_$name",'name'=>"{$class}[$name]",'value'=>date($format,$val)));
                    break;
                case "file":
                    $tmp = $this->file($name);
                    break;
                case "boolean":
                    $tmp = $this->checkbox($name);
                    break;
                case "text":
                    $tmp = $this->textarea($name);
                    break;
                case "html":
                case "content":
                    $tmp = $this->textarea($name);
                    break;
                default:
                    $tmp = $this->input($name);
            }
            if(isset($this->defaultScripts[$type]))
                $this->addScript($name, $this->defaultScripts[$type]);
            $tmp .= $this->initScript($name);
            $required = $this->requiredLabel;
            if((isset($flds['default']) && ($flds['default']=='NULL' || is_null($flds['default']))) || in_array('null', $flds));
                $required = '&nbsp;';
            $tmp = str_replace("%field", $tmp, $wrapper);
            $tmp = str_replace("%label", $field, $tmp);
            $tmp = str_replace("%required", $required, $tmp);
            $_html .= $tmp;
        }
        return $_html;
    }
    
    public function initScript($name) {
        if(isset($this->scripts[$name])){
            return $this->scripts[$name];
        }
        return '';
    }
    
    public function addScript($name,$script) {
        $id = "$name";
        if($this->module != null)
            $id = get_class($this->module) . "_$name";
        $script = str_replace("%Locale", X3::app()->locale, $script);
        $this->scripts[$name] = str_replace("%Id", $id, $script);
    }

    public function end() {
        return X3_Html::close_tag('form');
    }
}
?>
