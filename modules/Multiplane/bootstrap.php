<?php
//set version
if (!$this->retrieve('multiplane/version', false)) {
    $this->set('multiplane/version', $this['debug'] ? time()
        : \json_decode($this('fs')->read(MP_DIR.'/package.json'), true)['version']);
}
$this->set('cockpit/version', \json_decode($this('fs')->read('#root:package.json'), true)['version']);

if (!MP_SELF_EXPORT) {
    require_once(__DIR__ . '/override.php');
}

// set config path
$this->path('mp_config', MP_ENV_ROOT . '/config');

spl_autoload_register(function($class){

    // register autoload classes in namespace Multiplane\Controller from
    // `MP_DIR/Controller`, e. g.: `/Controller/Products.php`
    $class_path = MP_ENV_ROOT.'/Controller'.str_replace(['Multiplane\Controller', '\\'], ['', '/'], $class).'.php';
    if (\file_exists($class_path)) include_once($class_path);

    // autoload from /modules/Multiplane/lib
    $class_path = __DIR__.'/lib/'.$class.'.php';
    if (\file_exists($class_path)) include_once($class_path);

});

// add helpers
$this->helpers['fields'] = 'Multiplane\\Helper\\Fields';
$this->helpers['search'] = 'Multiplane\\Helper\\Search';


