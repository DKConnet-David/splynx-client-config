<?php

use splynx\v2\models\administration\BaseAdministrator;

return function ($params, $baseDir) {
    return [
        'components' => [
            'request' => [
                'baseUrl' => '/client-config',
                'enableCookieValidation' => false,
            ],
            'user' => [
                'identityClass' => BaseAdministrator::class,
                'idParam' => 'splynx_admin_id',
                'loginUrl' => '/admin/login/?return=%2Fclient-config%2F',
                'enableAutoLogin' => false,
            ],
        ],
    ];
};
