<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace UniPAYPaymentGateway\Unipay\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Checkout\Model\Session;

class PaymentMethod extends AbstractMethod
{
    /**
     * @var string
     */
    protected $_code = 'unipay';

    /**
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * @var bool
     */
    protected $_canUseInternal = false;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlBuilder;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    public $orderFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $baseLogger;

    /**
     * @var Session
     */
    public $checkoutSession;

    /**
     * @var \Magento\Framework\App\ResponseFactory
     */
    public $responseFactory;

    /**
     * @var string
     */
    const SESSION_ID_PREFIX = 'ORDER-';

    /**
     * PaymentMethod constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param Session $checkoutSession
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \UniPAYPaymentGateway\Unipay\Logger\Logger $log
     * @param \Magento\Framework\App\ResponseFactory $responseFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \UniPAYPaymentGateway\Unipay\Logger\Logger $log,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilder = $urlBuilder;
        $this->orderFactory = $orderFactory;
        $this->baseLogger = $log;
        $this->messageManager = $messageManager;
        $this->responseFactory = $responseFactory;
    }

    /**
     * @return $this|AbstractMethod
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validate()
    {
        parent::validate();

        $paymentInfo = $this->getInfoInstance();
        if ($paymentInfo instanceof OrderPayment) {
            $baseCurrencyCode = $paymentInfo->getOrder()->getBaseCurrencyCode();
        } else {
            $baseCurrencyCode = $paymentInfo->getQuote()->getBaseCurrencyCode();
        }

        if (!in_array($baseCurrencyCode, array_keys($this->getAllowedCurrencies()))) {
            throw new \RuntimeException(
                sprintf(
                    'The base currency "%s" it\'s not supported by payment method "%s".',
                    $baseCurrencyCode,
                    $this->getTitle()
                )
            );
        }

        return $this;
    }

    /**
     * Return allowed currencies list.
     *
     * @return array
     */
    private function getAllowedCurrencies()
    {
        return [
            'GEL' => 'Lari',
        ];
    }

    public function getUniPAYPaymentGatewayUrl()
    {

        /** @var Order $order */
        $order = $this->getOrder();
        $url = $this->redirectUrl($order);

        return $url;
    }

    /**
     * Get redirect url.
     * Save transaction id in order-grid.
     *
     * @param $order
     * @return mixed
     */
    private function redirectUrl($order)
    {

        $responseItem = $this->item($order);
        $reponseCurl  = $this->curl($responseItem);

        $this->baseLogger->info(sprintf('Transaction: "%s"', $reponseCurl->data->UnipayOrderHashID));
        $order->setUnipayTransaction($reponseCurl->data->UnipayOrderHashID);
        $order->save();

        $this->baseLogger->info('return redirect url successfully');

        return $reponseCurl->data->Checkout;
    }

    /**
     * Get hashed item.
     *
     * @param $order
     * @return array
     */
    private function item($order)
    {
        $items      = $order->getAllItems();
        $orderTotal = $order->getGrandTotal();
        $orderId    = $order->getIncrementId();
//        $customerId = $order->getCustomerId();
        $customerEmail = $order->getCustomerEmail();

        $this->baseLogger->info(sprintf('Start item process, orderId: "%s" ', $orderId));

        $itemsArray = $this->parseItems($items, $order);
        $SuccessBackLink = $this->getSuccessBackLink($orderId);
        $CancelBackLink = $this->getCancelBackLink($orderId);

        $arr = [
            "MerchantID"                => $this->getMerchantId(),
            "MerchantUser"              => $customerEmail,
            "MerchantOrderID"           => uniqid('UN') . '-' . $orderId,
            "OrderPrice"                => $orderTotal,
            "OrderCurrency"             => 'GEL',
            "SuccessRedirectUrl"        => $SuccessBackLink,
            "CancelRedirectUrl"         => $CancelBackLink,
            "Mlogo"                     => base64_encode($this->getLogo()),
            "Mslogan"                   => substr(preg_filter('/[^\p{L}0-9\s]+/u','',$this->getSlogan()), 0, 70),
            "Language"                  => $this->getLocale(),
        ];

        $orderItems = [];

        if(count($itemsArray) === 1) {
            $arr['OrderName']        = $itemsArray[0]['productName'];
            $arr['OrderDescription'] = $itemsArray[0]['productDescription'];
        } else {
            $orderItems['Items'] = $this->parseItemsArray($itemsArray);
            $orderItems['Items'] = $this->parseItemsArray($itemsArray);
        }

        $hash = ['Hash'=> hash('sha256',$this->getSecretKey().'|'.implode('|', $arr))];
        $hash = !is_null($orderItems) ? array_merge($hash, $orderItems) : $hash;
        $result = array_merge($hash, $arr);

        $this->baseLogger->info(sprintf('End item process, orderId: "%s" ', $orderId));

        return [
            'password' => $hash['Hash'],
            'opts'     => json_encode($result)
        ];


    }