$this->module('multiplane')->extend([

    // base config
    'theme'                 => 'rljbase',
    'parentTheme'           => null,
    'parentThemeBootstrap'  => true,

    'isMultilingual'        => false,
    'usePermalinks'         => false,
    'usePermalinksAsSlugs'  => false,
    'disableDefaultRoutes'  => false,             // don't use any default routes
    'outputMethod'          => 'dynamic',         // to do: static or pseudo static/cached
    'pageTypeDetection'     => 'collections',     // 'collections' or 'type' (experimental)
    'nav'                   => null,              // hard coded navigation

    'slugName'              => '_id',             // deprecated, field name for url slug
    'navName'               => 'nav',             // deprecated, field name for navigation

    'fieldNames' => [                             // field mappings to default field names
        'slug'      => '_id',
        'nav'       => 'nav',
        'permalink' => 'permalink',
        'published' => 'published',
        'startpage' => 'startpage',
        'title'     => 'title',
        'content'   => 'content',
        'type'      => 'type', // only if pageTypeDetection == 'type'
        'subpagemodule' => 'subpagemodule',
    ],

    'use' => [
        'collections' => [],                      // list of collection names
        'singletons'  => [],                      // list of singleton names
        'forms'       => [],                      // list of form names
    ],

    // maintenance mode
    'isInMaintenanceMode'   => false,             // display under construction page with 503 status
    'allowedIpsInMaintenanceMode' => null,        // separate multiple ip addresses with whitespaces

    'styles'                => [],                // access via mp()->userStyles();
    'scripts'               => [],                // access via mp()->userScripts();

    // use Fields render helper and optional field templates
    'preRenderFields'       => [],

    'site'                  => [],                // default site config
    'siteSingleton'         => '',                // singleton name for default config

    'pages'                 => '',                // collection name for pages
    // 'pagesPattern'          => '{title}',         // to do...

    'posts'                 => '',                // collection name for posts
    // 'postsPattern'          => '{collection}/{title}',        // to do...
    // 'postsPattern'         => '{YYYY}/{MM}/{DD}/{title}',  // to do...

    // content preview
    'isPreviewEnabled'      => false,
    'previewMethod'         => 'html',            // the inbuilt live preview renders the main part as html
    'livePreviewToken'      => md5(__DIR__),
    'previewDelay'          => 0,
    'previewScripts'        => false,           // restart MP init scripts

    // pagination
    'displayPostsLimit'     => 5,               // number of posts to display in subpagemodule
    'paginationDropdownLimit' => 5,             // number of pages, when the pagination turns to dropdown menu

    'lexy'                  => [],              // extend Lexy parser for image url templates

    // breadcrumbs
    'displayBreadcrumbs'    => false,

    // experimental full text search
    'search' => [
        'enabled'     => false,
        'minLength'   => 3,                     // minimum character length for search
        'collections' => [],                    // full list of collections to search in,
                                                // defaults to "multiplane/use/collections"
    ],

    'sitemap'               => null,            // array of collections

    'hasBackgroundImage'    => false,           // enable background image
    'backgroundBreakpoints' => [
        'mini' => [
            'points' => [
                'max-width' => 500,
            ],
            'size' => 700,
        ],
        'small' => [
            'points' => [
                'min-width' => 500,
                'max-width' => 1000,
            ],
            'size' => 1200,
        ],
        'normal' => [
            'points' => [
                'min-width' => 1000,
                'max-width' => 1200,
            ],
            'size' => 1400,
        ],
        'large' => [
            'points' => [
                'min-width' => 1200,
            ],
            'size' => 1920,
        ],
    ],

    // changes dynamically
    'defaultLang'           => $this->retrieve('multiplane/i18n', $this->retrieve('i18n', 'en')),
    'lang'                  => $this('i18n')->locale,
    'breadcrumbs'           => [['title' => $this('i18n')->get('Home'), 'slug' => '/']],
    'isStartpage'           => false,
    'collection'            => null,            // current collection
    'clientIpIsAllowed'     => false,           // if maintenance and ip is allowed
    'hasParentPage'         => false,           // for sub pages and pagination
    'parentPage'            => null,            // contains info about parent page
    'themePath'             => null,
    'parentThemePath'       => null,

    'set' => function($key, $value) {

        $this->$key = $value;

    }, // end of set()

    'add' => function($key, $value, $recursive = false) {

        if (\is_array($this->$key)) {
            if ($recursive) $this->$key = \array_merge_recursive($this->$key, $value);
            else            $this->$key = \array_merge($this->$key, $value);
        }

        elseif (\is_string($this->$key) && \is_string($value)) {
            $this->$key .= $value;
        }

        else {
            // do nothing
        }

    }, // end of add()

    // modified version of Lime\fetch_from_array()
    'get' => function($index, $default = null) {

        if (\is_null($index)) {

            return null;

        } elseif (isset($this->$index)) {

            return $this->$index;

        } elseif (\strpos($index, '/')) {

            $keys = \explode('/', $index);

            switch (\count($keys)){

                case 1:
                    if (isset($this->{$keys[0]})){
                        return $this->{$keys[0]};
                    }
                    break;

                case 2:
                    if (isset($this->{$keys[0]}[$keys[1]])){
                        return $this->{$keys[0]}[$keys[1]];
                    }
                    break;

                case 3:
                    if (isset($this->{$keys[0]}[$keys[1]][$keys[2]])){
                        return $this->{$keys[0]}[$keys[1]][$keys[2]];
                    }
                    break;

                case 4:
                    if (isset($this->{$keys[0]}[$keys[1]][$keys[2]][$keys[3]])){
                        return $this->{$keys[0]}[$keys[1]][$keys[2]][$keys[3]];
                    }
                    break;
            }
        }

        return \is_callable($default) ? \call_user_func($default) : $default;

    }, // end of get()

    'getSite' => function() {

        $site = $this->app->module('singletons')->getData($this->siteSingleton, ['lang' => $this->lang]);

        if ($site && \is_array($site)) $this->site = $site;

        return $site;

    }, // end of getSite()

    'getPage' => function($_slug = '') {

        $slug = $this->resolveSlug($_slug);
        $collection = $this->collection;

        $startpageName = $this->fieldNames['startpage'];

        // startpage
        if (empty($slug)) {

            $this->isStartpage = true;

            $filter = [
                $this->fieldNames['published'] => true,
                $startpageName => true,
            ];

        }
        // filter by slug
        else {
            $filter = [
                'published' => true,
            ];

            $slugName = $this->fieldNames['slug'];

            if (!$this->isMultilingual) {
                $filter[$slugName] = $slug;
            }
            else {
                // filter by localized slug
                $lang = $this->lang;

                $isLocalized = $this->isCollectionLocalized($collection);

                if ($slugName != '_id' && $isLocalized && $lang != $this->defaultLang) {
                    $filter[$slugName.'_'.$lang] = $slug;
                } else {
                    $filter[$slugName] = $slug;
                }
            }
        }

        $projection = null;
        $populate = false;
        $fieldsFilter = ['lang' => $this->lang];

        $this->app->trigger('multiplane.getpage.before', [$collection, &$filter, &$projection, &$populate, &$fieldsFilter]);

        $page = $this->app->module('collections')->findOne($collection, $filter, $projection, $populate, $fieldsFilter);

        if (!$page) return false;

        if (isset($page[$startpageName]) && $page[$startpageName]) $this->isStartpage = true;

        // reroute startpage if called via slug to avoid duplicated content
        if (!$this->usePermalinksAsSlugs) {
            if (\strlen($slug) && isset($page[$startpageName]) && $page[$startpageName] === true) {
                $path = '/' . ($this->isMultilingual ? $this->lang : '');
                $url = $this->app->routeUrl($path);
                \header('Location: '.$url, true, 301);
                $this->app->stop();
            }
        }

        if (!empty($this->preRenderFields) && is_array($this->preRenderFields)) {
            $page = $this->renderFields($page);
        }

        if ($this->hasBackgroundImage) {
            $this->addBackgroundImage($page);
        }

        return $page;

    }, // end of getPage()

    'userStyles' => function() {

        if (empty($this->styles)) return;

        echo "\r\n<style>\r\n";

        foreach ($this->styles as $selector => $style) {
            if (\is_numeric($selector) && is_string($style)) {
                echo $style . "\r\n";
                continue;
            }
            elseif (\is_string($style)) {
                echo "$selector $style" . "\r\n";
            }
        }

        echo "</style>\r\n";

    }, // end of userStyles()

    'userScripts' => function() {

        if (empty($this->scripts)) return;

        echo "\r\n<script>\r\n";

        foreach ($this->scripts as $script) {
            echo $script . "\r\n";
        }

        echo "</script>\r\n";

    }, // end of userScripts()

    'addBackgroundImage' => function($page = []) {

        $background = $page['background_image']['_id']
                   ?? $this->site['background_image']['_id']
                   ?? null;

        if ($background) {

            $css = [];
            $pattern = '';

            foreach ($this->backgroundBreakpoints as $name => $options) {

                $sizes = [];
                foreach ($options['points'] as $o => $size) {
                    $sizes[] = '(' . $o . ':' . $size . 'px)';
                }

                $backgroundSize = $options['size'] ?? $options['points']['max-width'] ?? $options['points']['min-width'] ?? 1920;

                $pattern = '@media ' . implode(' and ', $sizes);

                $css[] = $pattern . '{' . 'html {background-image: url("'.MP_BASE_URL.'/getImage?src='.$background.'&w='.$backgroundSize.'&m=bestFit&q=70");}' . '}';
            }

            $this->add('styles', $css);

        }

    }, // end of addBackgroundImage()

    'getPreview' => function() {

        $data = \class_exists('\Lime\Request') ? $this->app->request->request : $_REQUEST;

        $event      = $data['event'] ?? false;

        if ($event != 'cockpit:collections.preview') return false;

        $lang       = isset($data['lang']) && $data['lang'] != 'default'
                      ? $data['lang'] : $this->defaultLang;
        $page       = $data['entry'] ?? false;
        $collection = $data['collection'] ?? false;

        $posts = null;
        $site  = $this->site;
        $slug  = $this->resolveSlug(MP_BASE_URL . '/' . $page[$this->fieldNames['slug']]);

        if ($this->isMultilingual) {
            $this->initI18n($lang);
        }

        if ($lang != 'default') {

            $page = $this->app->module('collections')->_filterFields($page, $collection, ['lang' => $lang]);

        }

        if (!empty($this->preRenderFields) && is_array($this->preRenderFields)) {
            $page = $this->renderFields($page);
        }

        $hasSubpageModule = isset($page['subpagemodule']['active'])
                            && $page['subpagemodule']['active'] === true;

        if ($hasSubpageModule) {

            $subCollection = $page['subpagemodule']['collection'];
            $route = $page['subpagemodule']['route'];
            $posts = $this->getPosts($subCollection, $this->currentSlug);

        }

        $this->app->trigger('multiplane.getpreview.before', [$collection, &$page, &$posts, &$site]);

        if ($this->previewMethod == 'json') {
            return compact('page', 'posts', 'site');
        }

        elseif ($this->previewMethod == 'html') {
            $olayout = $this->app->layout;
            $this->app->layout = false;

            $view = 'views:layouts/default.php';
            if ($path = $this->app->path("views:layouts/collections/{$collection}.php")) {
                $view = $path;
            }

            $content = $this->app->view($view, compact('page', 'posts', 'site'));

            $this->app->layout = $olayout;

            return $content;
        }

        return false;

    }, // end of getPreview()

    'getNav' => function($collection = null, $type = '') {

        // if hard coded nav is present, return this one
        if (isset($this->nav[$type])) return $this->nav[$type];

        if (!$collection) $collection = $this->pages;

        $collection = $this->app->module('collections')->collection($collection);

        $isSortable = $collection['sortable'] ?? false;

        $slugName      = $this->fieldNames['slug'];
        $navName       = $this->fieldNames['nav'];
        $titleName     = $this->fieldNames['title'];
        $startpageName = $this->fieldNames['startpage'];

        $options = [
            'filter' => [
                'published' => true,
            ],
            'fields' => [
                $slugName      => true,
                $titleName     => true,
                $navName       => true,
                '_pid'         => true,
                '_o'           => true,
                $startpageName => true,
            ],
        ];

        if (!empty($type)) {
            $options['filter'][$navName] = ['$has' => $type];
        } else {
            $options['filter'][$navName] = ['$size' => ['$gt' => 0]];
        }

        if ($this->isMultilingual) {

            $lang = $this->lang;

            $options['lang'] = $lang;

            if ($lang != $this->defaultLang) {
                $options['fields']["{$titleName}_{$lang}"] = true;
                if ($slugName != '_id') {
                    $options['fields']["{$slugName}_{$lang}"] = true;
                }
            }

        }

        $entries = $this->app->module('collections')->find($collection['name'], $options);

        if (!$entries) return false;

        foreach($entries as &$n) {

            $active = false;
            if ($this->hasParentPage && $n[$slugName] == $this->parentPage[$slugName]) {
                $active = true;
            } elseif($this->currentSlug == $n[$slugName]
                || ($this->currentSlug == '' && !empty($n[$startpageName]))
                ) {
                $active = true;
            }

            $n['active'] = $active;

            if ($this->usePermalinksAsSlugs) {
                $n['url'] = $n[$slugName];
                unset($n[$slugName]);
            }

        }

        if ($isSortable) {

            $entries = $this->app->helper('utils')->buildTree($entries, [
                'parent_id_column_name' => '_pid',
                'children_key_name'     => 'children',
                'id_column_name'        => '_id',
                'sort_column_name'      => '_o'
            ]);

        }

        return $entries;

    }, // end of getNav()

    'getLanguages' => function($extended = false, $withDefault = true) {

        $languages = [];

        if ($this->isMultilingual && \is_array($this->app['languages'])) {

            foreach ($this->app['languages'] as $l => $label) {

                if ($l != 'default' || ($l == 'default' && $withDefault)) {

                    $code = $l == 'default' ? $this->defaultLang : $l;
                    $languages[] = !$extended ? $code : [
                        'code'    => $code,
                        'name'    => $label,
                        'active'  => $code == $this->lang,
                        'default' => $code == $this->defaultLang,
                    ];
                }
            }

        }

        return $languages;

    }, // end of getLanguages()

    'initI18n' => function($lang = 'en') {

        $this('i18n')->locale = $this->lang = $lang;

        if ($this->isMultilingual/* && !$this->usePermalinksAsSlugs*/) {
            $this->app->set('base_url', MP_BASE_URL . '/' . $lang);
        }

        // init + load i18n
        if ($translationspath = $this->app->path("mp_config:i18n/{$lang}.php")) {
            $this('i18n')->load($translationspath, $lang);
        }

    }, // end of initI18n()

    'getLanguageSwitch' => function($id) {

        $languages = $this->getLanguages(true);
        $slugName  = $this->fieldNames['slug'];

        foreach ($languages as &$l) {

            $lang = $l['code'];
            $slug = '';

            if ($this->isStartpage) {
                $l['url'] = MP_BASE_URL . '/' . $lang;
                continue;
            }

            else {
                $filter = [
                    'published' => true,
                    '_id'       => $id ?? '',
                ];
                $projection = [
                    $slugName   => true,
                    "{$slugName}_{$lang}" => true
                ];

                $entry = $this->app->module('collections')->findOne($this->collection, $filter, $projection, false, ['lang' => $lang]);

                $slug = $entry[$slugName] ?? '';
            }

            if (!$this->hasParentPage && !$this->usePermalinksAsSlugs) {
                $l['url'] = MP_BASE_URL . '/' . $lang . '/' . $slug;
                continue;
            }

            $key = 'route' . ($slugName == '_id' || $l['default'] ? '' : "_{$lang}");
            if (!empty($this->parentPage['subpagemodule'][$key])) {
                $route = $this->parentPage['subpagemodule'][$key];
            }
            else { // fallback to slug of parent page
                $key   = $slugName . ($slugName == '_id' || $l['default'] ? '' : "_{$lang}");
                $route = $this->parentPage[$key] ?? null;
            }

            if (!$this->usePermalinksAsSlugs) {
                $l['url'] = MP_BASE_URL . '/' . $lang . '/' . ($route ? trim($route, '/') . '/' : '') . $slug;
            } else {
                $l['url'] = $slug;
            }

        }

        return $languages;

    }, // end of getLanguageSwitch()

    'getPosts' => function($collection = null, $slug = '', $opts = []) {

        if ($this->pageTypeDetection == 'type') {
            return $this->getPostsByType($collection, $slug, $opts);
        }

        if (!$collection) $collection = $this->posts;

        $collection = $this->app->module('collections')->collection($collection);

        if (!$collection) return false;

        $name = $collection['name'];

        $lang  = $this->lang;
        $page  = $this->app->param('page', 1);
        $limit = (isset($opts['limit']) && (int)$opts['limit'] ? $opts['limit'] : null) ?? $this->displayPostsLimit ?? 5;
        $skip  = ($page - 1) * $limit;

        $filter = [
            'published' => true,
        ];

        $sort = !empty($opts['customsort']) ? $opts['customsort'] : [
            '_created' => isset($opts['sort']) && $opts['sort'] ? 1 : -1,
        ];

        $options = [
            'filter' => $filter,
            'lang'   => $lang,
            'limit'  => $limit,
            'skip'   => $skip,
            'sort'   => $sort,
        ];

        $this->app->trigger('multiplane.getposts.before', [$name, &$options]);

        $posts = $this->app->module('collections')->find($name, $options);

        $count = $this->app->module('collections')->count($name, $options['filter']);

        if (!$posts && $count) {
            // send 404 if no posts found (pagination too high)
            $this->app->response->status = 404;
            return;
        }

        if (!empty($this->preRenderFields) && is_array($this->preRenderFields)) {
            foreach($posts as &$post) {
                $post = $this->renderFields($post);
            }
        }

        // subpage module is on startpage without slug
        if (empty($slug) && $this->pageTypeDetection == 'collections' && $this->isStartpage) {

            $parentPage = $this->resolveParentPage();

            $key = 'route' . ($lang == $this->defaultLang ? '' : '_'.$lang);

            if (!empty($parentPage['subpagemodule'][$key])) {
                $slug = $parentPage['subpagemodule'][$key];
            }
            else {
                $key = $this->fieldNames['slug'] . ($this->fieldNames['slug'] == '_id' || $lang == $this->defaultLang ? '' : "_{$lang}");
                $slug = $parentPage[$key];
            }

        }

        $pagination =  [
            'count' => $count,
            'page'  => $page,
            'limit' => $limit,
            'pages' => ceil($count / $limit),
            'slug'  => $slug,
            'posts_slug' => $slug,
            'dropdownLimit' => $opts['dropdownLimit'] ?? $this->paginationDropdownLimit ?? 5,
            'hide'  => (!isset($opts['pagination']) || $opts['pagination'] !== true),
        ];

        return compact('posts', 'pagination', 'collection');

    }, // end of getPosts()

    'getPostsByType' => function($type = null, $slug = '', $opts = []) {

        if (!$type) $type = 'post';

        $lang  = $this->lang;
        $page  = $this->app->param('page', 1);
        $limit = (isset($opts['limit']) && (int)$opts['limit'] ? $opts['limit'] : null)
                  ?? $this->displayPostsLimit ?? 5;
        $skip  = ($page - 1) * $limit;

        $filter = [
            'published' => true,
            'type' => $type,
        ];

        $options = [
            'filter' => $filter,
            'lang'  => $lang,
            'limit' => $limit,
            'skip'  => $skip,
        ];

        $posts = $this->app->module('collections')->find($this->pages, $options);

        $count = $this->app->module('collections')->count($this->pages, $filter);

        if (!$posts && $count) {
            // send 404 if no posts found (paginagion too high)
            $this->app->response->status = 404;
            return;
        }

        if (!empty($this->preRenderFields) && is_array($this->preRenderFields)) {
            foreach($posts as &$post) {
                $post = $this->renderFields($post);
            }
        }

        $pagination =  [
            'count' => $count,
            'page'  => $page,
            'limit' => $limit,
            'pages' => \ceil($count / $limit),
            'slug'  => $slug,
            'posts_slug' => '',
            'dropdownLimit' => $opts['dropdownLimit'] ?? $this->paginationDropdownLimit ?? 5,
            'hide' => (!isset($opts['pagination']) || $opts['pagination'] !== true),
        ];

        return compact('posts', 'pagination');

    }, // end of getPostsByType()

    'resolveSlug' => function($_slug = '') {

        // check, if slug type is _id or slug
        // check, if sub page
        // to do...

        if ($_slug == '') return $_slug;

        // fix routes with ending slash
//         $slug = \rtrim($_slug, '/');
        $slug = \trim($_slug, '/');
        $permalink = '/'.$slug;

        if (\strpos($slug, '/')) {

            $parts = \explode('/', $slug);
            $count = \count($parts);

            if ($this->usePermalinksAsSlugs && $this->isMultilingual) {

                if ($count == 2 && $parts[0] == $this->lang) {
                    $this->currentSlug = $this->usePermalinksAsSlugs ? $permalink : $slug;
                    return $this->currentSlug;
                }
                else {
                    $_parts = $parts;
                    $parts = \array_slice($parts, 1);
                }
            }

            // possible options - to do...:
            // * /page-title
            // * /category/page-title
            // * /collection/page-title
            // * /blog/post-title
            // * /blog/page/2
            // * /blog/2019/06/06/post-title
            // * ...

            if ($this->pageTypeDetection == 'collections') {

                $route = $parts[0];

                if ($this->usePermalinksAsSlugs && $this->isMultilingual) {
                    $route = $_parts[0] . '/' . $parts[0];
                }

                if ($collection = $this->resolveCurrentCollection($route)) {

                    // pagination for blog module
                    if ($parts[1] == 'page' && $count > 2 && (int)$parts[2]) {
                        $slug = $parts[0];
                        $permalink = '/'.$parts[0];
                        if ($this->isMultilingual) $permalink = '/' . $this->lang . $permalink;

                        if (class_exists('Lime\Request')) {
                            $this->app->request->request['page'] = $parts[2];
                        } else {
                            $_REQUEST['page'] = $parts[2];
                        }

                        unset($parts[1]); // I don't want "page" in breadcrumbs
                    }

                    else {
                        $this->hasParentPage = true;
                        $this->collection = $collection;
                        $slug = $parts[1];
                    }

                    $count = count($parts);

                    // to do: find cleaner solution for permalinks and enable breadcrumbs again
                    if ($count > 1 && !$this->usePermalinksAsSlugs) {

                        $breadcrumbs = $this->breadcrumbs;
                        $lang = $this->lang;
                        $suffix = $lang != $this->defaultLang ? '_'.$lang : '';

                        foreach($parts as $k => $part) {

                            if ($k >= ($count - 1)) continue; // skip current page

                            $filter = [
                                $this->fieldNames['slug'] . $suffix => $part
                            ];
                            $projection = [];

                            $entry = $this->app->module('collections')->findOne($this->pages, $filter, $projection, false, ['lang' => $lang]);

                            $breadcrumbs[] = [
                                'title' => $entry[$this->fieldNames['title']] ?? $part,
                                'slug'  => $part,
                            ];

                        }
                        $this->breadcrumbs = $breadcrumbs;

                    }

                }

            }

            elseif($this->pageTypeDetection == 'type') {

                if ($parts[0] == 'page' && (int)$parts[1]) {
                    // pagination for blog module
                    $slug = ''; 

                    if (\class_exists('Lime\Request')) {
                        $this->app->request->request['page'] = $parts[1];
                    } else {
                        $_REQUEST['page'] = $parts[1];
                    }
                }

                if ($parts[1] == 'page' && (int)$parts[2]) {
                    // pagination for blog module
                    $slug = $parts[0]; 

                    if (\class_exists('Lime\Request')) {
                        $this->app->request->request['page'] = $parts[2];
                    } else {
                        $_REQUEST['page'] = $parts[2];
                    }
                }

            }

        }

        $this->currentSlug = $this->usePermalinksAsSlugs ? $permalink : $slug;

        return $this->currentSlug;

    }, // end of resolveSlug()

    'resolveCurrentCollection' => function($route = '') {

        $parentPage = $this->resolveParentPage($route);

        $this->parentPage = $parentPage;

        $collection = $parentPage['subpagemodule']['collection'] ?? false;

        if (is_string($collection) && $this->app->module('collections')->exists($collection)) {
            return $collection;
        }

        return false;

    }, // end of resolveCurrentCollection()

    'resolveParentPage' => function($_route = '') {

        $lang = $this->lang;

        $slugName      = $this->fieldNames['slug'] . ($lang == $this->defaultLang ? '' : '_'.$lang);
        $publishedName = $this->fieldNames['published'];
        $startpageName = $this->fieldNames['startpage'];

        $route = trim($_route, '/');

        if ($this->usePermalinksAsSlugs) $route = '/'.$route;

        $filter = [
            $publishedName => true,
            $slugName      => $route,
            'subpagemodule.active' => true,
        ];

        $projection = [
            $this->fieldNames['slug'] => true,
            'subpagemodule' => true,
        ];
        if ($this->fieldNames['slug'] != '_id') {
            foreach($this->getLanguages() as $l) {
                $projection[$this->fieldNames['slug'].'_'.$l] = true;
            }
        }

        if ($this->isStartpage) {

            $filter = [
                $publishedName => true,
                $startpageName => true,
                'subpagemodule.active' => true,
            ];

        }

        $fieldsFilter = [];

        $parentPage = $this->app->module('collections')->findOne($this->pages, $filter, $projection, false, $fieldsFilter);

        return $parentPage;

    }, // end of resolveParentPage()

    'renderFields' => function($page) {

        $collection = $this->app->module('collections')->collection($this->collection);

        if (!isset($collection['fields'])) return $page;

        $fields = $collection['fields'];

        foreach ($fields as $field) {

            if (!isset($page[$field['name']])) continue;

            if (!in_array($field['name'], $this->preRenderFields)) continue;

            $cmd  = $field['type'] ?? 'text';
            $opts = $field['options'] ?? [];
            $page[$field['name']] = $this('fields')->$cmd($page[$field['name']], $opts);
 
        }

        return $page;

    }, // end of renderFields()

    'accessAllowed' => function() {

        // maintenance mode
        if (!$this->isInMaintenanceMode) return true;

        if (!$ips = $this->allowedIpsInMaintenanceMode) {
            return false;
        }

        else {
            // allow array input or string with white space delimiter
            $ips = \is_array($ips) ? $ips : \explode(' ', trim($ips));

            if (\in_array($this->app->getClientIp(), $ips)) {
                $this->clientIpIsAllowed = true;
                return true;
            }
        }

        return false;

    }, // end of accessAllowed()

    'getRouteToPrivacyPage' => function() {

        $filter = [
            'published' => true,
            'privacypage' => true
        ];

        $lang = $this->lang;

        $slugName = $this->fieldNames['slug'];

        $projection = [
            $slugName => true,
            '_id' => false,
        ];
        if ($this->isMultilingual && $lang != $this->defaultLang) {
            $projection[$slugName.'_'.$lang] = true;
        }

        $page = $this->app->module('collections')->findOne($this->pages, $filter, $projection, null, false, ['lang' => $lang]);

        $route = $page[$slugName.'_'.$lang] ?? $page[$slugName] ?? '';

        return '/'.$route;

    }, // end of getRouteToPrivacyPage()

    'loadConfig' => function() {

        // overwrite default config

        // load config
        $config = $this->app->retrieve('multiplane', []);

        if (isset($this->app['modules']['cpmultiplanegui'])
          && isset($config['profile'])
          && $profile = $this->app->module('cpmultiplanegui')->profile($config['profile'])
          ) {
            $config = \array_replace_recursive($config, $profile);
        }

        // load theme config file(s), if available

        if (!empty($config['parentTheme'])) $this->parentTheme = $config['parentTheme'];
        if (!empty($config['theme']))       $this->theme       = $config['theme'];

        $themeConfig = $this->loadThemeConfig();

        if (\is_array($themeConfig)) {
            $config = \array_replace_recursive($themeConfig, $config);
        }

        foreach($config as $key => $val) {
            if ($key == 'fieldNames') {
                $fieldNames = $this->fieldNames;
                foreach ($val as $fieldName => $replacement) {
                    $fieldNames[$fieldName] = $replacement;
                }
                $this->fieldNames = $fieldNames;
            } else {
                $this->set($key, $val);
            }
        }

        // backwards compatibility
        if (isset($config['slugName']) && !isset($config['fieldNames']['slug'])) {
            $fieldNames = $this->fieldNames;
            $fieldNames['slug'] = $config['slugName'];
            $this->fieldNames = $fieldNames;
        }
        if (isset($config['navName']) && !isset($config['fieldNames']['nav'])) {
            $fieldNames = $this->fieldNames;
            $fieldNames['nav'] = $config['navName'];
            $this->fieldNames = $fieldNames;
        }

        // set current collection to pages
        $this->set('collection', $this->pages);

    }, // end of loadConfig()

    'loadThemeConfig' => function() {

        $themeConfig = $parentThemeConfig = [];

        if (  ($this->themePath = $this->app->path(MP_ENV_ROOT.'/themes/'.$this->theme))
           || ($this->themePath = $this->app->path(__DIR__.'/themes/'.$this->theme)) ) {

            if (\file_exists($this->themePath . '/config/config.php')) {
                $themeConfig = include($this->themePath . '/config/config.php');

                if (!$this->parentTheme && !empty($themeConfig['parentTheme'])) {
                    $this->parentTheme = $themeConfig['parentTheme'];
                }
            }

            if ($this->parentTheme) {
                if (  ($this->parentThemePath = $this->app->path(MP_ENV_ROOT.'/themes/'.$this->parentTheme))
                   || ($this->parentThemePath = $this->app->path(__DIR__.'/themes/'.$this->parentTheme)) ) {

                    // parent theme path must be set before theme path
                    $this->app->path('theme', $this->parentThemePath);
                    $this->app->path('views', $this->parentThemePath . '/views');

                    if (\file_exists($this->parentThemePath . '/config/config.php')) {
                        $parentThemeConfig = include($this->parentThemePath . '/config/config.php');
                    }
                }
            }

            $this->app->path('theme', $this->themePath);
            $this->app->path('views', $this->themePath . '/views');

            // return theme config
            return \array_replace_recursive($parentThemeConfig, $themeConfig);

        }

        else {
            if (!COCKPIT_CLI && !MP_SELF_EXPORT) {
                echo 'The theme "'.$this->theme.'" doesn\'t exist.';
                $this->app->stop();
            }
        }

    }, // end of loadThemeConfig()

    'extendLexyTemplateParser' => function() {

        // create image url shortcuts

        if (empty($this->lexy) || !\is_array($this->lexy)) return;

        foreach ($this->lexy as $k => $v) {

            if (\is_string($v)) {

                if ($v == 'raw') {
                    $this->app->renderer->extend(function($content) use ($k) {
                        return \preg_replace('/(\s*)@'.$k.'\((.+?)\)/', '$1<?php echo MP_BASE_URL; $app->base("#uploads:" . ltrim($2, "/")); ?>', $content);
                    });
                    continue;
                }

                else {
                    continue; // to do: custom callbacks...
                }

            }

            $pattern = '/(\s*)@'.$k.'\((.+?)\)/';

            $replacement = '$1<?php echo MP_BASE_URL."/getImage?src=".urlencode($2)';
            if (isset($v['width'])   && $v['width'])    $replacement .= '."&w=".$app->module(\'multiplane\')->get("lexy/'.$k.'/width", '   . $v['width'].')';
            if (isset($v['height'])  && $v['height'])   $replacement .= '."&h=".$app->module(\'multiplane\')->get("lexy/'.$k.'/height", '  . $v['height'].')';
            if (isset($v['quality']) && $v['quality'])  $replacement .= '."&q=".$app->module(\'multiplane\')->get("lexy/'.$k.'/quality", ' . $v['quality'].')';
            if (isset($v['method'])  && $v['method'])   $replacement .= '."&m=".$app->module(\'multiplane\')->get("lexy/'.$k.'/method", "' . $v['method'].'")';
            $replacement .= '; ?>';

            $this->app->renderer->extend(function($content) use ($pattern, $replacement) {
                return \preg_replace($pattern, $replacement, $content);
            });

        }

    }, // end of extendLexyTemplateParser()

    'self_export' => function() {

        $constants = [
            'MP_ENV_ROOT'     => MP_ENV_ROOT,
            'MP_BASE_URL'     => MP_BASE_URL,
            'MP_ADMIN_FOLDER' => MP_ADMINFOLDER,
            'MP_ENV_URL'      => MP_ENV_URL, // wrong url guess, if called from `/cockpit`
        ];

        $theme = [
            'name'        => $this->theme,
            'path'        => $this->themePath,
            'parentTheme' => $this->parentTheme,
            // 'config'      => $this->loadThemeConfig(),
        ];

        $themes    = [];
        $themedirs = [MP_DIR.'/modules/Multiplane/themes', MP_ENV_ROOT.'/themes'];

        foreach ($themedirs as $themedir) {
            foreach($this('fs')->ls($themedir) as $dir) {

                if (!$dir->isDir()) continue;

                $name = $dir->getFileName();
                $path = $dir->getPathName();

                $thm = [
                    'name'   => $name,
                    'path'   => $path,
                    'image'  => '',
                    'config' => \file_exists("{$path}/config/config.php") ? include("{$path}/config/config.php") : [],
                    'info'   => \file_exists("{$path}/package.json") ? json_decode($this('fs')->read("{$path}/package.json")) : [],
                ];

                if ( ($image = $this->app->pathToUrl("{$path}/screenshot.png"))
                  || ($image = $this->app->pathToUrl("{$path}/screenshot.jpg"))) {
                    $thm['image'] = $image;
                }

                $themes[$name] = $thm;
            }
        }

        return compact('constants', 'theme', 'themes');

    }, // end of self_export()

    // same as Lime\App->assets(), but with a switch to different script function
    // temporary fix to avoid nu validator warning
    'assets' => function($src, $version=false){

        $list = [];

        foreach ((array)$src as $asset) {

            $src = $asset;

            if (\is_array($asset)) {
                extract($asset);
            }

            if (@\substr($src, -3) == '.js') {
                $list[] = $this->script($asset, $version);
            }

            if (@\substr($src, -4) == '.css') {
                $list[] = $this->app->style($asset, $version);
            }
        }

        return \implode("\n", $list);

    }, // end of assets()

    // same as Lime\App->script(), but without `type=javascript`
    // temporary fix to avoid nu validator warning
    'script' => function ($src, $version=false){

        $list = [];

        foreach ((array)$src as $script) {

            $src  = $script;

            if (\is_array($script)) {
                \extract($script);
            }

            $ispath = \strpos($src, ':') !== false && !\preg_match('#^(|http\:|https\:)//#', $src);
            $list[] = '<script src="'.($ispath ? $this->app->pathToUrl($src):$src).($version ? "?ver={$version}":"").'"></script>';
        }

        return \implode("\n", $list);

    }, // end of script()

    'generateToken' => function() {

        return \uniqid(\bin2hex(\random_bytes(16)));

    }, // end of generateToken()

    'getSubPageRoute' => function($collection) {

        static $routes;

        if (isset($routes[$collection])) return $routes[$collection];

        $route = '';

        // to do: hard coded variant for all subpage modules
        $filter = [
            'published'                => true,
            'subpagemodule.active'     => true,
            'subpagemodule.collection' => $collection
        ];
        $projection = [
            '_id' => false,
            'subpagemodule' => true,
        ];

        $postRouteEntry = $this->app->module('collections')->findOne($this->pages, $filter, $projection, false, ['lang' => $this->lang]);

        $path = $this->lang == $this->defaultLang ? 'route' : 'route_'.$this->lang;

        if (isset($postRouteEntry['subpagemodule'][$path])) {
            $route = $postRouteEntry['subpagemodule'][$path];
        }

        $routes[$collection] = $route;

        return $route;

    }, // end of getSubPageRoute()

    'isCollectionLocalized' => function($collection) {

        // to do: do proper checks...
        // UniqueSlugs config check doesn't work, if disabled/not installed/not configured
        // for now, I just assume, that everything is configured correctly

        return true;

    }, // end of isCollectionLocalized()

]);

