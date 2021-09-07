<?php
/**
 * @brief mymeta, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Bruno Hondelatte and contributors
 *
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Core myMeta class
 *
 * Handles all backend settings for myMeta storage
 *
 * @package MyMeta
 */
class myMeta
{
    private $core;
    private $con;
    public $dcmeta;
    public $settings;

    /** @var array types registered mymeta types */
    private static $types;

    /** @var array typesCombo registered mymeta types, as combo for form display */
    private static $typesCombo;

    /** @var array mymeta list of mymeta entries, indexed by meta position*/
    protected $mymeta;

    /** @var array mymetaIDs, reference index for mymeta entries, indexed by meta ID */
    protected $mymetaIDs;
    protected $html_list = "<ul>\n%s</ul>\n";       ///< <b>string</b>      HTML errors list pattern
    protected $html_item = "<li>%s</li>\n"; ///< <b>string</b>      HTML error item pattern

    protected $sep_prefix = '__sep__';

    /**
     * registerType
     *
     * Registers a new meta type. Must extend myMetaEntry class
     *
     * @param myMetaEntry $class class to register
     * @static
     * @access public
     * @return void
     */
    public static function registerType($class)
    {
        $sample                    = new $class();
        $desc                      = $sample->getMetaTypeDesc();
        $type                      = $sample->getMetaTypeId();
        myMeta::$typesCombo[$desc] = $type;
        myMeta::$types[$type]      = [
            'desc'   => $desc,
            'object' => $class];
    }

    /**
     * Default constructor
     *
     * Mymeta settings are retrieved from dc settings
     *
     * @param mixed $core core to reference
     * @access public
     * @return void
     */
    public function __construct($core, $bypass_settings = false)
    {
        $this->core = & $core;
        if (isset($core->meta)) {
            $this->dcmeta = & $core->meta;
        } else {
            $this->dcmeta = new dcMeta($core);
        }

        if (version_compare(DC_VERSION, '2.2-alpha', '>=')) {
            $core->blog->settings->addNamespace('mymeta');
            $this->settings = & $core->blog->settings->mymeta;
        } else {
            $core->blog->settings->setNamespace('mymeta');
            $this->settings = & $core->blog->settings;
        }

        $this->con = & $this->core->con;
        if (!$bypass_settings && $this->settings->mymeta_fields) {
            $this->mymeta = @unserialize(base64_decode($this->settings->mymeta_fields));
            if (!is_array($this->mymeta)) {
                $this->mymeta = [];
            }
        } else {
            $this->mymeta = [];
        }

        // Redirect to admin home to perform upgrade, old settings detected
        if (count($this->mymeta) > 0 && get_class(current($this->mymeta)) == 'stdClass') {
            $this->mymeta = [];

            return $this;
        }

        $this->mymetaIDs = [];
        $this->sep_max   = 0;
        foreach ($this->mymeta as $k => $v) {
            $this->mymetaIDs[$v->id] = $k;
            if ($v instanceof myMetaSection) {
                // Compute max section id, to anticipate
                // future section ids
                $sep_id = substr($v->id, strlen($this->sep_prefix));
                if ($this->sep_max < $sep_id) {
                    $this->sep_max = $sep_id;
                }
            }
        }
    }

    /**
     * getTypesAsCombo
     *
     * Retrieves form-friendly registered mymeta types
     *
     * @access public
     * @return void
     */
    public function getTypesAsCombo()
    {
        $combo = myMeta::$typesCombo;

        return $combo;
    }

    /**
     * store
     *
     * Stores mymeta settings
     *
     * @access public
     * @return void
     */
    public function store()
    {
        $this->settings->put(
            'mymeta_fields',
            base64_encode(serialize($this->mymeta)),
            'string',
            'MyMeta fields');
    }

    /**
     * getAll
     *
     * Retrieves all mymeta, indexed by position
     *
     * @access public
     * @return void
     */
    public function getAll()
    {
        return $this->mymeta;
    }

    /**
     * getByPos
     *
     * Retrieves a mymeta, given its position
     *
     * @param int $pos  the position
     * @access public
     * @return mymetaEntry the mymeta
     */
    public function getByPos($pos)
    {
        return $this->mymeta[$pos];
    }

    /**
     * getByID
     *
     * Retrieves a mymeta, given its ID
     *
     * @param String $id  the ID
     * @access public
     * @return mymetaEntry the mymeta
     */
    public function getByID($id)
    {
        if (isset($this->mymetaIDs[$id])) {
            return $this->mymeta[$this->mymetaIDs[$id]];
        }

        return null;
    }

