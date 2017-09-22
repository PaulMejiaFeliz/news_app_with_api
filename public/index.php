<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

//comments this line in production
//use RuntimeException as Exception;
use Newsapp\Exception as NewsappException;
use Newsapp\ValidationException;
use Newsapp\MediaTypeException;
use Phalcon\Http\Response;

try {
    define('APP_PATH', realpath('..'));

    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");

    //composer autoload
    require_once __DIR__ . '/../vendor/autoload.php';

    /**
     * Read the configuration
     */
    $config = include __DIR__ . "/../app/config/config.php";

    //debug
    if (!$config->application->production) {
        error_reporting(E_ALL);
    } else {
        error_reporting(0);
    }

    /**
     * Read auto-loader
     */
    include __DIR__ . "/../app/config/loader.php";

    /**
     * Read services
     */
    include __DIR__ . "/../app/config/services.php";

    /**
     * Handle the request
     */
    $application = new \Phalcon\Mvc\Micro($di);

    //apply HMCA security with the event beforeRouteExceute
    $application->setEventsManager(new \Baka\Hmac\Events\Authenticate());

    /**
     * Router handler
     */
    include __DIR__ . "/../app/config/routes.php";

    /**
     * Log all API request for now so we can better debug the app
     * @todo change it to be a object given to the API class
     */
    if ($config->application->debug->logRequest) {
        $di->getLog('request')->addInfo('RECEIVED', $_REQUEST);
    }

    $application->handle();
} catch (NewsappException $e) {
    $response = new Phalcon\Http\Response();
    $response->setStatusCode(200, "OK");
    $response->setContentType('application/json');
    $response->setJsonContent([
        'message' => $e->getMessage(),
    ]);

    if ($config->application->production) {
        /**
         * Log the exception
         */
        $di->getLog()->addError($e->getMessage(), $e->getTrace());
    }

    $response->send();
} catch (ValidationException $e) {
    $response = new Phalcon\Http\Response();
    $response->setStatusCode(422, "Unprocessable Entity");
    $response->setContentType('application/json');
    $response->setJsonContent([
        'message' => 'Validation Failed',
        'errors' => $e->errors,
    ]);

    if ($config->application->production) {
        /**
         * Log the exception
         */
        $di->getLog()->addError($e->getMessage(), $e->getTrace());
    }

    $response->send();
} catch (MediaTypeException $e) {
    $response = new Phalcon\Http\Response();
    $response->setStatusCode(415, "Unsupported Media Type");

    if ($config->application->production) {
        /**
         * Log the exception
         */
        $di->getLog()->addError($e->getMessage(), $e->getTrace());
    }

    $response->send();
} catch (\Exception $e) {
    $response = new Phalcon\Http\Response();
    $response->setStatusCode(404, "Not Found");
    $response->setContentType('application/json');
    $response->setJsonContent([
        'status' => [
            'type' => 'FAILED',
            'message' => $e->getMessage(),
        ],
    ]);

    if ($config->application->production) {
        /**
         * Log the exception
         */
        $di->getLog()->addError($e->getMessage(), $e->getTrace());
    }

    $response->send();
}
