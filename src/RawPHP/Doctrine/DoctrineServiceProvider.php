<?php

/**
 * This file is part of Step in Deals application.
 *
 * Copyright (c) 2014 Tom Kaczocha
 *
 * This Source Code is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, you can obtain one at http://mozilla.org/MPL/2.0/.
 *
 * PHP version 5.4
 *
 * @category  PHP
 * @package   RawPHP\Doctrine
 * @author    Tom Kaczohca <tom@crazydev.org>
 * @copyright 2014 Tom Kaczocha
 * @license   http://crazydev.org/licenses/mpl.txt MPL
 * @link      http://crazydev.org/
 */

namespace RawPHP\Doctrine;

use Atrauzzi\LaravelDoctrine\DoctrineRegistry;
use Atrauzzi\LaravelDoctrine\Listener\Metadata\TablePrefix;
use Atrauzzi\LaravelDoctrine\ServiceProvider;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\CouchbaseCache;
use Doctrine\Common\Cache\MemcacheCache;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\Common\Cache\XcacheCache;
use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\Driver\DefaultFileLocator;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Memcache;
use Redis;

/**
 * Class DoctrineServiceProvider
 *
 * @package RawPHP\Doctrine
 */
class DoctrineServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = FALSE;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->package( 'rawphp/doctrine' );

        $this->app->alias( 'Doctrine\ORM\EntityManager', 'doctrine' );

        //
        // Doctrine
        //
        $this->app->singleton( 'Doctrine\ORM\EntityManager', function ( $app )
            {
                // Retrieve our configuration.
                $cache               = NULL; // Default, let Doctrine decide.
                $config              = $app[ 'config' ];
                $connection          = $config->get( 'doctrine::doctrine.connection' );
                $devMode             = $config->get( 'app.debug' );
                $proxyDir            = $config->get( 'doctrine::doctrine.proxy_classes.directory' );
                $proxyAutoGenerate   = $config->get( 'doctrine::doctrine.proxy_classes.auto_generate' );
                $metadata            = $config->get( 'doctrine::doctrine.metadata' );
                $defaultRepository   = $config->get( 'doctrine::doctrine.defaultRepository' );
                $sqlLogger           = $config->get( 'doctrine::doctrine.sqlLogger' );
                $proxyClassNamespace = $config->get( 'doctrine::doctrine.proxy_classes.namespace' );

                if ( !$devMode )
                {
                    $cacheConfig         = $config->get( 'doctrine::doctrine.cache' );
                    $cacheProvider       = $cacheConfig[ 'provider' ];
                    $cacheProviderConfig = $cacheConfig[ $cacheProvider ];

                    switch ( $cacheProvider )
                    {
                        case 'apc':
                            if ( extension_loaded( 'apc' ) )
                            {
                                $cache = new ApcCache();
                            }
                            break;

                        case 'xcache':
                            if ( extension_loaded( 'xcache' ) )
                            {
                                $cache = new XcacheCache();
                            }
                            break;

                        case 'memcache':
                            if ( extension_loaded( 'memcache' ) )
                            {
                                $memcache = new Memcache();
                                $memcache->connect( $cacheProviderConfig[ 'host' ], $cacheProviderConfig[ 'port' ] );
                                $cache = new MemcacheCache();
                                $cache->setMemcache( $memcache );
                            }
                            break;

                        case 'couchbase':
                            if ( extension_loaded( 'couchbase' ) )
                            {
                                $couchbase = new Couchbase(
                                    $cacheProviderConfig[ 'hosts' ],
                                    $cacheProviderConfig[ 'user' ],
                                    $cacheProviderConfig[ 'password' ],
                                    $cacheProviderConfig[ 'bucket' ],
                                    $cacheProviderConfig[ 'persistent' ]
                                );
                                $cache     = new CouchbaseCache();
                                $cache->setCouchbase( $couchbase );
                            }
                            break;

                        case 'redis':
                            if ( extension_loaded( 'redis' ) )
                            {
                                $redis = new Redis();
                                $redis->connect( $cacheProviderConfig[ 'host' ], $cacheProviderConfig[ 'port' ] );

                                if ( $cacheProviderConfig[ 'database' ] )
                                {
                                    $redis->select( $cacheProviderConfig[ 'database' ] );
                                }

                                $cache = new RedisCache();
                                $cache->setRedis( $redis );
                            }
                            break;

                        default:
                            $cache = new ArrayCache();
                            break;
                    }

                    // optionally set cache namespace
                    if ( isset( $cacheProviderConfig[ 'namespace' ] ) )
                    {
                        if ( $cache instanceof CacheProvider )
                        {
                            $cache->setNamespace( $cacheProviderConfig[ 'namespace' ] );
                        }
                    }
                }

                $docConfig = new Configuration();

                $driver = new DriverChain();

                foreach ( $this->getMetadataPaths( 'annotation', $metadata ) as $path )
                {
                    $annotationDriver = new AnnotationDriver( new AnnotationReader(), [ $path ] );
                    $driver->addDriver( $annotationDriver, $this->getMetadataNamespace( 'annotation', $path, $metadata ) );
                }

                foreach ( $this->getMetadataPaths( 'xml', $metadata ) as $path )
                {
                    $xmlDriver = new XmlDriver( new DefaultFileLocator( $path, '.xml' ), '.xml' );

                    $driver->addDriver( $xmlDriver, $this->getMetadataNamespace( 'xml', $path, $metadata ) );
                }

                $docConfig->setMetadataDriverImpl( $driver );
                $docConfig->setProxyDir( $proxyDir );

                /*
                 * set cache implementations
                 * must occur after Setup::createAnnotationMetadataConfiguration() in order to set custom namespaces properly
                 */
                if ( $cache !== NULL )
                {
                    $docConfig->setMetadataCacheImpl( $cache );
                    $docConfig->setQueryCacheImpl( $cache );
                    $docConfig->setResultCacheImpl( $cache );
                }

                $docConfig->setAutoGenerateProxyClasses( $proxyAutoGenerate );
                $docConfig->setDefaultRepositoryClassName( $defaultRepository );
                $docConfig->setSQLLogger( $sqlLogger );

                if ( $proxyClassNamespace !== NULL )
                {
                    $docConfig->setProxyNamespace( $proxyClassNamespace );
                }

                // Trap doctrine events, to support entity table prefix
                $evm = new EventManager();

                if ( isset( $connection[ 'prefix' ] ) && !empty( $connection[ 'prefix' ] ) )
                {
                    $evm->addEventListener( Events::loadClassMetadata, new TablePrefix( $connection[ 'prefix' ] ) );
                }

                // Obtain an EntityManager from Doctrine.
                return EntityManager::create( $connection, $docConfig, $evm );
            }
        );

        $this->app->singleton( 'Doctrine\ORM\Tools\SchemaTool', function ( $app )
            {
                return new SchemaTool( $app[ 'Doctrine\ORM\EntityManager' ] );
            }
        );

        //
        // Utilities
        //

        $this->app->singleton( 'Doctrine\ORM\Mapping\ClassMetadataFactory', function ( $app )
            {
                return $app[ 'Doctrine\ORM\EntityManager' ]->getMetadataFactory();
            }
        );

        $this->app->singleton( 'doctrine.registry', function ( $app )
            {
                $connections = array( 'doctrine.connection' );
                $managers    = array( 'doctrine' => 'doctrine' );
                $proxy       = 'Doctrine\Common\Persistence\Proxy';

                return new DoctrineRegistry( 'doctrine', $connections, $managers, $connections[ 0 ], $managers[ 'doctrine' ], $proxy );
            }
        );

        //
        // String name re-bindings.
        //

        $this->app->singleton( 'doctrine', function ( $app )
            {
                return $app[ 'Doctrine\ORM\EntityManager' ];
            }
        );

        $this->app->singleton( 'doctrine.metadata-factory', function ( $app )
            {
                return $app[ 'Doctrine\ORM\Mapping\ClassMetadataFactory' ];
            }
        );

        $this->app->singleton( 'doctrine.metadata', function ( $app )
            {
                return $app[ 'doctrine.metadata-factory' ]->getAllMetadata();
            }
        );

        // After binding EntityManager, the DIC can inject this via the constructor type hint!
        $this->app->singleton( 'doctrine.schema-tool', function ( $app )
            {
                return $app[ 'Doctrine\ORM\Tools\SchemaTool' ];
            }
        );

        // Registering the doctrine connection to the IoC container.
        $this->app->singleton( 'doctrine.connection', function ( $app )
            {
                return $app[ 'doctrine' ]->getConnection();
            }
        );

        //
        // Commands
        //
        $this->commands(
            array( 'Atrauzzi\LaravelDoctrine\Console\CreateSchemaCommand',
                'Atrauzzi\LaravelDoctrine\Console\UpdateSchemaCommand',
                'Atrauzzi\LaravelDoctrine\Console\DropSchemaCommand' )
        );
    }

    /**
     * Get a list of paths for type of metadata.
     *
     * @param string $type
     * @param array  $collection
     *
     * @return array
     */
    protected function getMetadataPaths( $type, array $collection )
    {
        $paths = [ ];

        if ( isset( $collection[ $type ] ) )
        {
            foreach ( $collection[ $type ] as $items )
            {
                foreach ( $items as $key => $value )
                {
                    if ( 'path' === $key )
                    {
                        $paths[ ] = $value;
                    }
                }
            }
        }

        return $paths;
    }

    /**
     * Get namespace for metadata path.
     *
     * @param string $type
     * @param string $path
     * @param array  $collection
     *
     * @return string
     */
    protected function getMetadataNamespace( $type, $path, array $collection )
    {
        if ( isset( $collection[ $type ] ) )
        {
            foreach ( $collection[ $type ] as $item )
            {
                if ( $item[ 'path' ] === $path )
                {
                    return $item[ 'namespace' ];
                }
            }
        }

        return '';
    }
}
