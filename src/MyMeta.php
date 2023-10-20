<?php
/**
 * @brief mymeta, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Jean-Christian Denis, Franck Paul and contributors
 *
 * @copyright Jean-Christian Denis, Franck Paul
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\mymeta;

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Interface\Core\BlogInterface;
use Dotclear\Interface\Core\BlogWorkspaceInterface;
use Dotclear\Interface\Core\ConnectionInterface;
use Dotclear\Interface\Core\MetaInterface;
use Exception;
use stdClass;

/**
 * Core myMeta class
 *
 * Handles all backend settings for myMeta storage
 *
 * @package MyMeta
 */
class MyMeta
{
    private ConnectionInterface $con;

    public MetaInterface $dcmeta;
    public BlogWorkspaceInterface $settings;

    /**
     * @var null|array<string, array<string, string>>
     */
    private static ?array $types = null;

    /**
     * @var null|array<string, string>
     */
    private static ?array $typesCombo = null;

    /**
     * @var array<int, mixed> mymeta list of mymeta entries, indexed by meta position
     */
    protected array $mymeta;

    /**
     * @var array<string, int> reference index for mymeta entries, indexed by meta ID
     */
    protected array $mymetaIDs;

    /**
     * @var string HTML errors list pattern
     */
    protected string $html_list = "<ul>\n%s</ul>\n";

    /**
     * @var string HTML error item pattern
     */
    protected string $html_item = "<li>%s</li>\n";

    protected int $sep_max;
    protected string $sep_prefix = '__sep__';

    /**
     * registerType
     *
     * Registers a new meta type. Must extend myMetaEntry class
     *
     * @param string $class class to register
     */
    public static function registerType(string $class): void
    {
        $sample                  = new $class();
        $desc                    = $sample->getMetaTypeDesc();  // @phpstan-ignore-line
        $type                    = $sample->getMetaTypeId();    // @phpstan-ignore-line
        self::$typesCombo[$desc] = $type;                       // @phpstan-ignore-line
        self::$types[$type]      = [                            // @phpstan-ignore-line
            'desc'   => $desc,
            'object' => $class,
        ];
    }

    /**
     * Default constructor
     *
     * Mymeta settings are retrieved from dc settings
     *
     * @param bool $bypass_settings  Get or not settings
     *
     * @return void
     */
    public function __construct(bool $bypass_settings = false)
    {
        $this->dcmeta   = App::meta();
        $this->settings = My::settings();

        $this->con = App::con();
        if (!$bypass_settings && $this->settings->mymeta_fields) {
            $this->mymeta = @unserialize(base64_decode($this->settings->mymeta_fields));
            if (!is_array($this->mymeta)) {
                $this->mymeta = [];
            }
        } else {
            $this->mymeta = [];
        }

        if (count($this->mymeta) > 0 && get_class(current($this->mymeta)) === stdClass::class) {
            // Redirect to admin home to perform upgrade, old settings detected
            $this->mymeta = [];
        } else {
            $this->mymetaIDs = [];
            $this->sep_max   = 0;
            foreach ($this->mymeta as $k => $v) {
                $this->mymetaIDs[$v->id] = (int) $k;    // @phpstan-ignore-line
                if ($v instanceof MyMetaSection) {
                    // Compute max section id, to anticipate
                    // future section ids
                    $sep_id = substr($v->id, strlen($this->sep_prefix));
                    if ($this->sep_max < (int) $sep_id) {
                        $this->sep_max = (int) $sep_id;
                    }
                }
            }
        }
    }

    /**
     * getTypesAsCombo
     *
     * Retrieves form-friendly registered mymeta types
     *
     * @return array<string, string>|null
     */
    public function getTypesAsCombo(): ?array
    {
        return MyMeta::$typesCombo;
    }

    /**
     * store
     *
     * Stores mymeta settings
     *
     * @return void
     */
    public function store(): void
    {
        $this->settings->put(
            'mymeta_fields',
            base64_encode(serialize($this->mymeta)),
            App::blogWorkspace()::NS_STRING,
            'MyMeta fields'
        );
    }

    /**
     * getAll
     *
     * Retrieves all mymeta, indexed by position
     *
     * @return array<int, mixed>
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
     *
     * @return MyMetaEntry the mymeta
     */
    public function getByPos(int $pos): MyMetaEntry
    {
        return $this->mymeta[$pos];
    }

