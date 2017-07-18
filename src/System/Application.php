<?php

namespace IVAgafonov\System;

/**
 * Class Application
 * @package IVAgafonov\System
 */
class Application implements ApplicationInterface
{
    /**
     * @var array
     */
    static $config = [];

    /**
     * @var array
     */
    static $services = [];

    /**
     * @param array $config
     * @return int
     */
    public static function init($config)
    {
        self::loadEnvironment();
        self::parseParams();
        self::loadModules($config);
        unset($config['Modules']);
        self::$config = array_replace_recursive(self::$config, $config);
        return true;
    }

    public static function loadEnvironment()
    {
        if (empty($_SERVER['PATH_INFO'])) {
            header("HTTP/1.1 400 Bad request");
            throw new \Exception(json_encode(['error' => ['code' => 10, 'text' => 'Router: Empty path info']]));
        }

        if (empty($_SERVER['REQUEST_METHOD'])) {
            header("HTTP/1.1 400 Bad request");
            throw new \Exception(json_encode(['error' => ['code' => 11, 'text' => 'Router: Invalid request method']]));
        }

        if (!in_array(strtoupper($_SERVER['REQUEST_METHOD']), ['GET', 'POST', 'UPDATE', 'DELETE', 'PUT', 'PATH', 'OPTIONS', 'HEAD'])) {
            header("HTTP/1.1 405 Method not allowed");
            throw new \Exception(json_encode(['error' => ['code' => 405, 'text' => 'Router: Method not allowed']]));
        }

        self::$config['current']['method'] = strtoupper($_SERVER['REQUEST_METHOD']);

        $path = explode("/", $_SERVER['PATH_INFO']);

        while (isset($path[0]) && !preg_match("/v\d{1,3}/i", $path[0])) {
            array_shift($path);
            array_filter($path);
            array_values($path);
        }

        if (is_array($path) && count($path) > 1) {
            if (!empty($path)) {
                self::$config['current']['apiVersion'] = strtolower(array_shift($path));
            }
            if (!empty($path)) {
                self::$config['current']['controller'] = ucfirst(strtolower(array_shift($path)));
                self::$config['current']['controller'] = preg_replace_callback('/-\w/i', function($matches) {
                    return substr(strtoupper($matches[0]), 1);
                }, self::$config['current']['controller']);
            }
            if (!empty($path)) {
                self::$config['current']['action'] = strtolower(array_shift($path));
            }
        }
    }

    public static function parseParams()
    {
        self::$config['current']['params'] = [];
        switch(self::$config['current']['params']) {
            case 'GET':
                self::$config['current']['params'] = $_GET;
                break;
            case 'POST':
                if (empty($_POST)) {
                    $_POST = (array)json_decode(trim(file_get_contents('php://input')), true);
                }
                self::$config['current']['params'] = $_POST;
                self::$config['current']['params'] = array_merge_recursive(self::$config['current']['params'], $_FILES);
                self::$config['current']['params'] = array_merge_recursive(self::$config['current']['params'], $_GET);
                break;
            default:
                self::$config['current']['params'] = (array)json_decode(trim(file_get_contents('php://input')), true);
                self::$config['current']['params'] = array_merge_recursive(self::$config['current']['params'], $_FILES);
                self::$config['current']['params'] = array_merge_recursive(self::$config['current']['params'], $_GET);
                self::$config['current']['params'] = array_merge_recursive(self::$config['current']['params'], $_POST);

        }
    }

    /**
     * @param $config
     * @throws \Exception
     */
    public static function loadModules($config)
    {
        if (!isset($config['Modules']) || !is_array($config)) {
            header("HTTP/1.1 500 Internal server error");
            throw new \Exception(json_encode(['error' => ['code' => 100, 'text' => 'Invalid application config']]));
        }

        foreach ($config['Modules'] as $module) {
            self::loadModule($module);
        }

        if (!empty($config['Services']) && is_array($config['Services'])) {
            foreach ($config['Services'] as $serviceName => $serviceData) {
                if (!empty(self::$services[$serviceName])) {
                    continue;
                }
                if (!empty($serviceData['object']) && class_exists($serviceData['object'])) {
                    $class = $serviceData['object'];
                    if (isset($serviceData['config'])) {
                        self::$services[$serviceName] = new $class($serviceData['config']);
                    } else {
                        self::$services[$serviceName] = new $class();
                    }
                }
            }
        }

        if (!empty(self::$config['Modules'])) {
            foreach (self::$config['Modules'] as $moduleName => $modulePath) {
                $moduleName = explode("/", $moduleName);
                $moduleName = preg_replace_callback('/-\w/i', function($matches) {
                    return substr(strtoupper($matches[0]), 1);
                }, $moduleName[1]);
                $moduleName = ucfirst($moduleName);
                $initClassName = "\\".$moduleName."\\Bootstrap\\Init";
                if (class_exists($initClassName)) {
                    $init = new $initClassName();
                    $initMethodName = 'init';
                    if (method_exists($init, $initMethodName)) {
                        $init->$initMethodName(self::$config);
                    }
                }
            }
        }
    }

    /**
     * @param string $moduleName
     * @throws \Exception
     */
    public static function loadModule($moduleName)
    {
        self::$config['Modules'][$moduleName] = self::moduleNameToPath($moduleName);
        $applicationConfig = self::moduleNameToPath($moduleName)."/config/ApplicationConfig.php";
        $moduleConfig = self::moduleNameToPath($moduleName)."/config/ModuleConfig.php";
        if (file_exists($applicationConfig) && file_exists($moduleConfig)) {
            $modules = include $applicationConfig;
            $config = include $moduleConfig;
            if ($config && is_array($config)) {
                self::$config = array_replace_recursive(self::$config, $config);
            }
            if ($modules['Modules'] && is_array($modules['Modules']) ) {
                foreach ($modules['Modules'] as $module) {
                    self::loadModule($module);
                }
            }
        } else {
            header("HTTP/1.1 500 Internal server error");
            throw new \Exception(json_encode(['error' => ['code' => 120, 'text' => 'Invalid module']]));
        }
    }

    /**
     * @param string $mainModuleName
     */
    public static function run($mainModuleName)
    {
        $initClassName = "\\".$mainModuleName."\\Bootstrap\\Init";
        if (class_exists($initClassName)) {
            $init = new $initClassName();
            $initMethodName = 'init';
            if (method_exists($init, $initMethodName)) {
                $init->$initMethodName(self::$config);
            }
        }
        $router = new \IVAgafonov\System\Router(self::$config);
        $router->setApiVersion(self::$config['current']['apiVersion']);
        $router->setMethod(self::$config['current']['method']);
        $router->setController(self::$config['current']['controller']);
        $router->setAction(self::$config['current']['action']);
        $router->setParams(self::$config['current']['params']);
        $router->run();
    }

    /**
     * @param string $moduleName
     * @return string
     * @throws \Exception
     */
    public static function moduleNameToPath($moduleName)
    {
        $path = __DIR__."/../../../".$moduleName;
        if (!file_exists($path)) {
            $path = __DIR__."/../../vendor/".strtolower($moduleName)."/";
            if (!file_exists($path)) {
                header("HTTP/1.1 500 Internal server error");
                throw new \Exception(json_encode(['error' => ['code' => 140, 'text' => 'Module path not found']]));
            }
        }
        return $path;
    }
}