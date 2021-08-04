<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    GeoKolo
 * @copyright GeoKolo
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

//require_once __DIR__.'/vendor/autoload.php';

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Mod_Seo extends Module implements WidgetInterface
{
    public function __construct()
    {
        $this->name = 'mod_seo';
        $this->author = 'GeoKolo';
        $this->version = '1.0.0';
        $this->need_instance = 0;
        $this->tab = 'administration';

        $this->bootstrap = false;
        parent::__construct();

        $this->displayName = $this->l('SEO implements');
        $this->description = $this->l('Adds a block to help a seo-management');

        $this->ps_versions_compliancy = array('min' => '1.7.6.0', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        return  parent::install();
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function getContent()
    {
        $route = SymfonyContainer::getInstance()->get('router')->generate('file_manager', array('conf' => 'default', 'view' => 'list'));
        Tools::redirectAdmin($route);
    }

    public function isUsingNewTranslationSystem()
    {
        return true;
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        
    }

    public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        return;
    }
}
