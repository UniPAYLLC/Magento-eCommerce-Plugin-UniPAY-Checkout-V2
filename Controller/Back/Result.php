<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace UniPAYPaymentGateway\Unipay\Controller\Back;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use UniPAYPaymentGateway\Unipay\Model\PaymentMethod;
use UniPAYPaymentGateway\Unipay\Logger\Logger;

class Result extends Action
{

    /**
     * @var PaymentMethod
     */
    private $paymentMethod;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Result constructor.
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
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        try {
            $params = $this->getRequest()->getParams();
            $merchantOrderId = '';

            if (array_key_exists('order-id', $params) && !empty($params['order-id'])) {
                $merchantOrderId =  $params['order-id'];
            }

            $this->logger->info(sprintf(' Payment has been successful. orderId: "%s" ', $merchantOrderId));

            $url = $this->_url->getUrl('checkout/onepage/success');

            return $this->getResponse()->setRedirect($url);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