    /**
     * @param $itemsArray
     * @return array
     */
    private function parseItemsArray($itemsArray)
    {
        $items = array();

        foreach( $itemsArray as $item )
            $items[] = $item['price'].'|'.$item['quantity'].'|'.$item['productName'].'|'.$item['productDescription'];

        return $items;
    }

    /**
     * Parse items information.
     *
     * @param $items
     * @return array
     */
    private function parseItems($items, $order)
    {

        $this->baseLogger->info('Start item parse process');
        $itemsArray = [];

        foreach ($items as $item) {
            $data['Items'][] = (object) [
                'price' => $item['price'],
                'quantity' => $item['quantity'],
                'title' => str_replace('|', ' - ', str_replace('&', '', $item['name'])),
                'description' => str_replace('|', ' - ', str_replace('&', '', @$item['description'])),
            ];
        }

        //Shipment item
        $shipmentItem = $this->getShipmentItem($order);
        if(count($shipmentItem) > 0) $itemsArray[] = $shipmentItem;

        $this->baseLogger->info('End item parse process');

        return $itemsArray;
    }

    /**
     * @param $order
     * @return array
     */
    private function getShipmentItem($order)
    {
        $itemsArray = array();

        $shipmentCost = $order->getShippingAmount();
        $shipmentName = $order->getShippingDescription();

        if( isset($shipmentName) && $shipmentCost > 0 ) {
            $itemsArray = [
                'price'              => $shipmentCost * 100,
                'quantity'           => 1,
                'productName'        => 'Shipment',
                'productDescription' => $shipmentName
            ];
        }

        return $itemsArray;
    }


    /**
     * Get backlink. success urls.
     *
     * @param $orderId
     * @return string
     */
    private function getSuccessBackLink($orderId)
    {
        $successUrl = $this->urlBuilder->getUrl('unipay/back/result') . '?' . http_build_query([ 'order-id' => $orderId ]);
        return base64_encode($successUrl);
    }


    /**
     * Get backlink. cancel urls.
     *
     * @param $orderId
     * @return string
     */
    private function getCancelBackLink($orderId)
    {

        $cancelUrl  = $this->urlBuilder->getUrl('payment-failed');

        return base64_encode($cancelUrl);
    }

