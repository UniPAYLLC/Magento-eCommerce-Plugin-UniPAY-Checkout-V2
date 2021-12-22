<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace UniPAYPaymentGateway\Unipay\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field as BaseField;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\UrlInterface;

class IpnUrl extends BaseField
{
    /**
     * Render element value
     *
     * @param                                         \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return                                        string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function _renderValue(AbstractElement $element)
    {
        $stores = $this->_storeManager->getStores();
        $valueReturn = '';
        $urlArray = [];

        foreach ($stores as $store) {
            $baseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_WEB);
            if ($baseUrl) {
                $value      = $baseUrl . 'unipay/back/ipn';
                $urlArray[] = "<div>".$this->escapeHtml($value)."</div>";
            }
        }

        $urlArray = array_unique($urlArray);
        foreach ($urlArray as $uniqueUrl) {
            $valueReturn .= "<div>".$uniqueUrl."</div>";
        }

        return '<td class="value">' . $valueReturn . '</td>';
    }

    /**
     * Render element value
     *
     * @param                                         \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return                                        string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function _renderInheritCheckbox(AbstractElement $element)
    {
        return '<td class="use-default"></td>';
    }
}
