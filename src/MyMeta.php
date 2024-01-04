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
use Dotclear\Database\Statement\JoinStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Interface\Core\BlogWorkspaceInterface;
use Dotclear\Interface\Core\MetaInterface;
use Exception;

/**
 * Core myMeta class
 *
 * Handles all backend settings for myMeta storage
 *
 * @package MyMeta
 */
class MyMeta
{
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
    protected array $mymeta = [];

    /**
     * @var array<string, int> reference index for mymeta entries, indexed by meta ID
     */
    protected array $mymetaIDs = [];

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

        if (!$bypass_settings && $this->settings->mymeta_fields) {
            $value = @unserialize((string) base64_decode($this->settings->mymeta_fields)) ?? [];
            if (!$value || !is_array($value)) {
                $this->mymeta = [];
            } else {
                $this->mymeta = $value;
            }
        } else {
            $this->mymeta = [];
        }

        if ($this->mymeta !== [] && current($this->mymeta) instanceof \stdClass) {
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
            $this->mymetaIDs[$id] = (int) count($this->mymeta);    // @phpstan-ignore-line
        } else {
            // ID already exists => update
            $this->mymeta[$this->mymetaIDs[$id]] = $meta;
        }
    }

    public function newSection(): MyMetaSection
    {
        ++$this->sep_max;
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
            if ($meta instanceof MyMetaField && $meta->enabled && (!isset($POST['post_type']) || $meta->isEnabledFor($POST['post_type']))) {
                try {
                    $meta->setPostMeta($this->dcmeta, $post_id, $POST, $deleteIfEmpty);
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
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
        $table = App::con()->prefix() . App::meta()::META_TABLE_NAME;

        $sql = new SelectStatement();
        $sql
            ->columns([
                'meta_type',
                $sql->count('M.post_id', 'count'),
            ])
            ->from($sql->as($table, 'M'))
            ->join(
                (new JoinStatement())
                    ->left()
                    ->from($sql->as(App::con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'))
                    ->on('M.post_id = P.post_id')
                    ->statement()
            )
            ->where('P.blog_id = ' . $sql->quote(App::blog()->id()))
            ->group([
                'meta_type',
                'P.blog_id',
            ])
            ->order('count DESC')
        ;

        if (!App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            // No content admin permission on current blog
            // Use only published posts restricted to user ID if set
            $and[] = 'post_status = ' . App::blog()::POST_PUBLISHED;

            if (App::blog()->withoutPassword()) {
                // Only without password posts
                $and[] = $sql->isNull('post_password');
            }

            if (App::auth()->userID()) {
                $sql->and($sql->orGroup([
                    $sql->andGroup($and),
                    'P.user_id = ' . $sql->quote(App::auth()->userID()),
                ]));
            } else {
                $sql->and($and);
            }
        }

        $rs = $sql->select() ?? MetaRecord::newFromArray([]);

        return $rs->toStatic();
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
        $sql = new SelectStatement();

        if ($count_only) {
            $sql->column($sql->count('DISTINCT M.meta_id'));
        } else {
            $sql->columns([
                'M.meta_id',
                'M.meta_type',
                $sql->count('M.post_id', 'count'),
            ]);
        }

        $sql
            ->from($sql->as(App::con()->prefix() . App::meta()::META_TABLE_NAME, 'M'))
            ->join(
                (new JoinStatement())
                    ->left()
                    ->from($sql->as(App::con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'))
                    ->on('M.post_id = P.post_id')
                    ->statement()
            )
            ->where('P.blog_id = ' . $sql->quote(App::blog()->id()))
        ;

        if (isset($params['meta_type'])) {
            $sql->and('meta_type = ' . $sql->quote($params['meta_type']));
        }

        if (isset($params['meta_id'])) {
            $sql->and('meta_id = ' . $sql->quote($params['meta_id']));
        }

        if (isset($params['post_id'])) {
            $sql->and('P.post_id ' . $sql->in($params['post_id']));
        }

        if (!App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            // No content admin permission on current blog
            // Use only published posts restricted to user ID if set
            $and[] = 'post_status = ' . App::blog()::POST_PUBLISHED;

            if (App::blog()->withoutPassword()) {
                // Only without password posts
                $and[] = $sql->isNull('post_password');
            }

            if (App::auth()->userID()) {
                $sql->and($sql->orGroup([
                    $sql->andGroup($and),
                    'P.user_id = ' . $sql->quote(App::auth()->userID()),
                ]));
            } else {
                $sql->and($and);
            }
        }

        if (!$count_only) {
            $sql->group([
                'meta_id',
                'meta_type',
                'P.blog_id',
            ]);
        }

        if (!$count_only && isset($params['order'])) {
            $sql->order($sql->escape((string) $params['order']));
        }

        if (isset($params['limit']) && !$count_only) {
            $sql->limit($params['limit']);
        }

        return $sql->select() ?? MetaRecord::newFromArray([]);
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
