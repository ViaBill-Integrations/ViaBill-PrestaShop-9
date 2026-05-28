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

namespace ViaBill\Builder\Payment;

use Configuration;
use Link;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use ViaBill\Builder\Template\TagBodyTemplate;
use ViaBill\Config\Config;
use ViaBill\Service\Validator\Payment\CurrencyValidator;

/**
 * Class PaymentOptionsBuilder
 */
class PaymentOptionsBuilder
{
    /**
     * @var Link
     */
    private $link;

    /**
     * @var \ViaBill
     */
    private $module;

    /**
     * @var TagBodyTemplate
     */
    private $tagBodyTemplate;

    /**
     * @var \Smarty
     */
    private $smarty;

    /**
     * @var float
     */
    private $orderPrice;

    /**
     * @var string
     */
    private $controller;

    /**
     * @var \Language
     */
    private $language;

    /**
     * @var \Currency
     */
    private $currency;

    /**
     * @var array
     */
    private $productTypes;

    /**
     * @var CurrencyValidator
     */
    private $currencyValidator;

    public function __construct(
        \ViaBill $module,
        TagBodyTemplate $tagBodyTemplate,
        CurrencyValidator $currencyValidator
    ) {
        $this->module = $module;
        $this->tagBodyTemplate = $tagBodyTemplate;
        $this->currencyValidator = $currencyValidator;
    }

    /**
     * @param Link $link
     */
    public function setLink(Link $link)
    {
        $this->link = $link;
    }

    /**
     * @param \Smarty $smarty
     */
    public function setSmarty($smarty)
    {
        $this->smarty = $smarty;
    }

    /**
     * @param float $orderPrice
     */
    public function setOrderPrice($orderPrice)
    {
        $this->orderPrice = $orderPrice;
    }

    /**
     * @param string $controller
     */
    public function setController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * @param \Language $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @param \Currency $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * Sets Product Types based on payment method.
     *
     * @param string $payment_method
     */
    public function setProductTypes($payment_method)
    {
        if ($payment_method == 'tbyb') {
            $this->productTypes = ['tbyb'];
        } else {
            $this->productTypes = ['light', 'liberty', 'plus'];
        }
    }

    /**
     * Gets ViaBill Payment Option.
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getPaymentOptions()
    {
        if (!$this->currencyValidator->isCurrencyMatches($this->currency)) {
            return [];
        }

        $url = $this->link->getModuleLink($this->module->name, 'checkout');
        $paymentOptions = [];

        if (!Config::isHideInCheckout()) {
            $paymentOption = new PaymentOption();
            $paymentOption->setAction($url);

            if (Configuration::get(Config::VIABILL_LOGO_DISPLAY_IN_CHECKOUT)) {
                $lang = strtolower($this->language->iso_code);

                if ($lang) {
                    $paymentOption->setLogo(
                        $this->module->getPathUri() . 'views/img/viabill_logo_tagline.' . $lang . '.png'
                    );
                } else {
                    $paymentOption->setLogo(
                        $this->module->getPathUri() . 'views/img/viabill_logo_tagline.png'
                    );
                }
            } else {
                $paymentOption->setCallToActionText(
                    $this->module->l(
                        'Pay with ViaBill'                        
                    )
                );
            }

            $paymentOption->setModuleName($this->module->name);

            if ($this->module->isPriceTagActive($this->controller)) {
                $this->constructTag($paymentOption, 'monthly');
            }

            $paymentOptions[] = $paymentOption;
        }

        if (Config::isTBYBAvailable(null, $this->currency)) {
            if (Configuration::get(Config::ENABLE_TRY_BEFORE_YOU_BUY)) {
                $url = $this->link->getModuleLink($this->module->name, 'checkout');
                $url = $this->addTryBeforeYouBuyURLParam($url);

                $tryPaymentOption = new PaymentOption();
                $tryPaymentOption->setAction($url);

                if (Configuration::get(Config::VIABILL_LOGO_DISPLAY_IN_CHECKOUT)) {
                    $lang = strtolower($this->language->iso_code);

                    if ($lang) {
                        $tryPaymentOption->setLogo(
                            $this->module->getPathUri() . 'views/img/viabill_try_logo_tagline.' . $lang . '.png'
                        );
                    } else {
                        $tryPaymentOption->setLogo(
                            $this->module->getPathUri() . 'views/img/viabill_try_logo_tagline.png'
                        );
                    }
                } else {
                    $tryPaymentOption->setCallToActionText(
                        $this->module->l(
                            'Pay with ViaBill'
                        )
                    );
                }

                $tryPaymentOption->setModuleName($this->module->name);

                if ($this->module->isPriceTagActive($this->controller)) {
                    $this->constructTag($tryPaymentOption, 'tbyb');
                }

                $paymentOptions[] = $tryPaymentOption;
            }
        }

        return $paymentOptions;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    private function addTryBeforeYouBuyURLParam($url)
    {
        if (strpos($url, '?') !== false) {
            $url = $url . '&trybeforeyoubuy=1';
        } else {
            $url = $url . '?trybeforeyoubuy=1';
        }

        return $url;
    }

    /**
     * @param PaymentOption $paymentOption
     *
     * @throws \SmartyException
     */
    private function constructTag(PaymentOption $paymentOption, $payment_method = null)
    {
        $this->tagBodyTemplate->setSmarty($this->smarty);
        $this->tagBodyTemplate->setPrice($this->orderPrice);
        $this->tagBodyTemplate->setView(Config::DATA_PAYMENT);
        $this->tagBodyTemplate->setLanguage($this->language);
        $this->tagBodyTemplate->setCurrency($this->currency);
        $this->tagBodyTemplate->setProductTypes($payment_method);
        $this->smarty->assign($this->tagBodyTemplate->getSmartyParams());

        $html = $this->tagBodyTemplate->getHtml();
        $paymentOption->setAdditionalInformation($html);
    }
}