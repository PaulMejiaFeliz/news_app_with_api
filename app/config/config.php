<?php

//ENV Variables
$dotenv = new Dotenv\Dotenv(__DIR__ . '/../../');
$dotenv->load();

return new \Phalcon\Config([
    'database' => [
        'adapter' => 'Mysql',
        'host' => getenv('DATABASE_HOST'),
        'username' => getenv('DATABASE_USER'),
        'password' => getenv('DATABASE_PASS'),
        'dbname' => getenv('DATABASE_NAME'),
    ],
    'application' => [
        'version' => '0.5.0',
        'siteName' => getenv('DOMAIN'),
        'siteUrl' => getenv('URL'),
        'controllersDir' => APP_PATH . '/app/controllers/',
        'modelsDir' => APP_PATH . '/app/models/',
        'libraryDir' => APP_PATH . '/app/library/',
        'cacheDir' => APP_PATH . '/app/cache/',
        'baseUri' => '/',
        'production' => getenv('PRODUCTION'),
        'debug' => ['profile' => getenv('DEBUG_PROFILE'), 'logQueries' => getenv('DEBUG_QUERY'), 'logRequest' => getenv('DEBUG_REQUEST')],
        'hmacSecurity' => getenv('HMCA_SECURITY'),
        'uploadDir' => '',
    ],
    'memcache' => [
        'host' => getenv('MEMCACHE_HOST'),
        'port' => getenv('MEMCACHE_PORT'),
    ],
    'cdn' => [
        'url' => getenv('CDN_URL'),
    ],
    'beanstalk' => [
        'host' => getenv('BEANSTALK_HOST'),
        'port' => getenv('BEANSTALK_PORT'),
        'prefix' => getenv('BEANSTALK_PREFIX'),
    ],
    'redis' => [
        'host' => getenv('REDIS_HOST'),
        'port' => getenv('REDIS_PORT'),
    ],
    'elasticSearch' => [
        'hosts' => getenv('ELASTIC_HOST'), //change to pass array
    ],
    'email' => [
        'host' => getenv('EMAIL_HOST'),
        'port' => getenv('EMAIL_PORT'),
        'username' => getenv('EMAIL_USER'),
        'password' => getenv('EMAIL_PASS'),
    ],
    'noauth' => [
        'GET' => [
            '/v1/medias' => '/v1/medias',
            '/v1/medias/{seriesType:(anime|manga)}' => '/v1/medias/{seriesType:(anime|manga)}',
            '/v1/mcanime' => '/v1/mcanime',
            '/v1/mcanime/{seriesType:(anime|manga)}' => '/v1/mcanime/{seriesType:(anime|manga)}',
            '/v1/forum/categories' => '/v1/forum/categories',
            '/v1/forum/topics/mcanime' => '/v1/forum/topics/mcanime',
        ],
    ],
]);
