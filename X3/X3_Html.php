<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of X3_Html
 *
 * @author Soul_man
 */
class X3_Html extends X3_Component {

    public static $requiredLabel = '<span class="required">*</span>';
    
    public static function tag($tag,$attributes) {
        $attr="";
        $attr = self::compileAttrs($attributes);
        return "<$tag $attr>";
    }

    public static function open_tag($tag,$attributes) {
        $attr="";
        $attr = self::compileAttrs($attributes);
        return "<$tag $attr>";
    }

    public static function close_tag($tag) {
        if($tag!='input' && $tag!='img')
        return "</$tag>";
    }

    public static function form_tag($tag,$attributes) {
        $attr="";
        switch ($tag) {
            case "input":
                $content="";
                if(isset($attributes['%content'])){
                    $content = $attributes['%content'];
                    unset($attributes['%content']);
                }
                $attr = self::compileAttrs($attributes);
                return "<$tag $attr />" . $content;
                break;
            case "button":
            case "option":
            case "select":
            case "textarea":
                $content="";
                if(is_array($attributes) && isset($attributes['%content'])){
                    $content = $attributes['%content'];
                    unset($attributes['%content']);
                }
                return self::open_tag($tag, $attributes).$content.self::close_tag($tag);
                break;
            case "label":
                $content="";
                if(isset($attributes['%content'])){
                    $content = $attributes['%content'];
                    unset($attributes['%content']);
                }
                if(isset($attributes['%required']) && $attributes['%required']===true){
                    $content .= self::$requiredLabel;
                    unset($attributes['%required']);
                }else
                    unset($attributes['%required']);
                return self::open_tag($tag, $attributes).$content.self::close_tag($tag);
                break;
        }
    }

    public static function metaTag($content,$name=null,$httpEquiv=null,$options=array())
    {
            if($name!==null)
                    $options['name']=$name;
            if($httpEquiv!==null)
                    $options['http-equiv']=$httpEquiv;
            $options['content']=$content;
            return self::tag('meta',$options);
    }
    public static function linkTag($relation=null,$type=null,$href=null,$media=null,$options=array())
    {
            if($relation!==null)
                    $options['rel']=$relation;
            if($type!==null)
                    $options['type']=$type;
            if($href!==null)
                    $options['href']=$href;
            if($media!==null)
                    $options['media']=$media;
            return self::tag('link',$options);
    }
    public static function css($text,$media='')
    {
            if($media!=='')
                    $media=' media="'.$media.'"';
            return "<style type=\"text/css\"{$media}>\n/*<![CDATA[*/\n{$text}\n/*]]>*/\n</style>";
    }
    public static function cssFile($url,$media='')
    {
            if($media!=='')
                    $media=' media="'.$media.'"';
            return '<link rel="stylesheet" type="text/css" href="'.self::encode($url).'"'.$media.' />';
    }
    public static function script($text)
    {
            return "<script type=\"text/javascript\">\n/*<![CDATA[*/\n{$text}\n/*]]>*/\n</script>";
    }
    public static function scriptFile($url)
    {
            return '<script type="text/javascript" src="'.self::encode($url).'"></script>';
    }
    public static function compileAttrs($attributes,$strict=true) {
        $attr="";
        if(is_array($attributes) && !empty($attributes))
        foreach($attributes as $k=>$a){
            if($strict && strpos($k,'%')===0) continue;
            $attr .= $k.'="'.htmlspecialchars($a).'" ';
        }
        return $attr;
    }
    public static function encode($text)
    {
            return htmlspecialchars($text,ENT_QUOTES,X3::app()->module->encoding);
    }
}
?>
