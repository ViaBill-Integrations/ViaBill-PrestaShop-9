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

namespace ViaBill\Service\Handler;

use Order;
use ViaBill\Adapter\Configuration;
use ViaBill\Adapter\Tools;
use ViaBill\Config\Config;
use ViaBill\Object\Api\Cancel\CancelRequest;
use ViaBill\Object\Handler\HandlerResponse;
use ViaBill\Service\Api\Cancel\CancelService;
use ViaBill\Service\Provider\OrderStatusProvider;
use ViaBill\Service\UserService;
use ViaBill\Util\DebugLog;
use ViaBill\Util\SignaturesGenerator;

/**
 * Class CancelPaymentHandler
 */
class CancelPaymentHandler
{
    /**
     * @var CancelService
     */
    private $cancelService;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var SignaturesGenerator
     */
    private $signaturesGenerator;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var \ViaBill
     */
    private $module;

    /**
     * @var OrderStatusProvider
     */
    private $stateProvider;

    /**
     * @var Tools
     */
    private $tools;

    public function __construct(
        \ViaBill $module,
        CancelService $cancelService,
        UserService $userService,
        SignaturesGenerator $signaturesGenerator,
        Configuration $configuration,
        OrderStatusProvider $stateProvider,
        Tools $tools
    ) {
        $this->cancelService = $cancelService;
        $this->userService = $userService;
        $this->signaturesGenerator = $signaturesGenerator;
        $this->configuration = $configuration;
        $this->module = $module;
        $this->stateProvider = $stateProvider;
        $this->tools = $tools;
    }

    /**
     * Handles Payment Cancel.
     *
     * @param Order $order
     *
     * @return HandlerResponse
     */
    public function handle(Order $order)
    {
        $cancelState = $this->configuration->get(Config::PAYMENT_CANCELED);
        $errors = [];
        $isCancelled = false;

        $debug_str = empty($order) ? '[empty]' : var_export($order, true);
        DebugLog::msg("Cancel Payment Handle / Order: $debug_str", 'notice');

        try {
            $isCancelled = $this->stateProvider->isCancelled($order);
        } catch (\Exception $exception) {
            $exceptionErrors = json_decode($exception->getMessage());

            if (is_array($exceptionErrors)) {
                foreach ($exceptionErrors as $error) {
                    $errors[] = $error;
                }
            } else {
                $errors[] = $exceptionErrors;
            }
        }

        if ((int) $order->getCurrentState() === (int) $cancelState || $isCancelled) {
            $errors[] = $this->module->l(
                'Order is already canceled.'
            );
        }

        if (!empty($errors)) {
            $debug_str = var_export($errors, true);
            DebugLog::msg("Cancel Payment Handle / Errors: $debug_str", 'error');
        }

        $user = $this->userService->getUser();

        $signature = $this->signaturesGenerator->generateCancelSignature($user, $order->reference);

        try {
            $debug_str = '';
            $debug_str .= property_exists($order, 'reference') ? '[Order Ref: ' . $order->reference . ']' : '[No order reference]';
            $debug_str .= method_exists($user, 'getKey') ? '[Key: ' . $user->getKey() . ']' : '[No user key]';
            $debug_str .= !empty($signature) ? '[Signature: ' . $signature . ']' : '[No signature]';
            DebugLog::msg("Cancel Payment Handle / Request: $debug_str", 'notice');
        } catch (\Exception $exception) {
            $er = $exception->getMessage();
            $debug_str = var_export($er, true);
            DebugLog::msg("Cancel Payment Handle / Request errors: $debug_str", 'error');
        }

        $cancelRequest = new CancelRequest(
            $order->reference,
            $user->getKey(),
            $signature
        );

        $response = $this->cancelService->cancelPayment($cancelRequest);
        $responseErrors = $response->getErrors();

        if (!empty($responseErrors)) {
            foreach ($responseErrors as $error) {
                $errors[] = $error->getError();
            }

            $debug_str = var_export($responseErrors, true);
            DebugLog::msg("Cancel Payment Handle / Response Errors: $debug_str", 'error');
        } else {
            $debug_str = $response->getStatusCode();
            DebugLog::msg("Cancel Payment Handle / Response Code: $debug_str", 'notice');
        }

        if (empty($errors)) {
            $order->setCurrentState($cancelState);
        }

        return new HandlerResponse(
            $order,
            $response->getStatusCode(),
            $errors,
            $this->module->l(
                'Order has been successfully canceled.'
            )
        );
    }
}