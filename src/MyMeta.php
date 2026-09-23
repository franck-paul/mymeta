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
use Dotclear\Helper\Html\Form\Details;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Summary;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;
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
    public MetaInterface $meta;

    public BlogWorkspaceInterface $settings;

    /**
     * @var null|array<string, array{desc: string, object: class-string}>
     */
    private static ?array $types = null;

    /**
     * @var null|array<string, string>
     */
    private static ?array $typesCombo = null;

    /**
     * @var array<int, MyMetaSection|MyMetaField>  $mymeta     list of mymeta entries, indexed by meta position
     */
    protected array $mymeta;

    /**
     * @var array<string, int> reference index for mymeta entries, indexed by meta ID
     */
    protected array $mymetaIDs;

    protected int $sep_max;

    protected string $sep_prefix = '__sep__';

    /**
     * registerType
     *
     * Registers a new meta type. Must extend myMetaEntry class
     *
     * @param class-string $class class to register
     */
    public static function registerType(string $class): void
    {
        /**
         * @var MyMetaEntry $sample
         */
        $sample = new $class();

        $desc = $sample->getMetaTypeDesc();
        $type = $sample->getMetaTypeId();

        self::$typesCombo[$desc] = $type;
        self::$types[$type]      = [
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
     */
    public function __construct(bool $bypass_settings = false)
    {
        $this->meta     = App::meta();
        $this->settings = My::settings();

        $fields        = [];
        $mymeta_fields = $this->settings->getStr('mymeta_fields', false);

        if (!$bypass_settings && $mymeta_fields !== '') {
            try {
                $mymeta_fields = base64_decode($mymeta_fields);
                /**
                 * @var false|array<int, MyMetaSection|MyMetaField>  $fields
                 */
                $fields = unserialize($mymeta_fields);
                if ($fields === false) {
                    $fields = [];
                }
            } catch (Exception) {
            }
        }

        $this->mymeta = $fields;

        $this->mymetaIDs = [];
        $this->sep_max   = 0;
        foreach ($this->mymeta as $k => $v) {
            $this->mymetaIDs[$v->id] = (int) $k;
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
     * @return array<int, MyMetaSection|MyMetaField>
     */
    public function getAll(): array
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
     * @return MyMetaSection|MyMetaField the mymeta
     */
    public function getByPos(int $pos): MyMetaSection|MyMetaField
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
     * @return MyMetaSection|MyMetaField|null the mymeta
     */
    public function getByID(string $id): null|MyMetaSection|MyMetaField
    {
        if (isset($this->mymetaIDs[$id])) {
            return $this->mymeta[$this->mymetaIDs[$id]];
        }

        return null;
    }

    /**
     * updates mymeta table with a given meta
     *
     * @param MyMetaSection|MyMetaField $meta the meta to store
     */
    public function update(MyMetaSection|MyMetaField $meta): void
    {
        $id = $meta->id;
        if (!isset($this->mymetaIDs[$id])) {
            // new id => create
            $this->mymeta[]       = $meta;
            $this->mymetaIDs[$id] = count($this->mymeta);
        } else {
            // ID already exists => update
            $this->mymeta[$this->mymetaIDs[$id]] = $meta;
        }
    }

    public function newSection(): MyMetaSection
    {
        ++$this->sep_max;
        $sep_id            = $this->sep_prefix . $this->sep_max;
        $myMetaSection     = new MyMetaSection();
        $myMetaSection->id = $sep_id;

        return $myMetaSection;
    }

    /**
     * @param      array<string>|null  $order  The order
     */
    public function reorder(?array $order = null): void
    {
        $pos          = 0;
        $newmymeta    = [];
        $newmymetaIDs = [];
        if ($order !== null) {
            foreach ($order as $id) {
                if (isset($this->mymetaIDs[$id])) {
                    $mymetum           = $this->mymeta[$this->mymetaIDs[$id]];
                    $mymetum->pos      = $pos++;
                    $newmymeta[]       = $mymetum;
                    $newmymetaIDs[$id] = count($newmymeta) - 1;
                    unset($this->mymeta[$this->mymetaIDs[$id]], $this->mymetaIDs[$id]);
                }
            }
        }

        // Just in case, if some items remain, add them
        foreach ($this->mymeta as $mymetum) {
            $mymetum->pos               = $pos++;
            $newmymeta[]                = $mymetum;
            $newmymetaIDs[$mymetum->id] = count($newmymeta) - 1;
        }

        $this->mymeta    = $newmymeta;
        $this->mymetaIDs = $newmymetaIDs;
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
                $pos = $this->mymetaIDs[$id];
                if ($this->mymeta[$pos] instanceof MyMetaField) {
                    $this->mymeta[$pos]->enabled = $enabled;
                }
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
            $mymeta_class = MyMeta::$types[$type]['object'];
            /**
             * @var MyMetaField
             */
            $instance = new $mymeta_class($id);

            return $instance;
        }

        return null;
    }

    public function isMetaEnabled(string $id): bool
    {
        if (!isset($this->mymetaIDs[$id])) {
            return false;
        }

        $pos = $this->mymetaIDs[$id];
        if (!empty($this->mymeta[$pos]) && $this->mymeta[$pos] instanceof MyMetaField) {
            return $this->mymeta[$pos]->enabled;
        }

        return false;
    }

    public function hasMeta(): bool
    {
        foreach ($this->mymeta as $mymetum) {
            if ($mymetum instanceof MyMetaField && $mymetum->enabled) {
                return true;
            }
        }

        return false;
    }

    public function postShowHeader(?MetaRecord $metaRecord, bool $standalone = false): string
    {
        $res = '';
        foreach ($this->mymeta as $mymetum) {
            if ($mymetum instanceof MyMetaField && $mymetum->enabled) {
                $res .= $mymetum->postHeader($metaRecord, $standalone);
            }
        }

        return $res;
    }

    public function postForm(?MetaRecord $metaRecord): None|Div
    {
        $items           = [];
        $active_sections = ['' => true];
        $cur_section     = '';
        foreach ($this->mymeta as $mymetum) {
            if ($mymetum instanceof MyMetaSection) {
                $cur_section = $mymetum->id;
            } elseif ($mymetum->enabled) {
                $active_sections[$cur_section] = true;
            }
        }

        foreach ($this->mymeta as $mymetum) {
            if ($mymetum instanceof MyMetaSection) {
                if (isset($active_sections[$mymetum->id])) {
                    $items[] = (new Text('h4', __($mymetum->prompt)));
                }
            } elseif ($mymetum->enabled) {
                $display_item = true;
                $post_type    = $metaRecord instanceof MetaRecord ? $metaRecord->strField('post_type') : '';
                if ($post_type === '') {
                    // Try to guess post_type from URI (only post and page are currently looked for)
                    $uri = is_string($uri = $_SERVER['REQUEST_URI']) ? $uri : '';
                    if ($uri !== '') {
                        $query = parse_url($uri, PHP_URL_QUERY);
                        $args  = [];
                        if (is_string($query)) {
                            parse_str($query, $args);
                        }

                        if (isset($args['Process']) && $args['Process'] === 'Post') {
                            $post_type = 'post';
                        } elseif (isset($args['Process'])
                            && $args['Process'] === 'Plugin'
                            && isset($args['p'])
                            && $args['p'] === 'pages'
                        ) {
                            $post_type = 'page';
                        }
                    }
                }

                if ($post_type !== '') {
                    $display_item = $mymetum->isEnabledFor($post_type);
                }

                if ($display_item) {
                    $items[] = $mymetum->postForm($this->meta, $metaRecord);
                }
            }
        }

        if ($items !== []) {
            return (new Div())
                ->class('mymeta')
                ->items([
                    (new Details())
                        ->open(true)
                        ->summary(new Summary(__('My Meta')))
                        ->items([
                            (new Fieldset())
                                ->fields($items),
                        ]),
                ]);
        }

        return (new None());
    }

    /**
     * Sets the meta.
     *
     * @param      int                      $post_id        The post identifier
     * @param      bool                     $deleteIfEmpty  The delete if empty
     *
     * @throws     Exception
     */
    public function setMeta(int $post_id, bool $deleteIfEmpty = true): void
    {
        /**
         * @var array<array-key, string>
         */
        $errors = [];
        foreach ($this->mymeta as $mymetum) {
            $post_type = isset($_POST['post_type']) && is_string($post_type = $_POST['post_type']) ? $post_type : '';
            if ($mymetum instanceof MyMetaField
                && $mymetum->enabled
                && ($post_type === '' || $mymetum->isEnabledFor($post_type))
            ) {
                try {
                    $mymetum->setPostMeta($this->meta, $post_id, $deleteIfEmpty);
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }

        if ($errors !== []) {
            $items = function (array $errors) {
                foreach ($errors as $error) {
                    if (is_string($error)) {
                        yield (new Li())
                            ->text($error);
                    }
                }
            };
            $message = (new Ul())
                ->items([
                    ... $items($errors),
                ])
            ->render();

            throw new Exception(__('Metadata errors :') . $message);
        }
    }

    // DB requests

    public function getMyMetaStats(): MetaRecord
    {
        $table = App::db()->con()->prefix() . App::meta()::META_TABLE_NAME;

        $selectStatement = new SelectStatement();
        $selectStatement
            ->columns([
                'meta_type',
                $selectStatement->count('M.post_id', 'count'),
            ])
            ->from($selectStatement->as($table, 'M'))
            ->join(
                (new JoinStatement())
                    ->left()
                    ->from($selectStatement->as(App::db()->con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'))
                    ->on('M.post_id = P.post_id')
                    ->statement()
            )
            ->where('P.blog_id = ' . $selectStatement->quote(App::blog()->id()))
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
            $and[] = 'post_status = ' . App::status()->post()::PUBLISHED;

            if (App::blog()->withoutPassword()) {
                // Only without password posts
                $and[] = $selectStatement->isNull('post_password');
            }

            if (App::auth()->userID()) {
                $selectStatement->and($selectStatement->orGroup([
                    $selectStatement->andGroup($and),
                    'P.user_id = ' . $selectStatement->quote(App::auth()->userID()),
                ]));
            } else {
                $selectStatement->and($and);
            }
        }

        $rs = $selectStatement->select() ?? MetaRecord::newFromArray([]);

        return $rs->toStatic();
    }

    // Metadata generic requests

    /**
     * Gets the metadata.
     *
     * @param      array<string, mixed>     $params      The parameters
     * @param      bool                     $count_only  The count only
     */
    public function getMetadata(array $params = [], bool $count_only = false): MetaRecord
    {
        $selectStatement = new SelectStatement();

        if ($count_only) {
            $selectStatement->column($selectStatement->count('DISTINCT M.meta_id'));
        } else {
            $selectStatement->columns([
                'M.meta_id',
                'M.meta_type',
                $selectStatement->count('M.post_id', 'count'),
            ]);
        }

        $selectStatement
            ->from($selectStatement->as(App::db()->con()->prefix() . App::meta()::META_TABLE_NAME, 'M'))
            ->join(
                (new JoinStatement())
                    ->left()
                    ->from($selectStatement->as(App::db()->con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'))
                    ->on('M.post_id = P.post_id')
                    ->statement()
            )
            ->where('P.blog_id = ' . $selectStatement->quote(App::blog()->id()))
        ;

        if (isset($params['meta_type']) && is_string($params['meta_type'])) {
            $selectStatement->and('meta_type = ' . $selectStatement->quote($params['meta_type']));
        }

        if (isset($params['meta_id']) && is_string($params['meta_id'])) {
            $selectStatement->and('meta_id = ' . $selectStatement->quote($params['meta_id']));
        }

        if (isset($params['post_id']) && (is_numeric($params['post_id']) || is_array($params['post_id']))) {
            $params_ids = $selectStatement->sanitizeIn($params['post_id'], 'int', false);
            if ($params_ids !== []) {
                $selectStatement->and('P.post_id ' . $selectStatement->in($params_ids));
            }
        }

        if (!App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            // No content admin permission on current blog
            // Use only published posts restricted to user ID if set
            $and[] = 'post_status = ' . App::status()->post()::PUBLISHED;

            if (App::blog()->withoutPassword()) {
                // Only without password posts
                $and[] = $selectStatement->isNull('post_password');
            }

            if (App::auth()->userID()) {
                $selectStatement->and($selectStatement->orGroup([
                    $selectStatement->andGroup($and),
                    'P.user_id = ' . $selectStatement->quote(App::auth()->userID()),
                ]));
            } else {
                $selectStatement->and($and);
            }
        }

        if (!$count_only) {
            $selectStatement->group([
                'meta_id',
                'meta_type',
                'P.blog_id',
            ]);
        }

        if (!$count_only
            && isset($params['order'])
            && is_string($params['order'])
        ) {
            $selectStatement->order($selectStatement->escape($params['order']));
        }

        if (isset($params['limit']) && !$count_only) {
            /**
             * @var list<string|int|null>   $values
             */
            $values = is_array($params['limit']) ? array_values($params['limit']) : [$params['limit']];
            // Make $values an array of integer values
            $values = array_map(fn (int|string|null $v): int => (int) $v, $values);

            /**
             * @var array{0: int, 1?: int}  $limit
             */
            $limit = [
                $values[0],
            ];
            if (isset($values[1])) {
                $limit[1] = $values[1];
            }

            $selectStatement->limit($limit);
        }

        return $selectStatement->select() ?? MetaRecord::newFromArray([]);
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

        $rs = $this->meta->getMetadata($params, false);

        return $this->meta->computeMetaStats($rs);
    }

    /**
     * Gets IDs as widget list.
     *
     * @return     array<string, string>
     */
    public function getIDsAsWidgetList(): array
    {
        $arr = [];
        foreach ($this->mymeta as $mymetum) {
            if ($mymetum instanceof MyMetaField && $mymetum->enabled) {
                $arr[$mymetum->id] = $mymetum->id;
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
        foreach ($this->mymeta as $mymetum) {
            if ($mymetum instanceof MyMetaSection) {
                $arr[$mymetum->prompt] = $mymetum->id;
            }
        }

        return $arr;
    }
}
