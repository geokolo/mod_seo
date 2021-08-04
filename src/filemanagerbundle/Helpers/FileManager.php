<?php

namespace ModSeo\FileManagerBundle\Helpers;

use Symfony\Component\Routing\RouterInterface;

class FileManager
{
    const VIEW_THUMBNAIL = 'thumbnail';
    const VIEW_LIST = 'list';

    private $queryParameters;
    private $settings;
    private $kernelRoute;
    private $router;
    
    /**
     * FileManager constructor.
     *
     * @param $queryParameters
     * @param $webDir
     *
     * @internal param $basePath
     */
    public function __construct($queryParameters, $settings, $kernelRoute, RouterInterface $router, $webDir)
    {
        $this->queryParameters = $queryParameters;
        $this->settings = $settings;
        $this->kernelRoute = $kernelRoute;
        $this->router = $router;
        $this->webDir = $webDir;   
    }

    public function getDirName()
    {
        return \dirname($this->getBasePath());
    }

    public function getBaseName()
    {
        return basename($this->getBasePath());
    }

    /**
     * @return bool|string
     */
    public function getBasePath()
    {
        return realpath($this->settings['dir']);
    }

    public function getCurrentRoute()
    {
        return urldecode($this->getRoute());
    }

    public function getRoute()
    {
        return isset($this->getQueryParameters()['route']) && '/' !== $this->getQueryParameters()['route'] ? $this->getQueryParameters()['route'] : null;
    }

    public function getCurrentPath()
    {
        return realpath($this->getBasePath() . $this->getCurrentRoute());
    }

    public function getType()
    {
        return $this->mergeConfAndQuery('type');
    }

    public function getRegex()
    {
        if (isset($this->getConfiguration()['regex'])) {
            return '/' . $this->getConfiguration()['regex'] . '/i';
        }

        switch ($this->getType()) {
            case 'media':
                return '/\.(mp4|ogg|webm)$/i';
                break;
            case 'image':
                return '/\.(gif|png|jpe?g|svg)$/i';
            case 'file':
            default:
                return '/.+$/i';
        }
    }

    public function getTree()
    {
        return $this->mergeQueryAndConf('tree', true);
    }

    public function getView()
    {
        return $this->mergeQueryAndConf('view', 'list');
    }
   
    private function mergeQueryAndConf($parameter, $default = null)
    {
        if (null !== $this->getQueryParameter($parameter)) {
            return $this->getQueryParameter($parameter);
        }
        if (null !== $this->getConfigurationParameter($parameter)) {
            return $this->getConfigurationParameter($parameter);
        }

        return $default;
    }

    /**
     * @return mixed
     */
    public function getQueryParameters()
    {
        return $this->queryParameters;
    }

    public function getConfigurationParameter($parameter)
    {
        return isset($this->getConfiguration()[$parameter]) ? $this->getConfiguration()[$parameter] : null;
    }

    public function getQueryParameter($parameter)
    {
        return isset($this->getQueryParameters()[$parameter]) ? $this->getQueryParameters()[$parameter] : null;
    }

    /**
     * @return mixed
     */
    public function getConfiguration()
    {
        return $this->settings;
    }

    private function mergeConfAndQuery($parameter, $default = null)
    {
        if (null !== $this->getConfigurationParameter($parameter)) {
            return $this->getConfigurationParameter($parameter);
        }
        if (null !== $this->getQueryParameter($parameter)) {
            return $this->getQueryParameter($parameter);
        }

        return $default;
    }

    public function getImagePath()
    {
        $baseUrl = $this->getBaseUrl();
        if ($baseUrl) {
            return $baseUrl . $this->getCurrentRoute() . '/';
        }

        return false;
    }

    private function getBaseUrl()
    {
        $webPath = $this->webDir;

        $dirl = new \SplFileInfo($this->getConfiguration()['dir']);
        $base = $this->prepareDir($dirl->getFilename());

        return mb_substr($webPath, 0, strrpos($webPath,'/')) . '/' . $base;
    }

    private function prepareDir($path) 
    {       
        $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = array();
        foreach ($parts as $part) {
            if ('.' == $part && count($absolutes) == 0) continue;
            if ('..' == $part && count($absolutes) == 0) {
                continue;
            } else {
                $absolutes[] = $part;
            }
        }
        return implode(DIRECTORY_SEPARATOR, $absolutes);
    }

    public function getModule()
    {
        return isset($this->getQueryParameters()['module']) ? $this->getQueryParameters()['module'] : null;
    }

    // parent url
    public function getParent()
    {
        $queryParentParameters = $this->queryParameters;
        $parentRoute = \dirname($this->getCurrentRoute());

        if (\DIRECTORY_SEPARATOR !== $parentRoute) {
            $queryParentParameters['route'] = \dirname($this->getCurrentRoute());
        } else {
            unset($queryParentParameters['route']);
        }

        $parentRoute = $this->router->generate('file_manager', $queryParentParameters);

        return $this->getRoute() ? $parentRoute : null;
    }
}