<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace UniPAYPaymentGateway\Unipay\Controller\Redirect;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Sales\Model\OrderFactory;
use UniPAYPaymentGateway\Unipay\Model\PaymentMethod;

class Redirect extends Action
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var PaymentMethod
     */
    private $paymentMethod;
    
    public function __construct(
        Context $context,
        Session $checkoutSession,
        OrderFactory $orderFactory,
        PaymentMethod $paymentMethod
    ) {
    
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $this->getResponse()->setRedirect(
            $this->paymentMethod->getUniPAYPaymentGatewayUrl()
        );
    }
}
