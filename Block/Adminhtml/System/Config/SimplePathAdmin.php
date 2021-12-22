<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace UniPAYPaymentGateway\Unipay\Block\Adminhtml\System\Config;

class SimplePathAdmin extends \Magento\Framework\View\Element\Template
{
    /**
     * @var SimplePath
     */
    private $simplePath;

    /**
     * SimplePathAdmin constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }
}
