<?php

namespace splynx\client_config;

use yii\base\BootstrapInterface;

/**
 * Client Config addon module.
 * Adds a "Client Config" tab to the customer detail page with a rich text
 * editor and full audit trail of changes.
 */
class Module extends \yii\base\Module implements BootstrapInterface
{
    public $controllerNamespace = 'splynx\client_config\controllers';

    /**
     * Bootstrap the module — register URL rules and customer tab hook.
     */
    public function bootstrap($app)
    {
        // Register URL rules for the addon
        $app->getUrlManager()->addRules([
            'admin/customers/client-config/<customerId:\d+>' => 'client-config/customer/index',
            'admin/customers/client-config/<customerId:\d+>/save' => 'client-config/customer/save',
            'admin/customers/client-config/<customerId:\d+>/history' => 'client-config/customer/history',
        ], false);

        // Register the customer tab via Splynx's event system
        if (method_exists($app, 'on')) {
            $app->on('customer.tabs', function ($event) {
                $event->tabs[] = [
                    'label' => 'Client Config',
                    'icon' => 'fa fa-file-text-o',
                    'url' => '/admin/customers/client-config/' . $event->customerId,
                    'position' => 90,
                ];
            });
        }
    }

    public function init()
    {
        parent::init();
        $this->setViewPath('@splynx/client_config/views');
    }
}
