<?php

namespace ModSeo\FileManagerBundle\Service;

use ModSeo\FileManagerBundle\FileManagerSettings;

use Symfony\Component\DependencyInjection\ContainerInterface;

class FilemanagerService
{
    /**
     * @var array
     */
    private $settings;
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(FileManagerSettings $filemanagerSettings, ContainerInterface $container)
    {
        $this->container = $container;
        $this->settings = $filemanagerSettings->getSettings();
    }

    public function getBasePath($queryParameters)
    {
        $conf = $queryParameters['conf'];
        $managerConf = $this->settings['conf'];
        if (isset($managerConf[$conf]['dir'])) {
            $managerConf[$conf]['dir'] = $this->prepareDir($managerConf[$conf]['dir']);
            return $managerConf[$conf];
        }

        // if (isset($managerConf[$conf]['service'])) {
        //     $extra = isset($queryParameters['extra']) ? $queryParameters['extra'] : [];
        //     $confService = $this->container->get($managerConf[$conf]['service'])->getConf($extra);
        //     return array_merge($managerConf[$conf], $confService);
        // }

        throw new \RuntimeException('Please define a "dir" or a "service" parameter in your config.yml');
    }

    function prepareDir($path) 
    {       
        $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = array();
        $absolutes[] = '..' . \DIRECTORY_SEPARATOR;
        foreach ($parts as $part) {
            if ('.' == $part) continue;
            if ('..' == $part) {
                continue;
            } else {
                $absolutes[] = $part;
            }
        }
        return implode(DIRECTORY_SEPARATOR, $absolutes);
    }
}