    /**
     * updates mymeta table with a given meta
     *
     * @param mixed $meta the meta to store
     * @access public
     * @return void
     */
    public function update($meta)
    {
        $id = $meta->id;
        if (!isset($this->mymetaIDs[$id])) {
            // new id => create
            $this->mymeta[]       = $meta;
            $this->mymetaIDs[$id] = sizeof($this->mymeta);
        } else {
            // ID already exists => update
            $this->mymeta[$this->mymetaIDs[$id]] = $meta;
        }
    }

    public function newSection()
    {
        $this->sep_max++;
        $sep_id  = $this->sep_prefix . $this->sep_max;
        $sep     = new myMetaSection();
        $sep->id = $sep_id;

        return $sep;
    }

    public function reorder($order = null)
    {
        $pos          = 0;
        $newmymeta    = [];
        $newmymetaIDs = [];
        if ($order != null) {
            foreach ($order as $id) {
                if (isset($this->mymetaIDs[$id])) {
                    $m                 = $this->mymeta[$this->mymetaIDs[$id]];
                    $m->pos            = $pos++;
                    $newmymeta[]       = $m;
                    $newmymetaIDs[$id] = count($newmymeta) - 1;
                    unset($this->mymeta[$this->mymetaIDs[$id]], $this->mymetaIDs[$id]);
                }
            }
        }
        // Just in case, if some items remain, add them
        foreach ($this->mymeta as $m) {
            $m->pos               = $pos++;
            $newmymeta[]          = $m;
            $newmymetaIDs[$m->id] = count($newmymeta) - 1;
        }
        $this->mymeta    = $newmymeta;
        $this->mymetaIDs = $newmymetaIDs;
    }
    public function delete($ids)
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        foreach ($ids as $id) {
            if (isset($this->mymetaIDs[$id])) {
                $pos = $this->mymetaIDs[$id];
                unset($this->mymeta[$pos]);
            }
        }
    }

    public function setEnabled($ids, $enabled)
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        foreach ($ids as $id) {
            if (isset($this->mymetaIDs[$id])) {
                $pos                         = $this->mymetaIDs[$id];
                $this->mymeta[$pos]->enabled = $enabled;
            }
        }
    }

    public function newMyMeta($type = 'string', $id = '')
    {
        if (!empty(myMeta::$types[$type])) {
            return new myMeta::$types[$type]['object']($id);
        }

        return null;
    }

    public function isMetaEnabled($id)
    {
        if (!isset($this->mymetaIDs[$id])) {
            return false;
        }
        $pos = $this->mymetaIDs[$id];
        if (!empty($this->mymeta[$pos])) {
            return $this->mymeta[$pos]->enabled;
        }

        return false;
    }

    public function hasMeta()
    {
        foreach ($this->mymeta as $id => $meta) {
            if ($meta instanceof myMetaField && $meta->enabled) {
                return true;
            }
        }

        return false;
    }

    public function postShowHeader($post, $standalone = false)
    {
        $res = '';
        foreach ($this->mymeta as $meta) {
            if ($meta instanceof myMetaField && $meta->enabled) {
                $res .= $meta->postHeader($post, $standalone);
            }
        }

        return $res;
    }

    public function postShowForm($post)
    {
        $res             = '';
        $inSection       = false;
        $active          = false;
        $active_sections = ['' => true];
        $cur_section     = '';
        foreach ($this->mymeta as $id => $meta) {
            if ($meta instanceof myMetaSection) {
                $cur_section = $meta->id;
            } elseif ($meta->enabled) {
                $active_sections[$cur_section] = true;
                $active                        = true;
            }
        }
        if (!count($active_sections)) {
            return;
        }
        $res .= '<div class="fieldset"><h3>' . __('My Meta') . '</h3>';
        foreach ($this->mymeta as $id => $meta) {
            if ($meta instanceof myMetaSection) {
                if (isset($active_sections[$meta->id])) {
                    $res .= '<h4>' . __($meta->prompt) . '</h4>';
                }
            } elseif ($meta->enabled) {
                $display_item = true;
                if (isset($post) && $post->exists('post_type')) {
                    $display_item = $meta->isEnabledFor($post->post_type);
                } else {
                    // try to guess post_type from URI
                    $u         = explode('?', $_SERVER['REQUEST_URI']);
                    $post_type = '';
                    if (basename($u[0]) == 'post.php') {
                        $post_type = 'post';
                    } elseif (basename($u[0]) == 'plugin.php') {
                        parse_str($u[1], $p);
                    }
                    $display_item = $meta->isEnabledFor($post_type);
                }

                if ($display_item) {
                    $res .= $meta->postShowForm($this->dcmeta, $post);
                }
            }
        }
        $res .= '</div>';

        return $res;
    }

    public function setMeta($post_id, $POST, $deleteIfEmpty = true)
    {
        $errors = [];
        foreach ($this->mymeta as $meta) {
            if ($meta instanceof myMetaField && $meta->enabled) {
                if (!isset($POST['post_type']) || $meta->isEnabledFor($POST['post_type'])) {
                    try {
                        $meta->setPostMeta($this->dcmeta, $post_id, $POST, $deleteIfEmpty);
                    } catch (Exception $e) {
                        $errors[] = $e->getMessage();
                    }
                }
            }
        }
        if (count($errors) != 0) {
            $res = '';
            foreach ($errors as $msg) {
                $res .= sprintf($this->html_item, $msg);
            }

            throw new Exception(__('Mymeta errors :') . sprintf($this->html_list, $res));
        }
    }

    // DB requests
    public function getMyMetaStats()
    {
        $table = $this->core->prefix . 'meta';

        $strReq = 'SELECT meta_type, COUNT(M.post_id) as count ' .
        'FROM ' . $table . ' M LEFT JOIN ' . $this->core->prefix . 'post P ' .
        'ON M.post_id = P.post_id ' .
        "WHERE P.blog_id = '" . $this->con->escape($this->core->blog->id) . "' ";

        if (!$this->core->auth->check('contentadmin', $this->core->blog->id)) {
            $strReq .= 'AND ((post_status = 1 ';

            if ($this->core->blog->without_password) {
                $strReq .= 'AND post_password IS NULL ';
            }
            $strReq .= ') ';

            if ($this->core->auth->userID()) {
                $strReq .= "OR P.user_id = '" . $this->con->escape($this->core->auth->userID()) . "')";
            } else {
                $strReq .= ') ';
            }
        }

        $strReq .= 'GROUP BY meta_type,P.blog_id ' .
        'ORDER BY count DESC';

        $rs = $this->con->select($strReq);
        $rs = $rs->toStatic();

        return $rs;
    }

    // Metadata generic requests
    public function getMetadata($params = [], $count_only = false)
    {
        if ($count_only) {
            $strReq = 'SELECT count(distinct M.meta_id) ';
        } else {
            $strReq = 'SELECT M.meta_id, M.meta_type, COUNT(M.post_id) as count ';
        }

        $strReq .= 'FROM ' . $this->core->prefix . 'meta M LEFT JOIN ' . $this->core->prefix . 'post P ' .
        'ON M.post_id = P.post_id ' .
        "WHERE P.blog_id = '" . $this->con->escape($this->core->blog->id) . "' ";

        if (isset($params['meta_type'])) {
            $strReq .= " AND meta_type = '" . $this->con->escape($params['meta_type']) . "' ";
        }

        if (isset($params['meta_id'])) {
            $strReq .= " AND meta_id = '" . $this->con->escape($params['meta_id']) . "' ";
        }

        if (isset($params['post_id'])) {
            $strReq .= ' AND P.post_id ' . $this->con->in($params['post_id']) . ' ';
        }

        if (!$this->core->auth->check('contentadmin', $this->core->blog->id)) {
            $strReq .= 'AND ((post_status = 1 ';

            if ($this->core->blog->without_password) {
                $strReq .= 'AND post_password IS NULL ';
            }
            $strReq .= ') ';

            if ($this->core->auth->userID()) {
                $strReq .= "OR P.user_id = '" . $this->con->escape($this->core->auth->userID()) . "')";
            } else {
                $strReq .= ') ';
            }
        }

        if (!$count_only) {
            $strReq .= 'GROUP BY meta_id,meta_type,P.blog_id ';
        }

        if (!$count_only && isset($params['order'])) {
            $strReq .= 'ORDER BY ' . $params['order'];
        }

        if (isset($params['limit']) && !$count_only) {
            $strReq .= $this->con->limit($params['limit']);
        }
        $rs = $this->con->select($strReq);

        return $rs;
    }

    public function getIDsAsWidgetList()
    {
        $arr = [];
        foreach ($this->mymeta as $k => $meta) {
            if ($meta instanceof myMetaField && $meta->enabled) {
                $arr[$meta->id] = $meta->id;
            }
        }

        return $arr;
    }

    public function getSectionsAsWidgetList()
    {
        $arr = [];
        foreach ($this->mymeta as $k => $meta) {
            if ($meta instanceof myMetaSection) {
                $arr[$meta->prompt] = $meta->id;
            }
        }

        return $arr;
    }
}

require_once dirname(__FILE__) . '/class.mymetatypes.php';
