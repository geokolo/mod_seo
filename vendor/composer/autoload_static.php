<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit17f2b66e4e619c3142df7dae6e9f0a7f
{
    public static $prefixLengthsPsr4 = array (
        'M' => 
        array (
            'ModSeo\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'ModSeo\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'ModSeo\\FileManagerBundle\\Controller\\FileManagerController' => __DIR__ . '/../..' . '/src/filemanagerbundle/Controller/FileManagerController.php',
        'ModSeo\\FileManagerBundle\\FileManagerSettings' => __DIR__ . '/../..' . '/src/filemanagerbundle/FileManagerSettings.php',
        'ModSeo\\FileManagerBundle\\Helpers\\File' => __DIR__ . '/../..' . '/src/filemanagerbundle/Helpers/File.php',
        'ModSeo\\FileManagerBundle\\Helpers\\FileManager' => __DIR__ . '/../..' . '/src/filemanagerbundle/Helpers/FileManager.php',
        'ModSeo\\FileManagerBundle\\Helpers\\GDImage' => __DIR__ . '/../..' . '/src/filemanagerbundle/Helpers/GDImage.php',
        'ModSeo\\FileManagerBundle\\Helpers\\UploadHandler' => __DIR__ . '/../..' . '/src/filemanagerbundle/Helpers/UploadHandler.php',
        'ModSeo\\FileManagerBundle\\Service\\CustomConfServiceInterface' => __DIR__ . '/../..' . '/src/filemanagerbundle/Service/CustomConfServiceInterface.php',
        'ModSeo\\FileManagerBundle\\Service\\FileTypeService' => __DIR__ . '/../..' . '/src/filemanagerbundle/Service/FileTypeService.php',
        'ModSeo\\FileManagerBundle\\Service\\FilemanagerService' => __DIR__ . '/../..' . '/src/filemanagerbundle/Service/FilemanagerService.php',
        'ModSeo\\FileManagerBundle\\Twig\\FileTypeExtension' => __DIR__ . '/../..' . '/src/filemanagerbundle/Twig/FileTypeExtension.php',
        'ModSeo\\FileManagerBundle\\Twig\\OrderExtension' => __DIR__ . '/../..' . '/src/filemanagerbundle/Twig/OrderExtension.php',
        'Mod_Seo' => __DIR__ . '/../..' . '/mod_seo.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit17f2b66e4e619c3142df7dae6e9f0a7f::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit17f2b66e4e619c3142df7dae6e9f0a7f::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit17f2b66e4e619c3142df7dae6e9f0a7f::$classMap;

        }, null, ClassLoader::class);
    }
}
