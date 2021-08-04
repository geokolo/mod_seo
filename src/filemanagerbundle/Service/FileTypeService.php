<?php

namespace ModSeo\FileManagerBundle\Service;

use ModSeo\FileManagerBundle\Helpers\FileManager;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class FileTypeService
{
    const IMAGE_SIZE = [
        FileManager::VIEW_LIST => '24',
        FileManager::VIEW_THUMBNAIL => '50',
    ];

    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var Environment
     */
    private $twig;

    /**
     * FileTypeService constructor.
     *
     * @param Packages $packages
     */
    public function __construct(RouterInterface $router, Environment $twig)
    {
        $this->router = $router;
        $this->twig = $twig;
    }

    public function preview(FileManager $fileManager, SplFileInfo $file)
    {
        if ($fileManager->getImagePath()) {
            $filePath = htmlentities($fileManager->getImagePath() . rawurlencode($file->getFilename()));
        } else {
            $filePath = $this->router->generate('file_manager_file',
                array_merge($fileManager->getQueryParameters(), ['fileName' => rawurlencode($file->getFilename())]));
        }
        $extension = $file->getExtension();
        $type = $file->getType();
        if ('file' === $type) {
            $size = $this::IMAGE_SIZE[$fileManager->getView()];

            return $this->fileIcon($filePath, $extension, $size, true, $fileManager->getConfigurationParameter('twig_extension'), $fileManager->getConfigurationParameter('cachebreaker'));
        }
        if ('dir' === $type) {
            $href = $this->router->generate('file_manager', array_merge($fileManager->getQueryParameters(),
                ['route' => $fileManager->getRoute() . '/' . rawurlencode($file->getFilename())]));

            return [
                'path' => $filePath,
                'html' => "<i class='material-icons md-dark' aria-hidden='true'>folder</i>",
                'folder' => '<a  href="' . $href . '">' . $file->getFilename() . '</a>',
            ];
        }
    }

    public function accept($type)
    {
        switch ($type) {
            case 'image':
                $accept = 'image/*';
                break;
            case 'media':
                $accept = 'video/*';
                break;
            case 'file':
                return false;
            default:
                return false;
        }

        return $accept;
    }

    public function fileIcon($filePath, $extension = null, $size = 75, $lazy = false, $twigExtension = null, $cachebreaker = null)
    {
        $imageTemplate = null;

        if (null === $extension) {
            $filePathTmp = strtok($filePath, '?');
            $extension = pathinfo($filePathTmp, PATHINFO_EXTENSION);
        }
        switch (true) {
            case $this->isYoutubeVideo($filePath):
            case preg_match('/(mp4|ogg|webm|avi|wmv|mov)$/i', $extension):
                $fa = 'material-icons';
                $fn = 'movie';
                break;
            case preg_match('/(mp3|wav)$/i', $extension):
                $fa = 'material-icons';
                $fn = 'audiotrack';
                break;
            case preg_match('/(gif|png|jpe?g|svg|webp)$/i', $extension):

                $fileName = $filePath;
                if ($cachebreaker) {
                    $query = parse_url($filePath, PHP_URL_QUERY);
                    $time = 'time=' . time();
                    $fileName = $query ? $filePath . '&' . $time : $filePath . '?' . $time;
                }

                if ($twigExtension) {
                    $imageTemplate = str_replace('$IMAGE$', 'file_path', $twigExtension);
                }

                $html = $this->twig->render('@Modules/mod_seo/src/filemanagerbundle/Resources/views/views/preview.html.twig', [
                    'filename' => $fileName,
                    'size' => $size,
                    'lazy' => $lazy,
                    'twig_extension' => $twigExtension,
                    'image_template' => $imageTemplate,
                    'file_path' => $filePath
                ]);

                $generatabletypes = array();
                if(preg_match('/(png|jpe?g)$/i', $extension)){
                    array_push($generatabletypes, 'webp');
                }

                return [
                    'path' => $filePath,
                    'html' => $html,
                    'image' => true,
                    'generatabletype' => $generatabletypes,
                ];
            case preg_match('/(pdf)$/i', $extension):
                $fa = 'material-icons';
                $fn = 'picture_as_pdf';
                break;
            case preg_match('/(docx?)$/i', $extension):
                $fa = 'material-icons';
                $fn = 'motes';
                break;
            case preg_match('/(xlsx?|csv)$/i', $extension):
                $fa = 'material-icons';
                $fn = 'grading';
                break;
            case preg_match('/(pptx?)$/i', $extension):
                $fa = 'material-icons';
                $fn = 'assignment';
                break;
            case preg_match('/(zip|rar|gz)$/i', $extension):
                $fa = 'material-icons';
                $fn = 'archive';
                break;
            case filter_var($filePath, FILTER_VALIDATE_URL):
                $fa = 'material-icons';
                $fn = 'text_snippet';
                break;
            default:
                $fa = 'material-icons';
                $fn = 'description';
        }

        return [
            'path' => $filePath,
            'html' => "<i class='{$fa} md-dark' aria-hidden='true'>{$fn}</i>",
        ];
    }

    public function isYoutubeVideo($url)
    {
        $rx = '~
              ^(?:https?://)?                            
               (?:www[.])?                               
               (?:youtube[.]com/watch[?]v=|youtu[.]be/)  
               ([^&]{11})                                
                ~x';

        return $has_match = preg_match($rx, $url, $matches);
    }
}
