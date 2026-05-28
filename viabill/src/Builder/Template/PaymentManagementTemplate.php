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

namespace ViaBill\Builder\Template;

use Currency;
use Language;
use Order;
use ViaBill\Adapter\Configuration;
use ViaBill\Adapter\Tools;
use ViaBill\Config\Config;
use ViaBill\Service\Provider\OrderStatusProvider;

/**
 * Class PaymentManagementTemplate
 */
class PaymentManagementTemplate implements TemplateInterface
{
    /**
     * @var \Smarty
     */
    private $smarty;

    /**
     * @var \ViaBill
     */
    private $module;

    /**
     * @var string
     */
    private $formAction;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var OrderStatusProvider
     */
    private $statusProvider;

    /**
     * @var Order
     */
    private $order;

    /**
     * @var Tools
     */
    private $tools;

    /**
     * @var Language
     */
    private $language;

    public function __construct(
        \ViaBill $module,
        Configuration $configuration,
        OrderStatusProvider $statusProvider,
        Tools $tools
    ) {
        $this->module = $module;
        $this->configuration = $configuration;
        $this->statusProvider = $statusProvider;
        $this->tools = $tools;
    }

    /**
     * @param \Smarty $smarty
     */
    public function setSmarty(\Smarty $smarty)
    {
        $this->smarty = $smarty;
    }

    /**
     * @param Order $order
     */
    public function setOrder(Order $order)
    {
        $this->order = $order;
    }

    /**
     * @param Language $language
     */
    public function setLanguage(Language $language)
    {
        $this->language = $language;
    }

    /**
     * @param string $formAction
     */
    public function setFormAction($formAction)
    {
        $this->formAction = $formAction;
    }

    /**
     * @return array
     */
    public function getSmartyParams()
    {
        $isCancelled = $this->statusProvider->isCancelled($this->order);
        $remainingToRefund = $this->statusProvider->getRemainingToRefund($this->order);
        $currency = new \Currency($this->order->id_currency, $this->order->id_lang);

        return [
            'paymentManagement' => [
                'formAction' => $this->formAction,
                'orderId' => $this->order->id,
                'currencyError' => $this->checkCurrency(),
                'isCancelled' => $isCancelled,
                'isFullRefund' => $this->isFullRefund(),
                'currencySign' => $currency->getSign(),
                'cancelFormGroup' => [
                    'isVisible' => $this->isCancelVisible(),
                    'cancelConfirmation' => $this->configuration->get(Config::SINGLE_ACTION_CANCEL_CONF_MESSAGE),
                ],
                'captureFormGroup' => [
                    'isVisible' => $this->isCaptureVisible(),                
                    'remainingToCapture' => $this->getRemainingToCapture($this->order->total_paid_tax_incl),
                    'captureConfirmation' => $this->configuration->get(Config::SINGLE_ACTION_CAPTURE_CONF_MESSAGE),
                ],
                'refundFormGroup' => [
                    'isVisible' => $this->isRefundVisible(),
                    'remainingToRefund' => $remainingToRefund,
                    'refundConfirmation' => $this->configuration->get(Config::SINGLE_ACTION_REFUND_CONF_MESSAGE),
                ],
                'renewFormGroup' => [
                    'isVisible' => $this->isRenewVisible(),
                ],
            ],
        ];
    }

    /**
     * @return string
     *
     * @throws \SmartyException
     */
    public function getHtml()
    {
        $this->smarty->assign($this->getSmartyParams());

        return $this->smarty->fetch(
            $this->module->getLocalPath() . 'views/templates/admin/payment-management.tpl'
        );
    }

    /**
     * @param float $orderTotal
     *
     * @return float
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function getRemainingToCapture($orderTotal)
    {
        $idOrderCapture = \ViaBillOrderCapture::getPrimaryKey($this->order->id);
        $orderCapture = new \ViaBillOrderCapture($idOrderCapture);

        return (float) $orderTotal - (float) $orderCapture->getTotalCaptured();
    }

    /**
     * @return bool
     */
    private function isCancelVisible()
    {
        return $this->statusProvider->canBeCancelled($this->order);
    }

    /**
     * @return bool
     */
    private function isRefundVisible()
    {
        return $this->statusProvider->canBeRefunded($this->order);
    }

    /**
     * @return bool
     */
    private function isRenewVisible()
    {
        return $this->statusProvider->canBeRenewed($this->order);
    }

    /**
     * @return bool
     */
    private function isCaptureVisible()
    {
        return $this->statusProvider->canBeCaptured($this->order);
    }

    /**
     * @return bool
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function isFullRefund()
    {
        $idOrderCapture = \ViaBillOrderCapture::getPrimaryKey($this->order->id);
        $orderCapture = new \ViaBillOrderCapture($idOrderCapture);
        $idRefund = \ViaBillOrderRefund::getPrimaryKey($this->order->id);
        $refund = new \ViaBillOrderRefund($idRefund);

        if (!\Validate::isLoadedObject($orderCapture) || !\Validate::isLoadedObject($refund)) {
            return false;
        }

        $totalCaptured = $this->tools->displayNumber($orderCapture->getTotalCaptured());
        $totalRefunded = $this->tools->displayNumber($refund->getTotalRefunded());

        return $totalCaptured === $totalRefunded;
    }

    /**
     * @return string
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function checkCurrency()
    {
        $primary = \ViaBillOrder::getPrimaryKey($this->order->id);
        $orderMark = new \ViaBillOrder($primary);
        $message = '';

        if ((int) $this->order->id_currency !== (int) $orderMark->id_currency) {
            $currency = new Currency($orderMark->id_currency, $this->language->id);

            $message = sprintf(
                $this->module->l(
                    'For the current order, only %s is a supported currency.'                    
                ),
                $currency->name
            );
        }

        return $message;
    }
}