<?php

define('COPILOT_SYSTEM', $this->path('site:system/bootstrap.php'));

if (COPILOT_SYSTEM) {

    /**
     * bootstrap cocopi
     */
    include_once(COPILOT_SYSTEM);
}

// ADMIN
if (COCKPIT_ADMIN && !COCKPIT_REST && COPILOT_SYSTEM) {

    copi::trigger('cockpit.bootstrap', [$this]);

    include_once(__DIR__.'/admin.php');
}

if (!COPILOT_SYSTEM) return;


$this->module("cocopi")->extend([

    'createPage' => function($root, $meta) {

        $root = ltrim($root, '/');

        $meta = array_merge([
            'title' => '',
            'slug'  => '',
            'type'  => 'html'
        ], $meta);

        if (!$meta['title']) {
            return false;
        }

        $meta['slug'] = strtolower(str_replace([' '], ['-'], $meta['slug'] ? $meta['slug'] : $meta['title']));

        $type     = $meta['type'];
        $typedef  = [];

        if ($path = copi::path("types:{$type}.yaml")) {
            $typedef = $this->app->helper('yaml')->fromFile($path);
        }

        $type = array_replace_recursive([
            'name' => $type,
            'ext' => 'html',
            'content' => [
                'visible' => true,
                'type'    => isset($typedef) && $typedef['ext'] == 'md' ? 'markdown':'html'
            ],
            'meta' => []
        ], (array)$typedef);


        $contentfolder = copi::path('content:');
        $pagepath      = $contentfolder.($root=='home' ? '':  $root.'/'.$meta['slug']).'/'.'index.'.($type['ext']=='md' ? 'md':'html');
        $time          = date('Y-m-d H:i:s', time());

        $content = [
            "uid: ".uniqid('pid-'),
            "type: ".$meta['type'],
            "created: ".$time,
            "modified: ".$time,
            "title: ".$meta['title'],
            "===\n",
        ];

        $this->app->helper('fs')->write($pagepath, implode("\n", $content));

        $url = str_replace(copi::path('site:'),'',$pagepath);

        return $url;
    },

    'deletePage' => function($path) {

        if ($page = copi::page($path)) {
            return $page->delete();
        }

        return false;
    },

    'getPageResources' => function($path, $asarray = false) {

        $page = copi::page($path);

        if (!$page) {
            return [];
        }

        return $asarray ? $page->files()->sorted()->toArray(): $page->files()->sorted();
    },

    'renameResource' => function($path, $name) {

        if ($res = copi::resource($path)) {
            return $res->rename($name)->toArray();
        }

        return false;
    },

    'deleteResource' => function($path) {

        if ($res = copi::resource($path)) {
            return $res->delete();
        }

        return false;
    },

    'getLicense' => function() {

        /**
         * Hello Code monkey ;-)
         *
         * Nothing really special here. A simple snippet to check the license code.
         * It's simple to hack. But please consider supporting this project by
         * buying a license instead. Be awesome.
         *
         * Anyway, have fun using cocopiPi!
         *
         * Greets
         * Artur
         *
         */

        $license = ['type' => 'trial'];
        $code    = (string)$this->app['cocopi.license'];
        $data    = [];

        try {
            $data = (array)JWT::decode($code, 'cocopipi', ['HS256']);
        } catch(Exception $e) {}

        if (isset($data['name'], $data['company'], $data['created'], $data['email'], $data['type'])) {
            $license = $data;
            $license['code'] = $code;
        }

        return (object)$license;
    }
]);