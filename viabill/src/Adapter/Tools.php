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

namespace ViaBill\Adapter;

/**
 * Class Tools
 */
class Tools
{
    /**
     * Turns String To Uppercase.
     *
     * @param string $string
     *
     * @return bool|mixed|string|string[]|null
     */
    public function strToUpper($string)
    {
        return \Tools::strtoupper($string);
    }

    /**
     * Checks If Link Is Submitted.
     *
     * @param string $link
     *
     * @return bool
     */
    public function isSubmit($link)
    {
        return \Tools::isSubmit($link);
    }

    /**
     * Redirects To Link Location.
     *
     * @param string $redirectLink
     */
    public function redirect($redirectLink)
    {
        \Tools::redirect($redirectLink);
    }

    /**
     * Gets Value From $_POST / $_GET
     *
     * @param string $value
     *
     * @return mixed
     */
    public function getValue($value)
    {
        return \Tools::getValue($value);
    }

    /**
     * GetIsset Value From $_POST / $_GET
     *
     * @param string $value
     *
     * @return mixed
     */
    public function getIsset($value)
    {
        return \Tools::getIsset($value);
    }

    /**
     * Encrypts Password.
     *
     * @param string $value
     *
     * @return string
     */
    public function encrypt($value)
    {
        return \Tools::hash($value);
    }

    /**
     * Allows To Get The Content From Either A URL Or A Local File.
     *
     * @param string $url
     *
     * @return bool|string
     */
    public function fileGetContents($url)
    {
        return \Tools::file_get_contents($url);
    }

    /**
     * Formats Number.
     *
     * @param float|int|string $number
     *
     * @return string
     */
    public function displayNumber($number)
    {
        return \Context::getContext()->currentLocale->formatNumber($number);
    }

    /**
     * Return Price With Currency Sign For A Given Product.
     *
     * @param float $amount
     * @param mixed $currency
     *
     * @return string
     */
    public function displayPrice($amount, $currency = null)
    {
        if ($currency === null) {
            $currency = \Context::getContext()->currency;
        }

        $currencyCode = is_object($currency) && isset($currency->iso_code)
            ? $currency->iso_code
            : \Context::getContext()->currency->iso_code;

        return \Context::getContext()->currentLocale->formatPrice($amount, $currencyCode);
    }
}