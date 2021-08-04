<?php

namespace ModSeo\FileManagerBundle;

use PrestaShop\PrestaShop\Core\ConfigurationInterface;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Class FileManagerSettings is responsible of accessing/saving the settings in PrestaShop configuration
 */
class FileManagerSettings
{
    const SETTINGS_KEY = 'FILE_MANAGER_SETTINGS';

    /** @var ConfigurationInterface */
    private $configuration;

    /**
     * @param array $languages
     */
    private $languages;

    /**
     * @param ConfigurationInterface $configuration
     */
    public function __construct(
        ConfigurationInterface $configuration,
        array $languages
    ) {
        $this->configuration = $configuration;
        $this->languages = $languages;

        // $configDirectory = __DIR__.'/Resources/config';
        // $configFile = $configDirectory.'/config.yml';
        // $filesystem = new Filesystem();
        // if($filesystem->exists($configFile)){
        //     $configValues = Yaml::parse(file_get_contents($configDirectory.'/config.yml'));
        //     $base_url = $this->configuration->get('_PS_BASE_URL_');
        //     $base_uri = $this->configuration->get('__PS_BASE_URI__');
        //     $configValues['file_manager_config']['web_dir'] = $base_url . $base_uri;
        //     $this->saveSettings($configValues);
        // }
    }

    /**
     * @return array
     */
    public function getDefaultSettings()
    {
        //$web_dir = $this->configuration->get('_PS_IMG_DIR_');

        // $web_dir = 'img';

        // return [
        //     'web_dir' => $web_dir,
        //     'conf' => 
        //         [
        //             'dir' => '../img',
        //             'type' => ['file', 'image', 'media'],
        //             'tree' => true,
        //             'twig_extension' => '',
        //             'cachebreaker' => '',
        //             'view' => array(['thumbnail', 'list'], ['list']),
        //         ]
            
        // ];
    }

    public function initSettings()
    {
        $this->saveSettings($this->getDefaultSettings());
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        // $configurationSettings = $this->configuration->get(self::SETTINGS_KEY);
        // if (empty($configurationSettings)) {
        //     return $this->getDefaultSettings();
        // }
        // $settings = json_decode($configurationSettings, true);

        // return empty($settings) ? $this->getDefaultSettings() : $settings['file_manager_config'];

        $configDirectory = __DIR__.'/Resources/config';
        $configFile = $configDirectory.'/config.yml';
        $filesystem = new Filesystem();
        if($filesystem->exists($configFile)){
            $configValues = Yaml::parse(file_get_contents($configDirectory.'/config.yml'));
            $base_url = $this->configuration->get('_PS_BASE_URL_');
            $base_uri = $this->configuration->get('__PS_BASE_URI__');
            $configValues['file_manager_config']['web_dir'] = $base_url . $base_uri;
            return $configValues['file_manager_config'];
        }

        return false;
    }

    /**
     * @param array $settings
     */
    public function saveSettings(array $settings)
    {
        $this->configuration->set(self::SETTINGS_KEY, json_encode($settings));
    }
}