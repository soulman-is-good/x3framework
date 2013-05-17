<?php
namespace X3;
/**
 * Controller
 *
 * @author Maxim <i@soulman.kz> Savin
 */
class Controller {

    /**
     * @var string plain action name 
     */
    protected $action = null;
    /**
     * @var X3_Renderer 
     */
    protected $template = null;

    public function __construct($action = null) {
        if(is_null($action))
            $this->action = 'index';
        else
            $this->action = $action;
//        $this->template = X3_Renderer::getInstance();
        if (!$this->handleFilters()) {
            $this->redirect(X3::app()->errorController);
        }
//        $this->init();
        return $this;
    }

    public function run() {
        $this->fire('beforeAction',array(&$this->action));
        $action = 'action' . ucfirst($this->action);
        if (($output = $this->handleCache()) !== false) {
            $this->fire('onRender',array(&$output));
            echo $output;
            $this->fire('afterAction');
        }else{            
            $this->$action();
            $this->fire('afterAction');
        }
    }
    
    /**
     * Returns array of definitions. Role->set of actions
     * @return array e.g. array(
     *       'allow'=>array(
     *           '*'=>array('index','show'),
     *           'creator'=>array('insert'),
     *           'editor'=>array('insert','update'),
     *           'admin'=>array('insert','update','delete')
     *       ),
     *       'handle'=>'redirect:site/index'
     *   );
     */
    public function filter() {
        return array();
    }

    public function route() {
        return array();
    }

    public function cache() {
        return array();
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
            if ((in_array($action, $aactions) || reset($aactions)=='*') || empty($nactions) || (!in_array($action, $nactions) && reset($nactions)!='*')) {
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
                            return X3::app()->cache->readCache($this->getId(),$action);
                        }
                    }
                }
            }
        }
        return false;
    }
    /**
     * Returns view renderer instance
     * @return X3_Renderer
     */
    public function getTemplate() {
        return $this->template;
    }

    /**
     * @return string plain controller's name
     */
    public function getId() {
        return (string)X3_String::create(get_class())->lcfirst();
    }

    /**
     * @return string plain action's name
     */
    public function getAction() {
        return $this->action;
    }
    
    /**
     * @param string $action link to an action property before it get handled as a function
     */
    public function beforeAction($action) {}
    /**
     * Here must be implemented code to be done after action's
     * @return boolean 
     */
    public function afterAction() {}

    /**
     * $statusCode could be
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

    /**
     * Refreshes current page
     */
    public function refresh() {
        $this->redirect($_SERVER['REQUEST_URI'],true,302);
    }
    
    /**
     * Alias to refresh() method
     * @see refresh() method
     */
    public function reload() {
        $this->refresh();
    }

}

