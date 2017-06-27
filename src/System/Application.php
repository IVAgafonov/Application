<?php

namespace IVAgafonov\System;

class Application implements ApplicationInterface
{
    static $config = [];

    public static function init($config)
    {
        self::loadModules($config);
        unset($config['Modules']);
        self::$config = array_merge_recursive(self::$config, $config);
    }

    public static function loadModules($config)
    {
        if (!isset($config['Modules']) || !is_array($config)) {
            header("HTTP/1.1 500 Internal server error");
            echo json_encode(['error' => ['code' => 100, 'text' => 'Invalid application config']]);
            return;
        }

        foreach ($config['Modules'] as $module) {
            self::loadModule($module);
        }

        foreach (self::$config['Modules'] as $moduleName => $modulePath) {
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

    public static function loadModule($moduleName)
    {
        self::$config['Modules'][$moduleName] = self::moduleNameToPath($moduleName);
        $applicationConfig = self::moduleNameToPath($moduleName)."\\config\\ApplicationConfig.php";
        $moduleConfig = self::moduleNameToPath($moduleName)."\\config\\ModuleConfig.php";
        if (file_exists($applicationConfig) && file_exists($moduleConfig)) {
            $modules = include $applicationConfig;
            $config = include $moduleConfig;
            if ($config && is_array($config)) {
                self::$config = array_merge_recursive(self::$config, $config);
            }
            if ($modules && is_array($modules)) {
                foreach ($modules as $module) {
                    self::loadModule($module);
                }
            }
        } else {
            header("HTTP/1.1 500 Internal server error");
            echo json_encode(['error' => ['code' => 120, 'text' => 'Invalid module']]);
            return;
        }
    }

    public static function moduleNameToPath($moduleName)
    {
        $path = __DIR__."\\..\\..\\..\\".strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $moduleName))."\\";
        if (!directoryExists($path)) {
            header("HTTP/1.1 500 Internal server error");
            echo json_encode(['error' => ['code' => 140, 'text' => 'Module path not found']]);
            return;
        }
        return $path;
    }
}