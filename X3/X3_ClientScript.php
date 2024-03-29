<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of X3_ClientScript
 *
 * @author Soul_man
 */
class X3_ClientScript extends X3_Component
{
	/**
	 * The script is rendered in the head section right before the title element.
	 */
	const POS_HEAD=0;
	/**
	 * The script is rendered at the beginning of the body section.
	 */
	const POS_BEGIN=1;
	/**
	 * The script is rendered at the end of the body section.
	 */
	const POS_END=2;
	/**
	 * The script is rendered inside window onload function.
	 */
	const POS_LOAD=3;
	/**
	 * The body script is rendered inside a jQuery ready function.
	 */
	const POS_READY=4;

	/**
	 * @var boolean whether JavaScript should be enabled. Defaults to true.
	 */
	public $enableJavaScript=true;
	/**
	 * @var array the mapping between script file names and the corresponding script URLs.
	 * The array keys are script file names (without directory part) and the array values are the corresponding URLs.
	 * If an array value is false, the corresponding script file will not be rendered.
	 * If an array key is '*.js' or '*.css', the corresponding URL will replace all
	 * all JavaScript files or CSS files, respectively.
	 *
	 * This property is mainly used to optimize the generated HTML pages
	 * by merging different scripts files into fewer and optimized script files.
	 * @since 1.0.3
	 */
	public $scriptMap=array();
	/**
	 * @var array the registered CSS files (CSS URL=>media type).
	 * @since 1.0.4
	 */
	protected $cssFiles=array();
	/**
	 * @var array the registered JavaScript files (position, key => URL)
	 * @since 1.0.4
	 */
	protected $scriptFiles=array();
	/**
	 * @var array the registered JavaScript code blocks (position, key => code)
	 * @since 1.0.5
	 */
	protected $scripts=array();

	private $_hasScripts=false;
	private $_packages;
	private $_dependencies;
	private $_baseUrl;
	private $_coreScripts=array();
	private $_css=array();
	private $_metas=array();
	private $_links=array();

        public function __construct() {
            $this->addTrigger('onRender');
        }
	/**
	 * Cleans all registered scripts.
	 */
	public function reset()
	{
		$this->_hasScripts=false;
		$this->_coreScripts=array();
		$this->cssFiles=array();
		$this->_css=array();
		$this->scriptFiles=array();
		$this->scripts=array();
		$this->_metas=array();
		$this->_links=array();
	}

	/**
	 * Renders the registered scripts.
	 * This method is called in {@link X3_Renderer::render} when it finishes
	 * rendering content. CClientScript thus gets a chance to insert script tags
	 * at <code>head</code> and <code>body</code> sections in the HTML output.
	 * @param string the existing output that needs to be inserted with script tags
	 */
	public function onRender(&$output)
	{
		if(!$this->_hasScripts)
			return;

		$this->renderCoreScripts();

		if(!empty($this->scriptMap))
			$this->remapScripts();

		$this->renderHead($output);
		if($this->enableJavaScript)
		{
			$this->renderBodyBegin($output);
			$this->renderBodyEnd($output);
		}
	}

	/**
	 * Uses {@link scriptMap} to re-map the registered scripts.
	 * @since 1.0.3
	 */
	protected function remapScripts()
	{
		$cssFiles=array();
		foreach($this->cssFiles as $url=>$media)
		{
			$name=basename($url);
			if(isset($this->scriptMap[$name]))
			{
				if($this->scriptMap[$name]!==false)
					$cssFiles[$this->scriptMap[$name]]=$media;
			}
			else if(isset($this->scriptMap['*.css']))
			{
				if($this->scriptMap['*.css']!==false)
					$cssFiles[$this->scriptMap['*.css']]=$media;
			}
			else
				$cssFiles[$url]=$media;
		}
		$this->cssFiles=$cssFiles;

		$jsFiles=array();
		foreach($this->scriptFiles as $position=>$scripts)
		{
			$jsFiles[$position]=array();
			foreach($scripts as $key=>$script)
			{
				$name=basename($script);
				if(isset($this->scriptMap[$name]))
				{
					if($this->scriptMap[$name]!==false)
						$jsFiles[$position][$this->scriptMap[$name]]=$this->scriptMap[$name];
				}
				else if(isset($this->scriptMap['*.js']))
				{
					if($this->scriptMap['*.js']!==false)
						$jsFiles[$position][$this->scriptMap['*.js']]=$this->scriptMap['*.js'];
				}
				else
					$jsFiles[$position][$key]=$script;
			}
		}
		$this->scriptFiles=$jsFiles;
	}

