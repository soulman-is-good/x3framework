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
    
    public $defaultScripts = array(
        'text'=>"<script>
                    if(typeof CKEDITOR.instances['%Id'] != 'undefined')
                        delete(CKEDITOR.instances['%Id']);
                    CKEDITOR.replace( '%Id' );
                </script>"
    );
    
    public $defaultWrapper = "<tr><td>%label</td><td>%field</td><td>%required</td></tr>";

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
            $attributes['%content'] .= X3_Html::form_tag('input', array('type'=>'hidden','name'=>get_class($this->module) . '[' . $value . '_source]','value'=>$this->module->$text)) . 
            "<br />" . 
            X3_Html::open_tag('a', array('href'=>'/uploads/'.  get_class($this->module) .'/'.$this->module->$text,'target'=>'_blank')) . $this->module->$text . X3_Html::close_tag('a');
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
                $class = X3_Module_Table::getInstance($class)->table->select("`$id`, `$value`")->where($where)->asObject();
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
    
    public function render() {
        if($this->module == null) return '';
        $_html = '';
        if(!$this->module->table->getIsNewRecord()){
            $_html .= $this->hidden($this->module->table->getPK());
        }
        //TODO: make render function that renders whole form
        $fields = $this->module->fieldNames();
        $_html .= "<table>";
        $_html .= $this->renderPartial();
        $_html .= '<tr><td colspan="3">'.X3_Html::form_tag('button',array('%content'=>'Сохранить','type'=>'submit')).'</td></tr>';
        $_html.="</table>";
        return $_html;
    }
    
    public function renderPartial($_fields = array(),$wrapper=null) {
        if(empty($_fields)){
            $fields = $this->module->fieldNames();
        }elseif(key($_fields)==0){
            $_fields = array_combine($_fields, array_fill(0, count($_fields), ''));
            $fields = array_intersect_key($this->module->fieldNames(), $_fields);
        }else{
            $fields = $_fields;
        }
        if($wrapper==null)
            $wrapper = $this->defaultWrapper;
        $class = get_class($this->module);
        $_html = '';
        foreach ($fields as $name => $field){
            if(!isset($this->module->_fields[$name])){
                X3::log("No such field '$name' in '$class' but there is a label!");
                continue;
            }
            $flds = $this->module->_fields[$name];
            $type = preg_replace("/\[.+?\]/", "", $flds[0]);
            switch ($type){
                case "integer":
                case "string":
                    if(isset($flds['ref']))
                        $tmp = $this->select($name);
                    elseif(in_array('password', $flds))
                        $tmp = X3_Html::form_tag ('input', array('id'=>"{$class}_{$name}",'name'=>"{$class}[{$name}]",'value'=>'','type'=>'password'));
                    else
                        $tmp = $this->input($name);
                    break;
                case "file":
                    $tmp = $this->file($name);
                    break;
                case "boolean":
                    $tmp = $this->checkbox($name);
                    break;
                case "text":
                    $tmp = $this->textarea($name);
                    $this->addScript($name, $this->defaultScripts[$name]);
                    break;
                case "html":
                case "content":
                    $tmp = $this->textarea($name);
                    break;
                default:
                    $tmp = $this->input($name);
            }
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
        $this->scripts[$name] = str_replace("%Id", $id, $script);
    }

    public function end() {
        return X3_Html::close_tag('form');
    }
}
?>