// module parts
include_once(__DIR__ . '/module/forms.php');

// experimental parts
include_once(__DIR__ . '/experimental/sitemap.php');
include_once(__DIR__ . '/experimental/matomo.php');
include_once(__DIR__ . '/experimental/seo.php');


// overwrite default config
$this->module('multiplane')->loadConfig();

// load theme bootstrap file(s)
if ($this->module('multiplane')->parentTheme && $this->module('multiplane')->parentThemeBootstrap
    && \file_exists($this->module('multiplane')->parentThemePath . '/bootstrap.php')) {

    include_once($this->module('multiplane')->parentThemePath . '/bootstrap.php');
}
if (\file_exists($this->module('multiplane')->themePath . '/bootstrap.php')) {
    include_once($this->module('multiplane')->themePath . '/bootstrap.php');
}

// load custom bootstrap file
if (\file_exists(MP_CONFIG_DIR.'/bootstrap.php')) {
    include_once(MP_CONFIG_DIR.'/bootstrap.php');
}

// extend lexy parser for custom image url templating
$this->module('multiplane')->extendLexyTemplateParser();

// bind routes

if (!MP_SELF_EXPORT) {

    // skip binding routes if in maintenance mode and
    // don't bind any routes, if users wants to use only their own routes
    if ($this->module('multiplane')->accessAllowed()
      && !$this->module('multiplane')->disableDefaultRoutes) {
        require_once(__DIR__ . '/bind.php');
    }

}

// CLI
if (COCKPIT_CLI) {
    $this->path('#cli', __DIR__.'/cli');
}
