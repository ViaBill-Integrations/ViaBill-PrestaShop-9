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

use Context;
use Order;
use ViaBill\Factory\LoggerFactory;
use ViaBill\Object\Handler\HandlerResponse;

/**
 * Class PaymentManagementHandler
 */
class PaymentManagementHandler
{
    /**
     * @var CancelPaymentHandler
     */
    private $cancelPaymentHandler;

    /**
     * @var CapturePaymentHandler
     */
    private $capturePaymentHandler;

    /**
     * @var RefundPaymentHandler
     */
    private $refundPaymentHandler;

    /**
     * @var RenewPaymentHandler
     */
    private $renewPaymentHandler;

    /**
     * @var \ViaBill
     */
    private $module;

    /**
     * @var LoggerFactory
     */
    private $loggerFactory;

    public function __construct(
        \ViaBill $module,
        LoggerFactory $loggerFactory,
        CancelPaymentHandler $cancelPaymentHandler,
        CapturePaymentHandler $capturePaymentHandler,
        RefundPaymentHandler $refundPaymentHandler,
        RenewPaymentHandler $renewPaymentHandler
    ) {
        $this->cancelPaymentHandler = $cancelPaymentHandler;
        $this->capturePaymentHandler = $capturePaymentHandler;
        $this->refundPaymentHandler = $refundPaymentHandler;
        $this->renewPaymentHandler = $renewPaymentHandler;
        $this->module = $module;
        $this->loggerFactory = $loggerFactory;
    }

    /**
     * Handles Payment Management.
     *
     * @param Order $order
     * @param bool $isCancel
     * @param bool $isCapture
     * @param bool $isRefund
     * @param bool $isRenew
     * @param float $amount
     *
     * @return HandlerResponse
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function handle(
        Order $order,
        $isCancel,
        $isCapture,
        $isRefund,
        $isRenew,
        $amount
    ) {
        $existingOrder = \ViaBillOrder::getPrimaryKey($order->id);
        $isOrderExists = $order->payment === $this->module->name && $existingOrder;

        if (!$isOrderExists) {
            $errorMessage = sprintf(
                $this->module->l(
                    'Order %s is not related to the ViaBill system.'
                ),
                $order->reference
            );

            return new HandlerResponse(
                $order,
                404,
                [$errorMessage]
            );
        }

        if ($isCancel) {
            return $this->cancelPaymentHandler->handle($order);
        }

        if ($isCapture) {
            return $this->capturePaymentHandler->handle($order, $amount);
        }

        if ($isRefund) {
            return $this->refundPaymentHandler->handle($order, $amount);
        }

        if ($isRenew) {
            return $this->renewPaymentHandler->handle($order);
        }

        return new HandlerResponse($order, 200);
    }

    /**
     * Handles Multiple Payment Management.
     *
     * @param Context $context
     * @param bool $isCancel
     * @param bool $isRefund
     * @param bool $isCapture
     * @param int[]|bool $orderIds
     */
    public function handleMultiple(
        Context $context,
        $isCancel,
        $isRefund,
        $isCapture,
        $orderIds
    ) {
        if (empty($orderIds)) {
            $context->controller->warnings[] = sprintf(
                $this->module->l(
                    'At least one %s order must be selected in order to proceed.'
                ),
                $this->module->displayName
            );

            return;
        }

        $orders = $this->getOrders($orderIds);
        $logger = $this->loggerFactory->create();
        $allErrors = [];
        $allWarnings = [];
        $operationName = $this->getOperationName($isCancel, $isRefund, $isCapture);

        foreach ($orders as $order) {
            $amount = $order->total_paid_tax_incl;

            if ($isRefund) {
                $idCapture = \ViaBillOrderCapture::getPrimaryKey($order->id);
                $idRefund = \ViaBillOrderRefund::getPrimaryKey($order->id);

                $capture = new \ViaBillOrderCapture($idCapture);
                $refund = new \ViaBillOrderRefund($idRefund);

                $totalCaptured = $capture->getTotalCaptured();
                $totalRefunded = $refund->getTotalRefunded();
                $refundRemaining = $totalCaptured - $totalRefunded;

                $amount = $refundRemaining;
            }

            $handlerResult = $this->handle($order, $isCancel, $isCapture, $isRefund, false, $amount);
            $errors = $handlerResult->getErrors();
            $warnings = $handlerResult->getWarnings();

            if (!empty($errors)) {
                $errorMessage = sprintf(
                    $this->module->l(
                        '%s operation failed for order %s.'
                    ),
                    $operationName,
                    $order->reference
                );

                $allErrors[] = $errorMessage;
                $logger->error(
                    $errorMessage,
                    [
                        'errors' => $errors,
                    ]
                );
            }

            if (!empty($warnings)) {
                $warningMessage = sprintf(
                    $this->module->l(
                        '%s operation produced warnings for order %s.'
                    ),
                    $operationName,
                    $order->reference
                );

                $allWarnings[] = $warningMessage;
                $logger->warning(
                    $warningMessage,
                    [
                        'warning' => $warnings,
                    ]
                );
            }
        }

        if (empty($allWarnings) && empty($allErrors)) {
            $context->controller->confirmations[] = sprintf(
                $this->module->l(
                    '%s operation completed successfully.'
                ),
                $operationName
            );

            return;
        }

        $context->controller->errors = $allErrors;
        $context->controller->warnings = $allWarnings;
    }

    /**
     * Gets Order Objects Array.
     *
     * @param array $orderIds
     *
     * @return array
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function getOrders(array $orderIds)
    {
        $result = [];

        foreach ($orderIds as $id) {
            $order = new Order($id);

            if (!\Validate::isLoadedObject($order)) {
                continue;
            }

            $result[] = $order;
        }

        return $result;
    }

    /**
     * Gets Order Operation Name.
     *
     * @param bool $isCancel
     * @param bool $isRefund
     * @param bool $isCapture
     *
     * @return string
     */
    private function getOperationName($isCancel, $isRefund, $isCapture)
    {
        if ($isCancel) {
            return $this->module->l(
                'Cancel'
            );
        }

        if ($isRefund) {
            return $this->module->l(
                'Refund'
            );
        }

        if ($isCapture) {
            return $this->module->l(
                'Capture'
            );
        }

        return '';
    }
}