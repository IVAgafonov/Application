<?php

namespace IVAgafonov\System;

/**
 * Interface ApplicationInterface
 */
interface ApplicationInterface
{
    /**
     * @param $config
     * @return mixed
     */
    public static function init($config);
}