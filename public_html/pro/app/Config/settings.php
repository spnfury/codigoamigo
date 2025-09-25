<?php 

    $config = [
            'settings' => [
                'displayErrorDetails'    => true, // set to false in production
                'addContentLengthHeader' => false, // Allow the web server to send the content-length header

                // Renderer settings
                'renderer'               => [
                    'template_path' => __DIR__ . '/../app/Views',
                ],

                // Monolog settings /home/admin/web/api.hellodevel.com/public_html/app
                'logger'                 => [
                    'name'  => 'SYSTEM',
                    'path'  => __DIR__ . '/../logs/app.log',
                    'level' => \Monolog\Logger::DEBUG,
                    // 'directory' => '/logs',  // Path to log directory
                    // 'filename' => 'my-app.log',     // Log file name
                    // 'timezone' => 'Asia/Jakarta',   // Your timezone
                    // 'level' => 'DEBUG',             // Log level
                    // 'handlers' => [],    
                ],
                'mongo' => [
                            'driver'   => 'mongodb',
                            'host'     => ' ',
                            'port'     => 27017,
                            'database' => ' ',
                            // 'username' => ' ',
                            // 'password' => ' ',
                            'options' => [
                                'database' => 'empresas' // sets the authentication database required by mongo 3
                            ]
                ],
                //db settings
                'Database' => [
                                 'driver'       => 'mysql',
                                    'host'      => ' ',
                                    'database'  => ' ',
                                    'username'  => ' ',
                                    'password'  => ' ',
                                    'charset'   => 'utf8',
                                    'collation' => 'utf8_unicode_ci',
                                    'prefix'    => ''
                                ],
                  
            ],
        ];

 ?>