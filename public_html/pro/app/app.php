<?php 

    session_start();

    error_reporting(E_ALL);
    ini_set("display_errors", 1);

    define('ROOT_PATH'  , __DIR__.'/../');
    define('VENDOR_PATH', __DIR__.'/../vendor/');
    define('APP_PATH'   , __DIR__.'/');
    define('PUBLIC_PATH', __DIR__.'/../public/');
    /* */
    require VENDOR_PATH. 'autoload.php';

    /** 
        * Añadimos tantas variables como queramos a la app 
        * Entre ellas las conexiones de mongo
    */

    $config = array(
        'path.root'     => ROOT_PATH,
        'path.public'   => PUBLIC_PATH,
        'path.app'      => APP_PATH
    );

    foreach (glob(APP_PATH.'Config/*.php') as $configFile) {
        require $configFile;
    }



    $app = new \Slim\App($config);
 
    $container = $app->getContainer();

    //  Connect to the database with Eloquent
    $capsule = new \Illuminate\Database\Capsule\Manager;
    $capsule->addConnection($container['settings']['Database']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    $container['db'] = function($container) use ($capsule) {
        return $capsule;
    };

    // Register Twig View helper
    $container['view'] = function ($c) {

        $view = new \Slim\Views\Twig(APP_PATH.'Views', [
            'cache' => false
        ]);

        $view->addExtension(new Slim\Views\TwigExtension(
        	$c->router,
        	$c->request->getUri()
        ));

        return $view;
    };


    $container['logger'] = function ($c) {
        $settings = $c->get('settings');
        $logger = new \Monolog\Logger($settings['logger']['name']);
        $logger->pushProcessor(new \Monolog\Processor\UidProcessor());
        $logger->pushHandler(new \Monolog\Handler\StreamHandler($settings['logger']['path'], \Monolog\Logger::DEBUG));
        return $logger;
    };

    require APP_PATH. 'routes.php';