<?php

/**
 * X3_Controller
 *
 * @author soulman
 *
 * 21.11.2010 0:37:08
 */
class X3_Controller extends X3_Component implements X3_Interface_Controller {

    public $id = null;
    public $action = null;
    public $template = null;
    private $module = null;

    public function __construct($action, $module=null) {
        if (empty($action))
            $action = 'index';
        $this->action = $action;
        $this->module = $module;
        if ($module !== null)
            $this->id = (string)X3_String::create(get_class($module))->lcfirst();
        else
            $this->id = (string)X3_String::create(get_class($this))->lcfirst();
        if (!$this->handleFilters()) {
            $this->redirect(X3::app()->errorController);
        }
        //TODO: Handling with no module! Check work status
        if ($module !== null)
            $this->module->init();
        else {
            $this->module = $this;
            $this->run();
        }
        return $this;
    }

    public function run() {
        $this->fire('beforeAction',array(&$this->action));
        if($this->action!=null){
            $action = 'action' . ucfirst($this->action);
            if (($output = $this->handleCache()) !== false) {
                $this->fire('onRender',array(&$output));
                echo $output;
                $this->fire('afterAction');
            }else{            
                $this->module->$action();
                $this->fire('afterAction');
            }
        }
    }

    public function filter() {
        return $this->module->filter();
    }

    public function route() {
        return $this->module->route();
    }

    public function cache() {
        return $this->module->cache();
    }

    protected function handleFilters() {
        $filter = $this->filter();
        $action = $this->action;
        if (isset($filter) && is_array($filter) && !empty($filter)) {
            if (isset($filter['allow']))
                $allow = $filter['allow'];
            else
                $allow = array();
            if (isset($filter['deny']))
                $deny = $filter['deny'];
            else
                $deny = array();
            if (!isset($allow['*']))
                $allow['*'] = array();
            if (!isset($deny['*']))
                $deny['*'] = array();

            $user_group = X3::app()->user->group;
            if (isset($allow[$user_group])) {
                $aactions = $allow[$user_group];
                if(!is_array($aactions)) $aactions = array($aactions);
                $aactions = array_merge($allow['*'], $aactions);
            } else {
                $aactions = $allow['*'];
                if(!is_array($aactions)) $aactions = array($aactions);
            }
            if (isset($deny[$user_group])) {
                $nactions = $deny[$user_group];
                if(!is_array($nactions)) $nactions = array($nactions);
                $nactions = array_merge($deny['*'], $nactions);
            } else {
                $nactions = $deny['*'];
                if(!is_array($nactions)) $nactions = array($nactions);
            }
            if ((in_array($action, $aactions) || reset($aactions)=='*') && (!in_array($action, $nactions) || reset($nactions)!='*')) {
                return true;
            } else {
                if (isset($filter['handle'])) {
                    if (is_string($filter['handle'])) {
                        $handle = explode(':', $filter['handle']);
                        $this->{$handle[0]}($handle[1]);
                    } elseif (is_array($filter['handle'])) {
                        call_user_func_array($filter['handle'], array());
                    } elseif (is_callable($filter['handle'])) {
                        call_user_func($filter['handle']);
                    }
                }else
                    return false;
            }
        }
        return true;
    }

    protected function handleCache() {
        $cache = $this->cache();
        $action = $this->action;
        $skip = false;
        if (isset($cache) && is_array($cache) && !empty($cache)) {
            if (isset($cache['nocache'])) {
                $isact = false;
                if ($cache['nocache']['role'] == '*' || X3::app()->user->group == $cache['nocache']['role']) {
                    if ($cache['nocache']['actions'] == '*')
                        $isact = true;
                    else {
                        $actions = explode(',', $cache['nocache']['actions']);
                        foreach ($actions as $a)
                            if (($isact = (trim($a) === $action)))
                                break;
                    }
                    if ($isact) {
                        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                        header('Cache-Control: no-store, no-cache, must-revalidate');
                        header('Cache-Control: post-check=0, pre-check=0', FALSE);
                        header('Pragma: no-cache');
                        $skip = true;
                    }
                }
            }
            if (isset($cache['cache']) && !$skip) {
                if (!isset($cache['cache'][0]) || !is_array($cache['cache'][0]))
                    $cache['cache'] = array($cache['cache']);
                foreach ($cache['cache'] as $c) {
                    $isact = false;
                    if ($c['role'] == '*' || X3::app()->user->group == $c['role']) {
                        if ($c['actions'] == '*')
                            $isact = true;
                        else {
                            $actions = explode(',', $c['actions']);
                            foreach ($actions as $a)
                                if (($isact = ((trim($a) === $action && !isset($c['trigger'])) || (isset($c['trigger']) && trim($a) === $action && call_user_func($c['trigger'])))))
                                    break;
                        }
                        if ($isact) {
                            if (isset($c['filename'])){
                                X3::app()->cache->filename = $c['filename'];
                            }
                            if (isset($c['directory'])){
                                X3::app()->cache->directory = $c['directory'];
                            }
                            if (isset($c['expire']))
                                X3::app()->cache->expire = $c['expire'];
                            return X3::app()->cache->readCache($this->id,$action);
                        }
                    }
                }
            }
        }
        return false;
    }

