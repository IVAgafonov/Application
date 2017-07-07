<?php

namespace IVAgafonov\System;

class Application implements ApplicationInterface
{
    static $config = [];
    static $services = [];

    public static function init($config)
    {
        self::loadModules($config);
        unset($config['Modules']);
        self::$config = array_replace_recursive(self::$config, $config);
    }

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
        $router->run();
    }

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