<?php

use Monolog\Handler\FirePHPHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonoLogger;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\DI\FactoryDefault;
use Phalcon\Logger;

/**
 * The FactoryDefault Dependency Injector automatically register the right services providing a full stack framework
 */
$di = new FactoryDefault();

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->set('db', function () use ($config, $di) {

    //db connection
    $connection = new DbAdapter(array(
        'host' => $config->database->host,
        'username' => $config->database->username,
        'password' => $config->database->password,
        'dbname' => $config->database->dbname,
        'charset' => 'utf8',
    ));

    //profile sql queries
    if ($config->application->debug['logQueries']) {
        $eventsManager = new \Phalcon\Events\Manager();

        //Listen all the database events
        $eventsManager->attach('db', function ($event, $connection) use ($di) {
            if ($event->getType() == 'beforeQuery') {
                $sqlVariables = $connection->getSQLVariables();
                if (count($sqlVariables)) {
                    $di->getLog('sql')->addInfo($connection->getSQLStatement() . ' BINDS =>', $sqlVariables);
                } else {
                    $di->getLog('sql')->addInfo($connection->getSQLStatement());
                }
            }
        });

        //Assign the eventsManager to the db adapter instance
        $connection->setEventsManager($eventsManager);
    }

    return $connection;
});

/**
 * Redis configuration
 */
$di->set('redis', function () use ($config) {
    //Connect to redis
    $redis = new Redis();
    $redis->connect($config->redis->host, $config->redis->port);
    $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);

    return $redis;
});

// Start the session
$di->setShared('session', function () use ($config) {
    $memcache = new \Phalcon\Session\Adapter\Memcache(array(
        'host' => $config->memcache->host, // mandatory
        'post' => $config->memcache->port, // optional (standard: 11211)
        'lifetime' => 8600, // optional (standard: 8600)
        'prefix' => 'naruhodo', // optional (standard: [empty_string]), means memcache key is my-app_31231jkfsdfdsfds3
        'persistent' => false, // optional (standard: false)
    ));

    //only start the session if its not already started
    if (!isset($_SESSION)) {
        $memcache->start();
    }

    return $memcache;

    /*
forma vieja de usar sessiones
$session = new SessionAdapter();
$session->start();
return $session;*/
});

/**
 * Set the models cache service
 * Cache for models
 */
$di->set('modelsCache', function () use ($config) {

    //si no estamos en producto 0 cache
    if (!$config->application->production) {
        $frontCache = new \Phalcon\Cache\Frontend\None();
        $cache = new Phalcon\Cache\Backend\Memory($frontCache);
    } else {
        //Cache data for one day by default
        $frontCache = new \Phalcon\Cache\Frontend\Data(array(
            "lifetime" => 86400,
        ));

        //Memcached connection settings
        $cache = new \Phalcon\Cache\Backend\Memcache($frontCache, array(
            "host" => $config->memcache->host,
            "port" => $config->memcache->port,
        ));
    }

    return $cache;
});

/**
 * Set up the flash service
 */
$di->set('flash', function () {
    return new \Phalcon\Flash\Session();
});

$di->set('queue', function () use ($config) {

    //Connect to the queue
    $queue = new Phalcon\Queue\Beanstalk\Extended([
        'host' => $config->beanstalk->host,
        'prefix' => $config->beanstalk->prefix,
    ]);

    return $queue;
});

$di->set('config', $config);

/**
 * System Log using monolog
 */
$di->set('log', function ($file = 'debug') use ($config, $di) {

    // Create the logger
    $logger = new MonoLogger('NARUHODO.API');
    // Now add some handlers
    $logger->pushHandler(new StreamHandler(APP_PATH . "/app/logs/" . $file . '.log', Logger::DEBUG));
    $logger->pushHandler(new FirePHPHandler());

    return $logger;
});

$di->set('purifier', function () use ($config) {
    //require_once($config->application->vendorDir . 'ezyang/htmlpurifier/library/HTMLPurifier.auto.php');

    $hpConfig = \HTMLPurifier_Config::createDefault();
    $hpConfig->set('HTML.Allowed', '');

    return new \HTMLPurifier($hpConfig);
});

/**
 * service to get the CDN for the service. ¿why a service ? we can have multiple cdn we need a way to hand
 */
$di->set('cdn', function () use ($config) {

    return $config->cdn->url;
});
