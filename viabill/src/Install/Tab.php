<?php
/**
* NOTICE OF LICENSE
*
* @author    Written for or by ViaBill
* @copyright Copyright (c) Viabill
* @license   Addons PrestaShop license limitation
*
* @see       /LICENSE
*/

namespace ViaBill\Install;

/**
 * Class Tab
 */
class Tab
{
    /**
     * @var string
     */
    private $controllerInvisibleName = 'AdminViaBillTabs';

    /**
     * @var string
     */
    private $controllerSettingsName = 'AdminViaBillSettings';

    /**
     * @var string
     */
    private $controllerAuthenticationName = 'AdminViaBillAuthentication';

    /**
     * @var string
     */
    private $controllerActionsName = 'AdminViaBillActions';

    /**
     * @var string
     */
    private $controllerCustomCodeName = 'AdminViaBillCustomCode';

    /**
     * @var string
     */
    private $controllerContactName = 'AdminViaBillContact';

    /**
     * @var string
     */
    private $controllerTroubleshootName = 'AdminViaBillTroubleshoot';

    /**
     * @var string
     */
    private $controllerConflictName = 'AdminViaBillConflict';

    /**
     * @var \ViaBill
     */
    private $module;

    public function __construct(\ViaBill $module)
    {
        $this->module = $module;
    }

    /**
     * Gets Module Tabs.
     *
     * @return array
     */
    public function getTabs()
    {
        return [
            [
                'name' => $this->module->displayName,
                'ParentClassName' => 'AdminParentModulesSf',
                'class_name' => $this->controllerInvisibleName,
                'visible' => false,
            ],
            [
                'name' => $this->module->l(
                    'Ajax'
                ),
                'ParentClassName' => 'AdminParentModulesSf',
                'class_name' => $this->controllerActionsName,
                'visible' => false,
                'module_tab' => true,
            ],
            [
                'name' => $this->module->l(
                    'Authentication'
                ),
                'ParentClassName' => $this->controllerInvisibleName,
                'class_name' => $this->controllerAuthenticationName,
                'visible' => true,
                'module_tab' => true,
            ],
            [
                'name' => $this->module->l(
                    'Settings'
                ),
                'ParentClassName' => $this->controllerInvisibleName,
                'class_name' => $this->controllerSettingsName,
                'visible' => true,
                'module_tab' => true,
            ],
            [
                'name' => $this->module->l(
                    'Custom CSS/JS'
                ),
                'ParentClassName' => $this->controllerInvisibleName,
                'class_name' => $this->controllerCustomCodeName,
                'visible' => true,
                'module_tab' => true,
            ],
            [
                'name' => $this->module->l(
                    'Contact'
                ),
                'ParentClassName' => $this->controllerInvisibleName,
                'class_name' => $this->controllerContactName,
                'visible' => true,
                'module_tab' => true,
            ],
            [
                'name' => $this->module->l(
                    'Troubleshooting'
                ),
                'ParentClassName' => $this->controllerInvisibleName,
                'class_name' => $this->controllerTroubleshootName,
                'visible' => true,
                'module_tab' => true,
            ],
            [
                'name' => $this->module->l(
                    'Ajax'
                ),
                'ParentClassName' => 'AdminParentModulesSf',
                'class_name' => $this->controllerActionsName,
                'visible' => false,
                'module_tab' => true,
            ],
        ];
    }

    /**
     * Gets Invisible Controller Name.
     *
     * @return string
     */
    public function getControllerInvisibleName()
    {
        return $this->controllerInvisibleName;
    }

    /**
     * Gets Settings Controller Name.
     *
     * @return string
     */
    public function getControllerSettingsName()
    {
        return $this->controllerSettingsName;
    }

    /**
     * Gets Authentication Controller Name.
     *
     * @return string
     */
    public function getControllerAuthenticationName()
    {
        return $this->controllerAuthenticationName;
    }

    /**
     * Gets Action Controller Name.
     *
     * @return string
     */
    public function getControllerActionsName()
    {
        return $this->controllerActionsName;
    }
}