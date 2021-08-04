<?php

namespace ModSeo\FileManagerBundle\Twig;

use ModSeo\FileManagerBundle\Helpers\FileManager;

use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class OrderExtension extends AbstractExtension
{
    // const ASC = 'asc';
    // const DESC = 'desc';
    // const ICON = [self::ASC => 'up', self::DESC => 'down'];
    
    const ASC = 'asc';
    const DESC = 'desc';
    const ICON = [self::ASC => 'arrow_drop_up', self::DESC => 'arrow_drop_down'];

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * OrderExtension constructor.
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function order(Environment $environment, FileManager $fileManager, $type)
    {
        $order = self::ASC === $fileManager->getQueryParameter('order');
        $active = $fileManager->getQueryParameter('orderby') === $type ? 'actived' : null;
        $orderBy = [];
        $orderBy['orderby'] = $type;
        $orderBy['order'] = $active ? ($order ? self::DESC : self::ASC) : self::ASC;
        $parameters = array_merge($fileManager->getQueryParameters(), $orderBy);

        //$icon = $active ? '-'.($order ? self::ICON[self::ASC] : self::ICON[self::DESC]) : '';
        //$icon = $active ? ($order ? self::ICON[self::ASC] : self::ICON[self::DESC]) : '';
        $icon = $active ? ($order ? self::ASC : self::DESC) : '';

        $href = $this->router->generate('file_manager', $parameters);

        return $environment->render('@Modules/mod_seo/src/filemanagerbundle/Resources/views/extension/_order.html.twig', [
            'active' => $active,
            'href' => $href,
            'icon' => $icon,
            'type' => $type,
            'islist' => 'list' === $fileManager->getView(),
        ]);
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            'order' => new TwigFunction('order', [$this, 'order'],
                ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }
}
