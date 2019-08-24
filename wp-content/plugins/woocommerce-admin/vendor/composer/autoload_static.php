<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita143a150bd945345e0b978e13ff12933
{
    public static $files = array (
        'fd9f5df955e5151499fd2f642091d0a0' => __DIR__ . '/../..' . '/includes/core-functions.php',
        'b907791bfd099af1d822ca201c3f03f0' => __DIR__ . '/../..' . '/includes/feature-config.php',
        'c440206b9a4cb2ee803d8c41ad1f292a' => __DIR__ . '/../..' . '/includes/page-controller-functions.php',
    );

    public static $prefixLengthsPsr4 = array (
        'C' => 
        array (
            'Composer\\Installers\\' => 20,
        ),
        'A' => 
        array (
            'Automattic\\WooCommerce\\Admin\\' => 29,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Composer\\Installers\\' => 
        array (
            0 => __DIR__ . '/..' . '/composer/installers/src/Composer/Installers',
        ),
        'Automattic\\WooCommerce\\Admin\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInita143a150bd945345e0b978e13ff12933::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita143a150bd945345e0b978e13ff12933::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