	/**
	 * Renders the specified core javascript library.
	 * @since 1.0.3
	 */
	public function renderCoreScripts()
	{
		if($this->_packages===null)
			return;
		$baseUrl=$this->getCoreScriptUrl();
		$cssFiles=array();
		$jsFiles=array();
		foreach($this->_coreScripts as $name)
		{
			foreach($this->_packages[$name] as $path)
			{
				$url=$baseUrl.'/'.$path;
				if(substr($path,-4)==='.css')
					$cssFiles[$url]='';
				else
					$jsFiles[$url]=$url;
			}
		}
		// merge in place
		if($cssFiles!==array())
		{
			foreach($this->cssFiles as $cssFile=>$media)
				$cssFiles[$cssFile]=$media;
			$this->cssFiles=$cssFiles;
		}
		if($jsFiles!==array())
		{
			if(isset($this->scriptFiles[self::POS_HEAD]))
			{
				foreach($this->scriptFiles[self::POS_HEAD] as $url)
					$jsFiles[$url]=$url;
			}
			$this->scriptFiles[self::POS_HEAD]=$jsFiles;
		}
	}

	/**
	 * Inserts the scripts in the head section.
	 * @param string the output to be inserted with scripts.
	 */
	public function renderHead(&$output)
	{
		$html='';
		foreach($this->_metas as $meta)
			$html.=X3_Html::metaTag($meta['content'],null,null,$meta)."\n";
		foreach($this->_links as $link)
			$html.=X3_Html::linkTag(null,null,null,null,$link)."\n";
		foreach($this->cssFiles as $url=>$media)
			$html.=X3_Html::cssFile($url,$media)."\n";
		foreach($this->_css as $css)
			$html.=X3_Html::css($css[0],$css[1])."\n";
		if($this->enableJavaScript)
		{
			if(isset($this->scriptFiles[self::POS_HEAD]))
			{
				foreach($this->scriptFiles[self::POS_HEAD] as $scriptFile)
					$html.=X3_Html::scriptFile($scriptFile)."\n";
			}

			if(isset($this->scripts[self::POS_HEAD]))
				$html.=X3_Html::script(implode("\n",$this->scripts[self::POS_HEAD]))."\n";
		}

		if($html!=='')
		{
			$count=0;
			$output=preg_replace('/(<title\b[^>]*>|<\\/head\s*>)/is','<###head###>$1',$output,1,$count);
			if($count)
				$output=str_replace('<###head###>',$html,$output);
			else
				$output=$html.$output;
		}
	}

	/**
	 * Inserts the scripts at the beginning of the body section.
	 * @param string the output to be inserted with scripts.
	 */
	public function renderBodyBegin(&$output)
	{
		$html='';
		if(isset($this->scriptFiles[self::POS_BEGIN]))
		{
			foreach($this->scriptFiles[self::POS_BEGIN] as $scriptFile)
				$html.=X3_Html::scriptFile($scriptFile)."\n";
		}
		if(isset($this->scripts[self::POS_BEGIN]))
			$html.=X3_Html::script(implode("\n",$this->scripts[self::POS_BEGIN]))."\n";

		if($html!=='')
		{
			$count=0;
			$output=preg_replace('/(<body\b[^>]*>)/is','$1<###begin###>',$output,1,$count);
			if($count)
				$output=str_replace('<###begin###>',$html,$output);
			else
				$output=$html.$output;
		}
	}

