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

use ViaBill\Config\Config;

require_once dirname(__FILE__) . '/../../vendor/autoload.php';

/**
 * ViaBill Contact Controller Class.
 *
 * Class AdminViaBillContactController
 */
class AdminViaBillConflictController extends ModuleAdminController
{
    /**
     * Module Main Class Variable Declaration.
     *
     * @var ViaBill
     */
    public $module;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->context = Context::getContext();

        parent::__construct();
    }

    /**
     * Calls Class Processes By Checking Is Ajax Is False.
     *
     * @return bool|ObjectModel|void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        $this->disableThirdPartyPaymentMethod();
    }

    /**
     * Update third-party payment method status in order to resolve conflict
     *
     * @throws PrestaShopException
     */
    private function disableThirdPartyPaymentMethod()
    {        
        if (Tools::getAdminTokenLite('AdminViaBillConflict') !== Tools::getValue('token')) {        
            $this->ajaxResponse($this->l('Form token mismatch detected.'));
        }

        $conflict_key = Config::MODULE_CONFLICT_THIRD_PARTY_KEY;
        if (Configuration::hasKey($conflict_key)) {
            Configuration::updateValue($conflict_key, 0);
            $message = $this->l('The third party payment method disabled!');
        } else {
            $message = $this->l('The third party payment method could not be found!');
        }
        $this->ajaxResponse($message);
    }

    public function ajaxResponse($data)
    {
        die(is_string($data) ? $data : Tools::jsonEncode($data));
    }

    /*
    Legacy wrapper for translation l method
    */
    public function l($string, $specific = false, $locale = null)
    {
        return $this->trans($string, [], 'Modules.Viabill.Admin', $locale);
    }
}
