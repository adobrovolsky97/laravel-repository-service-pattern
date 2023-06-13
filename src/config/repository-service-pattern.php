<?php
/*
 * is_create_entity_folder setting if for creating Models\\Entity folder,
 * or just create file directly in Models, Repos, Services, etc. directory
 */
return [
    'service'    => [
        'is_create_entity_folder' => true,
        'namespace'               => 'App\\Services',
    ],
    'repository' => [
        'is_create_entity_folder' => true,
        'namespace'               => 'App\\Repositories',
    ],
    'request'    => [
        'namespace' => 'App\\Http\\Requests',
    ],
    'resource'   => [
        'is_create_entity_folder' => true,
        'namespace'               => 'App\\Http\\Resources',
    ],
    'controller' => [
        'is_create_entity_folder' => true,
        'namespace'               => 'App\\Http\\Controllers',
        'store_request_name'      => 'StoreRequest',
        'update_request_name'     => 'UpdateRequest',
    ],
    'model'      => [
        'is_create_entity_folder' => true,
        'namespace'               => 'App\\Models'
    ]
];
