<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace UniPAYPaymentGateway\Unipay\Controller\Back;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use UniPAYPaymentGateway\Unipay\Model\PaymentMethod;
use UniPAYPaymentGateway\Unipay\Logger\Logger;

class Ipn extends Action
{
    /**
     * @var PaymentMethod
     */
    private $paymentMethod;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Ipn constructor.
     * @param Context $context
     * @param PaymentMethod $paymentMethod
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        PaymentMethod $paymentMethod,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->paymentMethod = $paymentMethod;
        $this->logger = $logger;
    }

    /**
     * Dispatch request
     * @return ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Exception
     */
    public function execute()
    {
        $this->logger->info('Start handle call-back url');
        $params    = $this->getRequest()->getParams();
        $secretKey = $this->paymentMethod->getSecretKey();

        if(empty($params) || empty($params['Hash'])) {
            $this->logger->info('error call-back');
            $this->getResponse()->setBody('AUTH ERROR');
            return;
        }

        $hash               = $params['Hash'];
        $status             = $params['Status'];
        $reason             = $params['Reason'];
        $unipayOrderId      = $params['UnipayOrderID'];
        $merchantOrderId    = $params['MerchantOrderID'];
        $calculateHash      = hash('sha256',$unipayOrderId.'|'.$merchantOrderId.'|'.$status.'|'.$secretKey);

        $merchantOrderId = explode("-", $merchantOrderId)[1];


        if ($hash === $calculateHash) {

            $this->logger->info(
                sprintf('hash "%s" equal calculatedHas: "%s". orderId: "%s" ',
                    $hash, $calculateHash, $merchantOrderId));
            $order = $this->getOrderDetailByOrderId($merchantOrderId);
            $this->logger->info(sprintf(' Transaction status is "%s". orderId: "%s" ', $status, $merchantOrderId));
            $order->setState($this->getStatus($status));
            $order->setStatus($this->getStatus($status));
            $order->save();
            $this->logger->info(sprintf(' Change order status "%s". orderId: "%s" ', $status, $merchantOrderId));
        } else {
            $this->logger->info(
                sprintf('hash "%s" not equal calculatedHas: "%s". orderId: "%s" ',
                    $hash, $calculateHash, $merchantOrderId));
            $this->getResponse()->setBody('AUTH ERROR');
            return;
        }
    }

    /**
     * Get Order Status
     *
     * @param string $unipayStatus
     * @return string $status
     */
    private function getStatus(string $unipayStatus)
    {

        switch ($unipayStatus) {
            case 3:
                $returnStatus = $this->paymentMethod->getCompleteStatus();
                break;
            case 'PENDING':
                $returnStatus = $this->paymentMethod->getPendingStatus();
                break;
            case 1:
                $returnStatus = $this->paymentMethod->getPendingStatus();
                break;
            case 13:
                $returnStatus = 'closed';
                break;
            default:
                $returnStatus = 'canceled';
                break;
        }


        return $returnStatus;
    }

    /**
     * Get Order object
     *
     * @param integer $orderId Order id
     * @return \Magento\Sales\Model\Order
     */
    private function getOrderDetailByOrderId($orderId)
    {
        $order = $this->paymentMethod->orderFactory
            ->create()
            ->loadByIncrementId($orderId);
        if (!$order || !$order->getId()) {
            return null;
        }
        return $order;
    }
}