    /**
     * Curl process.
     *
     * @param array $params
     * @return mixed
     */
    private function curl(array $params)
    {

        $this->baseLogger->info('Start curl process');

        $merchantId = $this->getMerchantId();

        $curl = curl_init($this->getGatewayUrl());
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, "$merchantId:$params[password]"); //Your credentials goes here
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params['opts']);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($params['opts'])]);
        curl_setopt($curl, CURLOPT_HEADER, false);

        $curlResponse = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $response = json_decode($curlResponse);

        curl_close($curl);


        if ($status != 200){
            $this->showErrorAndRedirect(sprintf('Call to URL failed with status: "%s". Response: "%s" ',$status,$curlResponse));
        }

        if (empty($response)){
            $this->showErrorAndRedirect('UniPAY response is null, Some parameters is incorect');
        }

        if ($response->errorcode !== 0){
            $this->showErrorAndRedirect(sprintf('Payment Error: "%s" ', $response->message));
        }

        $this->baseLogger->info('End curl process successfully');

        return $response;
    }

    /**
     * @param $orderIncrementId
     * @return Order
     */
    private function loadOrder($orderIncrementId)
    {
        return $this->orderFactory->create()->loadByIncrementId($orderIncrementId);
    }

    /**
     * Get order.
     *
     * @return Order Object
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getOrder()
    {
        $orderId = $this->checkoutSession->getLastRealOrderId();
        $order = $this->loadOrder($orderId);

        if (!$order->getId()) {
            throw new \InvalidArgumentException(
                sprintf('Cannot handle notify. Order with increment ID "%s" is not found.', $orderId)
            );
        }
        if ($order->getPayment()->getMethod() !== $this->getCode()) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Cannot handle notify. Order "%s" is not paid with Unipay (method is "%s").',
                    $orderId,
                    $order->getPayment()->getMethod()
                )
            );
        }
        return $order;
    }

    /**
     * Get localization.
     *
     * @return string
     */
    private function getLocale()
    {

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $store = $objectManager->get('Magento\Framework\Locale\Resolver');
        $locale = $store->getLocale();
        $locale === 'ka_GE' ? $language = 'GE' : $language = 'EN';

        return $locale;
    }

    /**
     * Get UniPAY gateway Url.
     *
     * @return string
     */
    private function getGatewayUrl()
    {
        return 'https://apiv2.unipay.com/magento/checkout/v1/createorder';

    }

    /**
     * Get Merchant secret key.
     *
     * @return string
     */
    public function getSecretKey()
    {
        return $this->getConfigData('secret_key');
    }

    /**
     * Get Merchant merchant id.
     *
     * @return string
     */
    public function getMerchantId()
    {
        return $this->getConfigData('merchant_id');
    }

    /**
     * Get slogan text.
     *
     * @return string
     */
    public function getSlogan()
    {
        return $this->getConfigData('slogan');
    }

    /**
     * Get logo url.
     *
     * @return string
     */
    public function getLogo()
    {
        return $this->getConfigData('logo');
    }

    /**
     * Get selected Complete status.
     *
     * @return string
     */
    public function getCompleteStatus()
    {
        return $this->getConfigData('completed_order_status');
    }

    /**
     * Get selected Pending status.
     *
     * @return string
     */
    public function getPendingStatus()
    {
        return $this->getConfigData('pending_order_status');
    }

    /**
     * @param $showError
     */
    private function showErrorAndRedirect($showError)
    {
        $this->restoreCart();
        $this->messageManager->addError($showError);
        $this->baseLogger->info($showError);
        $cartUrl = $this->urlBuilder->getUrl('checkout/cart');
        $this->responseFactory->create()->setRedirect($cartUrl)->sendResponse();

        exit;

    }

    /**
     * Restore cart.
     *
     * @return void
     */
    public function restoreCart()
    {
        $session    = $this->checkoutSession;
        $order      = $this->getOrderDetailByOrderId($session->getLastRealOrderId());
        $objectManager  = \Magento\Framework\App\ObjectManager::getInstance();
        $quoteFactory   = $objectManager->create('\Magento\Quote\Model\QuoteFactory');

        if($order === null) return;

        $quote = $quoteFactory->create()->load($order->getQuoteId());
        $quote->setIsActive(1)->setReservedOrderId(null)->save();
        $session->replaceQuote($quote);
        $session->unsLastRealOrderId();
    }

    /**
     * Get Order object
     *
     * @param integer $orderId Order id
     * @return \Magento\Sales\Model\Order
     */
    public function getOrderDetailByOrderId($orderId)
    {
        $order = $this->orderFactory
            ->create()
            ->loadByIncrementId($orderId);
        if (!$order || !$order->getId()) {
            return null;
        }
        return $order;
    }
}
