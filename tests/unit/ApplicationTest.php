<?php
namespace Tests\unit;


class ApplicationTest extends \Codeception\Test\Unit
{
    /**
     * @var \Tests\
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testInitApplicationEmptyConfig()
    {
        $_SERVER['PATH_INFO'] = '/api/v1/controller/action';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('{"error":{"code":100,"text":"Invalid application config"}}');

        \IVAgafonov\System\Application::init([]);
    }

    public function testInitApplicationWithoutModules()
    {
        $_SERVER['PATH_INFO'] = '/api/v1/controller/action';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $config = [
            'Modules' => [
            ],
            'Router' => [
                'Controller' => [
                    'Factory' => [
                        'Module' => '\MyModule\Controller\Factory\ModuleControllerFactory'
                    ]
                ]
            ]
        ];

        \IVAgafonov\System\Application::init($config);
    }

    public function testInitApplicationWithNotExistsModule()
    {
        $_SERVER['PATH_INFO'] = '/api/v1/controller/action';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $config = [
            'Modules' => [
                'MyModule1'
            ],
            'Router' => [
                'Controller' => [
                    'Factory' => [
                        'Module' => '\MyModule\Controller\Factory\ModuleControllerFactory'
                    ]
                ]
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('{"error":{"code":140,"text":"Module path not found"}}');

        \IVAgafonov\System\Application::init($config);
    }

    public function testInitApplicationWithInvalidModule()
    {
        $_SERVER['PATH_INFO'] = '/api/v1/controller/action';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $config = [
            'Modules' => [
                'iagafonov/controller'
            ],
            'Router' => [
                'Controller' => [
                    'Factory' => [
                        'Module' => '\MyModule\Controller\Factory\ModuleControllerFactory'
                    ]
                ]
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('{"error":{"code":120,"text":"Invalid module"}}');

        \IVAgafonov\System\Application::init($config);
    }

    public function testInitApplicationWithValidConfig()
    {
        $_SERVER['PATH_INFO'] = '/api/v1/controller/action';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $config = [
            'Modules' => [
                'iagafonov/my-module'
            ],
            'Router' => [
                'Controller' => [
                    'Factory' => [
                        'Module' => '\MyModule\Controller\Factory\ModuleControllerFactory'
                    ]
                ]
            ],
            'Services' => [
                'defaultDataProvider' => [
                    'object' => '\IVAgafonov\System\DataProvider',
                    'config' => [
                        'dbHost' => 'localhost',
                        'dbName' => 'test',
                        'dbUser' => 'root',
                        'dbPass' => ''
                    ]
                ],
                'defaultStdClass' => [
                    'object' => '\stdClass'
                ],
                'defaultStdClassExists' => [
                    'object' => '\stdClass'
                ]
            ]
        ];

        \IVAgafonov\System\Application::$services['defaultStdClassExists'] = new \stdClass();

        \IVAgafonov\System\Application::init($config);
        $this->assertFalse(empty(\IVAgafonov\System\Application::$config['Modules']));
        $this->assertInstanceOf('\IVAgafonov\System\DataProvider', \IVAgafonov\System\Application::$services['defaultDataProvider']);
        $this->assertInstanceOf('\stdClass', \IVAgafonov\System\Application::$services['defaultStdClass']);
    }

    public function testRunApplicationWithValidConfig()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['PATH_INFO'] = 'api/v1/my-module/index';

        $config = [
            'Modules' => [
                'iagafonov/my-module'
            ],
            'Router' => [
                'Controller' => [
                    'v1' => [
                        'Factory' => [
                            'MyModule' => '\MyModule\Controller\Factory\ModuleControllerFactory'
                        ]
                    ]
                ]
            ]
        ];

        $this->expectOutputString('{"status":"ok","module":"MyModule"}{"status":"ok","module":"MyModule"}{"status":"ok","module":"MyModule"}');

        \IVAgafonov\System\Application::init($config);
        \IVAgafonov\System\Application::run('MyModule');
        $this->assertFalse(empty(\IVAgafonov\System\Application::$config['Modules']));
    }
}