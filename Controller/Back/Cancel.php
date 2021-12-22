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

class Cancel extends Action
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
     * Cancel constructor.
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
        try {
            $this->paymentMethod->restoreCart();
            $this->logger->info('Payment has been cancelled.');
            $this->messageManager->addError(__('Payment has been cancelled.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('checkout/cart');
            return $resultRedirect;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
