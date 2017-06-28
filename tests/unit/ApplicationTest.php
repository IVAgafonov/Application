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
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('{"error":{"code":100,"text":"Invalid application config"}}');
        \IVAgafonov\System\Application::init([]);
    }

    public function testInitApplicationWithoutModules()
    {
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
        $config = [
            'Modules' => [
                'controller'
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
        $config = [
            'Modules' => [
                'MyModule'
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
        $this->assertFalse(empty(\IVAgafonov\System\Application::$config['Modules']));
    }

    public function testRunApplicationWithValidConfig()
    {
        $config = [
            'Modules' => [
                'MyModule'
            ],
            'Router' => [
                'Controller' => [
                    'Factory' => [
                        'MyModule' => '\MyModule\Controller\Factory\ModuleControllerFactory'
                    ]
                ]
            ]
        ];

        $this->expectOutputString('{"status":"ok","module":"MyModule"}{"status":"ok","module":"MyModule"}{"status":"ok","module":"MyModule"}');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['PATH_INFO'] = 'api/v1/my-module/index';

        \IVAgafonov\System\Application::init($config);
        \IVAgafonov\System\Application::run('MyModule');
        $this->assertFalse(empty(\IVAgafonov\System\Application::$config['Modules']));
    }
}