	/**
	 * Inserts the scripts at the end of the body section.
	 * @param string the output to be inserted with scripts.
	 */
	public function renderBodyEnd(&$output)
	{
		if(!isset($this->scriptFiles[self::POS_END]) && !isset($this->scripts[self::POS_END])
			&& !isset($this->scripts[self::POS_READY]) && !isset($this->scripts[self::POS_LOAD]))
			return;

		$fullPage=0;
		$output=preg_replace('/(<\\/body\s*>)/is','<###end###>$1',$output,1,$fullPage);
		$html='';
		if(isset($this->scriptFiles[self::POS_END]))
		{
			foreach($this->scriptFiles[self::POS_END] as $scriptFile)
				$html.=X3_Html::scriptFile($scriptFile)."\n";
		}
		$scripts=isset($this->scripts[self::POS_END]) ? $this->scripts[self::POS_END] : array();
		if(isset($this->scripts[self::POS_READY]))
		{
			if($fullPage)
				$scripts[]="jQuery(document).ready(function() {\n".implode("\n",$this->scripts[self::POS_READY])."\n});";
			else
				$scripts[]=implode("\n",$this->scripts[self::POS_READY]);
		}
		if(isset($this->scripts[self::POS_LOAD]))
		{
			if($fullPage)
				$scripts[]="window.onload=function() {\n".implode("\n",$this->scripts[self::POS_LOAD])."\n};";
			else
				$scripts[]=implode("\n",$this->scripts[self::POS_LOAD]);
		}
		if(!empty($scripts))
			$html.=X3_Html::script(implode("\n",$scripts))."\n";

		if($fullPage)
			$output=str_replace('<###end###>',$html,$output);
		else
			$output=$output.$html;
	}

	/**
	 * Sets the base URL of all core javascript files.
	 * This setter is provided in case when core javascript files are manually published
	 * to a pre-specified location. This may save asset publishing time for large-scale applications.
	 * @param string the base URL of all core javascript files.
	 */
	public function setCoreScriptUrl($value)
	{
		$this->_baseUrl=$value;
	}

	/**
	 * Registers a core javascript library.
	 * @param string the core javascript library name
	 * @see renderCoreScript
	public function registerCoreScript($name)
	{
		if(isset($this->_coreScripts[$name]))
			return;

		if($this->_packages===null)
		{
			$config=require(BASE_PATH.'/web/js/packages.php');
			$this->_packages=$config[0];
			$this->_dependencies=$config[1];
		}
		if(!isset($this->_packages[$name]))
			return;
		if(isset($this->_dependencies[$name]))
		{
			foreach($this->_dependencies[$name] as $depName)
				$this->registerCoreScript($depName);
		}

		$this->_hasScripts=true;
		$this->_coreScripts[$name]=$name;
		$params=func_get_args();
	}
	 */

	/**
	 * Registers a CSS file
	 * @param string URL of the CSS file
	 * @param string media that the CSS file should be applied to. If empty, it means all media types.
	 */
	public function registerCssFile($url,$media='')
	{
		$this->_hasScripts=true;
		$this->cssFiles[$url]=$media;
		$params=func_get_args();
	}

	/**
	 * Registers a piece of CSS code.
	 * @param string ID that uniquely identifies this piece of CSS code
	 * @param string the CSS code
	 * @param string media that the CSS code should be applied to. If empty, it means all media types.
	 */
	public function registerCss($id,$css,$media='')
	{
		$this->_hasScripts=true;
		$this->_css[$id]=array($css,$media);
		$params=func_get_args();
	}

	/**
	 * Registers a javascript file.
	 * @param string URL of the javascript file
	 * @param integer the position of the JavaScript code. Valid values include the following:
	 * <ul>
	 * <li>CClientScript::POS_HEAD : the script is inserted in the head section right before the title element.</li>
	 * <li>CClientScript::POS_BEGIN : the script is inserted at the beginning of the body section.</li>
	 * <li>CClientScript::POS_END : the script is inserted at the end of the body section.</li>
	 * </ul>
	 */
	public function registerScriptFile($url,$position=self::POS_HEAD)
	{
		$this->_hasScripts=true;
		$this->scriptFiles[$position][$url]=$url;
		$params=func_get_args();
	}