    /**
     * getByID
     *
     * Retrieves a mymeta, given its ID
     *
     * @param String $id  the ID
     *
     * @return MyMetaEntry|null the mymeta
     */
    public function getByID(string $id): ?MyMetaEntry
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
     *
     * @return void
     */
    public function update($meta): void
    {
        $id = $meta->id;
        if (!isset($this->mymetaIDs[$id])) {
            // new id => create
            $this->mymeta[]       = $meta;
            $this->mymetaIDs[$id] = (int) sizeof($this->mymeta);    // @phpstan-ignore-line
        } else {
            // ID already exists => update
            $this->mymeta[$this->mymetaIDs[$id]] = $meta;
        }
    }

    public function newSection(): MyMetaSection
    {
        $this->sep_max++;
        $sep_id  = $this->sep_prefix . (string) $this->sep_max;
        $sep     = new MyMetaSection();
        $sep->id = $sep_id;

        return $sep;
    }

    /**
     * @param      array<string>|null  $order  The order
     */
    public function reorder(?array $order = null): void
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
            $newmymetaIDs[$m->id] = (int) count($newmymeta) - 1;
        }
        $this->mymeta    = $newmymeta;
        $this->mymetaIDs = $newmymetaIDs;   // @phpstan-ignore-line
    }

    /**
     * Deletes the given identifiers.
     *
     * @param      array<string>|string  $ids    The identifiers
     */
    public function delete($ids): void
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

    /**
     * Sets the enabled.
     *
     * @param      array<string>|string     $ids      The identifiers
     * @param      bool                     $enabled  The enabled
     */
    public function setEnabled($ids, bool $enabled): void
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

    /**
     * @param      string  $type   The type
     * @param      string  $id     The identifier
     *
     * @return     MyMetaField|null  My meta.
     */
    public function newMyMeta(string $type = 'string', string $id = ''): ?MyMetaField
    {
        if (!empty(MyMeta::$types[$type])) {
            return new MyMeta::$types[$type]['object']($id);    // @phpstan-ignore-line
        }

        return null;
    }

    public function isMetaEnabled(string $id): bool
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

    public function hasMeta(): bool
    {
        foreach ($this->mymeta as $meta) {
            if ($meta instanceof MyMetaField && $meta->enabled) {
                return true;
            }
        }

        return false;
    }

    public function postShowHeader(?MetaRecord $post, bool $standalone = false): string
    {
        $res = '';
        foreach ($this->mymeta as $meta) {
            if ($meta instanceof MyMetaField && $meta->enabled) {
                $res .= $meta->postHeader($post, $standalone);
            }
        }

        return $res;
    }

    public function postShowForm(?MetaRecord $post): string
    {
        $res             = '';
        $active_sections = ['' => true];
        $cur_section     = '';
        foreach ($this->mymeta as $meta) {
            if ($meta instanceof MyMetaSection) {
                $cur_section = $meta->id;
            } elseif ($meta->enabled) {
                $active_sections[$cur_section] = true;
            }
        }
        foreach ($this->mymeta as $meta) {
            if ($meta instanceof MyMetaSection) {
                if (isset($active_sections[$meta->id])) {
                    $res .= '<h4>' . __($meta->prompt) . '</h4>';
                }
            } elseif ($meta->enabled) {
                $display_item = true;
                if (!is_null($post) && $post->exists('post_type')) {
                    $display_item = $meta->isEnabledFor($post->post_type);
                } else {
                    // try to guess post_type from URI
                    $u         = explode('?', $_SERVER['REQUEST_URI']);
                    $post_type = '';
                    // TODO !!!
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

        return $res !== '' ? '<div class="mymeta"><details open><summary>' . __('My Meta') . '</summary>' . $res . '</details></div>' : '';
    }

    /**
     * Sets the meta.
     *
     * @param      int                      $post_id        The post identifier
     * @param      array<string, string>    $POST           The post
     * @param      bool                     $deleteIfEmpty  The delete if empty
     *
     * @throws     Exception
     */
    public function setMeta(int $post_id, array $POST, bool $deleteIfEmpty = true): void
    {
        $errors = [];
        foreach ($this->mymeta as $meta) {
            if ($meta instanceof MyMetaField && $meta->enabled) {
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

    public function getMyMetaStats(): MetaRecord
    {
        $table = App::con()->prefix() . MetaInterface::META_TABLE_NAME;

        $strReq = 'SELECT meta_type, COUNT(M.post_id) as count ' .
        'FROM ' . $table . ' M LEFT JOIN ' . App::con()->prefix() . 'post P ' .
        'ON M.post_id = P.post_id ' .
        "WHERE P.blog_id = '" . $this->con->escapeStr(App::blog()->id()) . "' ";

        if (!App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            $strReq .= 'AND ((post_status = ' . BlogInterface::POST_PUBLISHED . ' ';

            if (App::blog()->withoutPassword()) {
                $strReq .= 'AND post_password IS NULL ';
            }
            $strReq .= ') ';

            if (App::auth()->userID()) {
                $strReq .= "OR P.user_id = '" . $this->con->escapeStr(App::auth()->userID()) . "')";
            } else {
                $strReq .= ') ';
            }
        }

        $strReq .= 'GROUP BY meta_type,P.blog_id ' .
        'ORDER BY count DESC';

        $rs = new MetaRecord($this->con->select($strReq));
        $rs = $rs->toStatic();

        return $rs;
    }

    // Metadata generic requests

    /**
     * Gets the metadata.
     *
     * @param      array<string, mixed>     $params      The parameters
     * @param      bool                     $count_only  The count only
     *
     * @return     MetaRecord
     */
    public function getMetadata(array $params = [], bool $count_only = false): MetaRecord
    {
        if ($count_only) {
            $strReq = 'SELECT count(distinct M.meta_id) ';
        } else {
            $strReq = 'SELECT M.meta_id, M.meta_type, COUNT(M.post_id) as count ';
        }

        $strReq .= 'FROM ' . App::con()->prefix() . MetaInterface::META_TABLE_NAME . ' M LEFT JOIN ' . App::con()->prefix() . BlogInterface::POST_TABLE_NAME . ' P ' .
        'ON M.post_id = P.post_id ' .
        "WHERE P.blog_id = '" . $this->con->escapeStr(App::blog()->id()) . "' ";

        if (isset($params['meta_type'])) {
            $strReq .= " AND meta_type = '" . $this->con->escapeStr($params['meta_type']) . "' ";
        }

        if (isset($params['meta_id'])) {
            $strReq .= " AND meta_id = '" . $this->con->escapeStr($params['meta_id']) . "' ";
        }

        if (isset($params['post_id'])) {
            $strReq .= ' AND P.post_id ' . $this->con->in($params['post_id']) . ' ';
        }

        if (!App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            $strReq .= 'AND ((post_status = ' . BlogInterface::POST_PUBLISHED . ' ';

            if (App::blog()->withoutPassword()) {
                $strReq .= 'AND post_password IS NULL ';
            }
            $strReq .= ') ';

            if (App::auth()->userID()) {
                $strReq .= "OR P.user_id = '" . $this->con->escapeStr(App::auth()->userID()) . "')";
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

        return new MetaRecord($this->con->select($strReq));
    }

    public function getMeta(?string $type = null, ?string $limit = null, ?string $meta_id = null, ?int $post_id = null): MetaRecord
    {
        $params = [];

        if ($type != null) {
            $params['meta_type'] = $type;
        }
        if ($limit != null) {
            $params['limit'] = $limit;
        }
        if ($meta_id != null) {
            $params['meta_id'] = $meta_id;
        }
        if ($meta_id != null) {
            $params['post_id'] = $post_id;
        }
        $rs = $this->dcmeta->getMetadata($params, false);

        return $this->dcmeta->computeMetaStats($rs);
    }

    /**
     * Gets IDs as widget list.
     *
     * @return     array<string, string>
     */
    public function getIDsAsWidgetList(): array
    {
        $arr = [];
        foreach ($this->mymeta as $meta) {
            if ($meta instanceof MyMetaField && $meta->enabled) {
                $arr[$meta->id] = $meta->id;
            }
        }

        return $arr;
    }

    /**
     * Gets the sections as widget list.
     *
     * @return     array<string, string>
     */
    public function getSectionsAsWidgetList(): array
    {
        $arr = [];
        foreach ($this->mymeta as $meta) {
            if ($meta instanceof MyMetaSection) {
                $arr[$meta->prompt] = $meta->id;
            }
        }

        return $arr;
    }
}
