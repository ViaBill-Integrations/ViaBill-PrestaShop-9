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
use ViaBill\Util\NumberUtility;

/**
 * ViaBill Actions Controller Class.
 *
 * Class AdminViaBillActionsController
 */
class AdminViaBillActionsController extends ModuleAdminController
{
    /**
     * Module Main Class Variable Declaration.
     *
     * @var ViaBill
     */
    public $module;

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
        if ($this->ajax) {
            $this->captureAjaxConfirmationMessageProcess();
            return;
        }

        $this->capturePostProcess();
    }

    /**
     * Capture Order Action And Handle It.
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function capturePostProcess()
    {
        $isAction = Tools::isSubmit('capturePayment') ||
            Tools::isSubmit('refundPayment') ||
            Tools::isSubmit('cancelPayment') ||
            Tools::isSubmit('renewPayment');

        // Payment actions change money flows: only accept them as POST
        // requests carrying a valid back office token. The core check is
        // used because, depending on the PrestaShop version and the
        // "token in URLs" setting, the token is the legacy 'token' parameter,
        // the Symfony '_token' parameter or absent.
        if ($isAction && (
            !isset($_SERVER['REQUEST_METHOD']) ||
            strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST' ||
            !$this->checkToken()
        )) {
            $this->errors[] = $this->l('Form token mismatch detected.');

            return;
        }

        /** @var \ViaBill\Service\Handler\PaymentManagementHandler $paymentHandler */
        /** @var \ViaBill\Service\MessageService $messageService */
        $paymentHandler = $this->module->getModuleContainer()->get('service.handler.paymentManagement');
        $messageService = $this->module->getModuleContainer()->get('service.message');

        $order = new Order((int) Tools::getValue('id_order'));

        $isCapture = Tools::isSubmit('capturePayment');
        $isRefund = Tools::isSubmit('refundPayment');
        $isCancel = Tools::isSubmit('cancelPayment');
        $isRenew = Tools::isSubmit('renewPayment');

        $amount = 0.0;

        if ($isCapture) {
            $amount = (float) NumberUtility::replaceCommaToDot(Tools::getValue('capture_amount'));
        }

        if ($isRefund) {
            $amount = (float) NumberUtility::replaceCommaToDot(Tools::getValue('refund_amount'));
        }

        $handleResponse = $paymentHandler->handle(
            $order,
            $isCancel,
            $isCapture,
            $isRefund,
            $isRenew,
            $amount
        );

        $errors = $handleResponse->getErrors();
        $warnings = $handleResponse->getWarnings();
        $confirmations = [];

        if (empty($errors) && $handleResponse->getSuccessMessage()) {
            $confirmations[] = $handleResponse->getSuccessMessage();
        }

        $messageService->redirectWithMessages($order, $confirmations, $errors, $warnings);
    }

    /**
     * Capture Order Action Ajax Confirmation And Return Confirmation Message.
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function captureAjaxConfirmationMessageProcess()
    {           
        /*
        if ($this->token !== Tools::getValue('token')) {
            $this->ajaxResponse($this->l('Form token mismatch detected.'));
        }
        */

        $order = new Order((int) Tools::getValue('idOrder'));
        if (!Validate::isLoadedObject($order)) {
            $this->ajaxResponse([
                'success' => false,
                'message' => $this->l('Form confirmation error. Order was not found.'),
            ]);
        }

        $amount = (float) Tools::getValue('amount');
        $type = (string) Tools::getValue('type');

        $currency = new Currency((int) $order->id_currency);
        $locale = $this->context->getCurrentLocale();
        $convertedPrice = $locale->formatPrice($amount, $currency->iso_code);

        switch ($type) {
            case 'capture':
                $message =
                     sprintf(
                         $this->l('Are you sure that you want to capture %s ?'),
                         $convertedPrice
                     );
                break;
            case 'refund':
                $message =
                    sprintf(
                        $this->l('Are you sure that you want to refund %s ?'),
                        $convertedPrice
                    );
                break;
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
