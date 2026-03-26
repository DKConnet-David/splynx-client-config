<?php

namespace splynx\client_config\assets;

use yii\web\AssetBundle;

/**
 * Registers Quill.js (CDN) and the addon's own JS/CSS.
 */
class ClientConfigAsset extends AssetBundle
{
    public $sourcePath = __DIR__;

    public $css = [
        'css/client-config.css',
    ];

    public $js = [
        'js/client-config.js',
    ];

    /**
     * Quill.js loaded from CDN — lightweight rich text editor.
     */
    public $cssOptions = [];
    public $jsOptions = ['position' => \yii\web\View::POS_END];

    public function init()
    {
        parent::init();

        // Quill.js CDN assets
        $this->css = array_merge([
            'https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css',
        ], $this->css);

        $this->js = array_merge([
            'https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js',
        ], $this->js);
    }

    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
