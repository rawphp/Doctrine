<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | This array passes right through to the EntityManager factory.
    |
    | http://www.doctrine-project.org/documentation/manual/2_0/en/dbal
    |
    */

    'connection'                   =>
        [
            'driver'   => 'pdo_mysql',
            'user'     => 'root',
            'password' => '',
            'dbname'   => 'cianoroza_db',
            'host'     => 'localhost',
            'prefix'   => ''

        ],

    /*
    |--------------------------------------------------------------------------
    | Metadata Sources
    |--------------------------------------------------------------------------
    |
    | This array passes right through to the EntityManager factory.
    |
    | http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/configuration.html
    |
    */
    'metadata'                     =>
        [
            'annotation' =>
                [
                    [
                        'namespace' => 'Suits\\Entity\\',
                        'path'      => base_path() . '/app/Entity/',
                    ],
                ],

            'xml'        =>
                [
//                    [
//                        'namespace' => 'RawPHP\\OAuth\\Entity\\',
//                        'path'      => base_path() . '/workbench/raw-php/oauth/src/resources/mappings/',
//                    ],
//                    [
//                        'namespace' => 'League\\OAuth2\\Server\\Entity\\',
//                        'path'      => base_path() . '/workbench/raw-php/oauth/src/resources/mappings/',
//                    ],
//                    [
//                        'namespace' => 'RawPHP\\Guard\\Entity\\',
//                        'path'      => base_path() . '/workbench/raw-php/guard/src/resources/mappings/'
//                    ],
//                    [
//                        'namespace' => 'RawPHP\\Menu\\Entity\\',
//                        'path'      => base_path() . '/workbench/raw-php/menu/src/resources/mappings',
//                    ],
                ],

            'yml'        =>
                [

                ],
        ],

    /*
    |--------------------------------------------------------------------------
    | Sets the directory where Doctrine generates any proxy classes, including
    | with which namespace.
    |--------------------------------------------------------------------------
    |
    | http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/configuration.html
    |
    */
    'proxy_classes'                =>
        [
            'auto_generate' => TRUE,
            'directory'     => app_path() . '/storage/doctrine',
            'namespace'     => 'Suits\\Doctrine\\Proxy\\',
        ],

    /*
   |--------------------------------------------------------------------------
   | Cache providers, supports apc, xcache, memcache, redis
   | Only redis and memcache have additionals configurations
   |--------------------------------------------------------------------------
   */
    'cache'                        =>
        [
            'provider' => 'redis',

            'redis'    =>
                [
                    'host'     => '127.0.0.1',
                    'port'     => 6379,
                    'database' => 1
                ],

            'memcache' =>
                [
                    'host' => '127.0.0.1',
                    'port' => 11211
                ]
        ],

    'migrations'                   =>
        [
            'directory'  => '/database/doctrine-migrations',
            'namespace'  => 'Suits\Migrations',
            'table_name' => 'migrations'
        ],

    /*
   |--------------------------------------------------------------------------
   | Use to specify the default repository
   | http://docs.doctrine-project.org/en/2.1/reference/configuration.html item 3.7
   |--------------------------------------------------------------------------
   */
    'defaultRepository'            => '\Doctrine\ORM\EntityRepository',

    /*
     |--------------------------------------------------------------------------
     | Annotation Reader
     | https://github.com/doctrine/doctrine2/blob/master/lib/Doctrine/ORM/Tools/Setup.php
     |--------------------------------------------------------------------------
     */
    'use_simple_annotation_reader' => FALSE,

    /*
   |--------------------------------------------------------------------------
   | Use to specify the SQL Logger
   | http://docs.doctrine-project.org/en/2.1/reference/configuration.html item 3.2.6
   | To use with \Doctrine\DBAL\Logging\EchoSQLLogger, do:
   | 'sqlLogger' => new \Doctrine\DBAL\Logging\EchoSQLLogger();
   |--------------------------------------------------------------------------
   */
    'sqlLogger'                    => NULL, //new Doctrine\DBAL\Logging\EchoSQLLogger(),

];