    public function getModule() {
        return $this->module;
    }

    /* public function render($view, $data=null, $return=false,$processOutput=true) {
      if (($viewFile = $this->getViewFile($view)) !== false){
      $output = $this->renderPartial($viewFile, $data, true);
      }else
      throw new X3_Exception(get_class($this).' не может найти представление "'.$view.'".');

      if (($layoutFile = $this->getLayoutFile($this->layout)) !== false)
      $output = $this->renderFile($layoutFile, array('content' => $output), true);
      X3::app()->cs->render($output);
      if ($processOutput)
      $output = $this->processOutput($output);
      if ($return)
      return $output;
      else
      echo $output;
      }
      public function renderPartial($view, $data=null,$return=false) {
      if(!is_file($view))
      $view = $this->resolveViewFile($view);
      $output = $this->renderFile($view, $data, true);
      if ($return)
      return $output;
      else
      echo $output;

      }

      public function renderFile($viewFile, $data=null, $return=false) {
      $content=$this->renderInternal($viewFile, $data, $return);
      return $content;
      }

      public function renderInternal($_viewFile_, $_data_=null, $_return_=false) {
      if (is_array($_data_))
      extract($_data_, EXTR_PREFIX_SAME, 'data');
      else
      $data=$_data_;
      if ($_return_) {
      ob_start();
      ob_implicit_flush(false);
      require($_viewFile_);
      return ob_get_clean();
      }
      else
      require($_viewFile_);
      }

      public function getViewFile($viewName) {
      return $this->resolveViewFile($viewName);
      }

      public function getLayoutFile($viewName) {
      return $this->resolveLayoutFile($viewName);
      }

      public function resolveViewFile($viewFile) {
      $path = X3::app()->getPathFromAlias($viewFile);
      if (!is_file($path))
      $viewPath = X3::app()->basePath . DIRECTORY_SEPARATOR . X3::app()->APPLICATION_DIR
      . DIRECTORY_SEPARATOR . X3::app()->VIEWS_DIR . DIRECTORY_SEPARATOR . $this->id
      . DIRECTORY_SEPARATOR . $path . '.php';
      else
      $viewPath = $path;
      if(!is_file($viewPath))
      throw new X3_Exception ("Could not open view file '$viewPath'!", X3::FILE_IO_ERROR);
      return $viewPath;
      }

      public function resolveLayoutFile($viewFile) {
      $path = X3::app()->getPathFromAlias($viewFile);
      if (!is_file($path))
      $viewPath = X3::app()->basePath . DIRECTORY_SEPARATOR . X3::app()->APPLICATION_DIR
      . DIRECTORY_SEPARATOR . X3::app()->VIEWS_DIR . DIRECTORY_SEPARATOR . X3::app()->LAYOUTS_DIR
      . DIRECTORY_SEPARATOR . $path . '.php';
      else
      $viewPath = $path;
      if(!is_file($viewPath))
      throw new X3_Exception ("Could not open layout file '$viewPath'!", X3::FILE_IO_ERROR);
      return $viewPath;
      }

      public function processOutput($output) {
      return $output;
      } */

    /**
     *    300 Multiple Choices (Множество выборов).
     *    301 Moved Permanently (Перемещено окончательно).
     *    302 Found (Найдено).
     *    303 See Other (Смотреть другое).
     *    304 Not Modified (Не изменялось).
     *    305 Use Proxy (Использовать прокси).
     *    306 (зарезервировано).
     *    307 Temporary Redirect (Временное перенаправление).
     * @param string $url
     * @param bool $terminate
     * @param int $statusCode
     */
    public function redirect($url, $terminate=true, $statusCode=302) {
        header('Location: ' . $url, true, $statusCode);
        if ($terminate)
            exit(0);
    }

    public function refresh() {
        $this->redirect($_SERVER['REQUEST_URI'],true,302);
    }

    public function reload() {
        $this->refresh();
    }

}