	/**
	 * Registers a piece of javascript code.
	 * @param string ID that uniquely identifies this piece of JavaScript code
	 * @param string the javascript code
	 * @param integer the position of the JavaScript code. Valid values include the following:
	 */
	public function registerScript($id,$script,$position=self::POS_READY)
	{
		$this->_hasScripts=true;
		$this->scripts[$position][$id]=$script;
		$params=func_get_args();
	}

	/**
	 * Registers a meta tag that will be inserted in the head section (right before the title element) of the resulting page.
	 * @param string content attribute of the meta tag
	 * @param string name attribute of the meta tag. If null, the attribute will not be generated
	 * @param string http-equiv attribute of the meta tag. If null, the attribute will not be generated
	 * @param array other options in name-value pairs (e.g. 'scheme', 'lang')
	 * @since 1.0.1
	 */
	public function registerMetaTag($content,$name=null,$httpEquiv=null,$options=array())
	{
		$this->_hasScripts=true;
		if($name!==null)
			$options['name']=$name;
		if($httpEquiv!==null)
			$options['http-equiv']=$httpEquiv;
		$options['content']=$content;
		$this->_metas[serialize($options)]=$options;
		$params=func_get_args();
	}

	/**
	 * Registers a link tag that will be inserted in the head section (right before the title element) of the resulting page.
	 * @param string rel attribute of the link tag. If null, the attribute will not be generated.
	 * @param string type attribute of the link tag. If null, the attribute will not be generated.
	 * @param string href attribute of the link tag. If null, the attribute will not be generated.
	 * @param string media attribute of the link tag. If null, the attribute will not be generated.
	 * @param array other options in name-value pairs
	 * @since 1.0.1
	 */
	public function registerLinkTag($relation=null,$type=null,$href=null,$media=null,$options=array())
	{
		$this->_hasScripts=true;
		if($relation!==null)
			$options['rel']=$relation;
		if($type!==null)
			$options['type']=$type;
		if($href!==null)
			$options['href']=$href;
		if($media!==null)
			$options['media']=$media;
		$this->_links[serialize($options)]=$options;
		$params=func_get_args();
	}

	/**
	 * Checks whether the CSS file has been registered.
	 * @param string URL of the CSS file
	 * @return boolean whether the CSS file is already registered
	 */
	public function isCssFileRegistered($url)
	{
		return isset($this->cssFiles[$url]);
	}

	/**
	 * Checks whether the CSS code has been registered.
	 * @param string ID that uniquely identifies the CSS code
	 * @return boolean whether the CSS code is already registered
	 */
	public function isCssRegistered($id)
	{
		return isset($this->_css[$id]);
	}

	/**
	 * Checks whether the JavaScript file has been registered.
	 * @param string URL of the javascript file
	 * @param integer the position of the JavaScript code. Valid values include the following:
	 * <ul>
	 * <li>CClientScript::POS_HEAD : the script is inserted in the head section right before the title element.</li>
	 * <li>CClientScript::POS_BEGIN : the script is inserted at the beginning of the body section.</li>
	 * <li>CClientScript::POS_END : the script is inserted at the end of the body section.</li>
	 * </ul>
	 * @return boolean whether the javascript file is already registered
	 */
	public function isScriptFileRegistered($url,$position=self::POS_HEAD)
	{
		return isset($this->scriptFiles[$position][$url]);
	}

	/**
	 * Checks whether the JavaScript code has been registered.
	 * @param string ID that uniquely identifies the JavaScript code
	 * @param integer the position of the JavaScript code. Valid values include the following:
	 * <ul>
	 * <li>CClientScript::POS_HEAD : the script is inserted in the head section right before the title element.</li>
	 * <li>CClientScript::POS_BEGIN : the script is inserted at the beginning of the body section.</li>
	 * <li>CClientScript::POS_END : the script is inserted at the end of the body section.</li>
	 * <li>CClientScript::POS_LOAD : the script is inserted in the window.onload() function.</li>
	 * <li>CClientScript::POS_READY : the script is inserted in the jQuery's ready function.</li>
	 * </ul>
	 * @return boolean whether the javascript code is already registered
	 */
	public function isScriptRegistered($id,$position=self::POS_READY)
	{
		return isset($this->scripts[$position][$id]);
	}

}
?>
