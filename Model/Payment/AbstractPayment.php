<?php
/**
 * Sofinco Epayment module for Magento
 *
 * Feel free to contact Sofinco at support@paybox.com for any
 * question.
 *
 * LICENSE: This source file is subject to the version 3.0 of the Open
 * Software License (OSL-3.0) that is available through the world-wide-web
 * at the following URI: http://opensource.org/licenses/OSL-3.0. If
 * you did not receive a copy of the OSL-3.0 license and are unable
 * to obtain it through the web, please send a note to
 * support@paybox.com so we can mail you a copy immediately.
 *
 * @version   1.0.8-meqp
 * @author    BM Services <contact@bm-services.com>
 * @copyright 2012-2017 Sofinco
 * @license   http://opensource.org/licenses/OSL-3.0
 * @link      http://www.paybox.com/
 */

namespace Sofinco\Epayment\Model\Payment;

use \Magento\Sales\Model\Order;
use \Magento\Sales\Model\Order\Invoice;
use \Magento\Sales\Model\Order\Payment\Transaction;
use \Magento\Framework\Validator\Exception;
use \Magento\Framework\DataObject;
use \Magento\Payment\Model\Method\AbstractMethod;
// use \Magento\Payment\Model\Method\Adapter;
use \Magento\Payment\Model\InfoInterface;
use \Magento\Framework\Event\ManagerInterface;
use \Magento\Payment\Gateway\Command\CommandPoolInterface;
use \Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use \Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use \Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Sofinco\Epayment\Helper\Utf8Data;

abstract class AbstractPayment extends AbstractMethod
{
    const CODE = 'sfco';

    protected $_code = self::CODE;

    const CALL_NUMBER = 'sofinco_call_number';
    const TRANSACTION_NUMBER = 'sofinco_transaction_number';
    const PBXACTION_DEFERRED = 'deferred';
    const PBXACTION_IMMEDIATE = 'immediate';
    const PBXACTION_MANUAL = 'manual';
    const PBXACTION_MODE_SHIPMENT = 'shipment';

    /**
     * Availability options
     */
    // basics
    protected $_isGateway = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_stripeApi = false;
    protected $_countryFactory;
    protected $_minAmount = null;
    protected $_maxAmount = null;
    protected $_supportedCurrencyCodes = ['USD', 'EUR'];
    protected $_debugReplacePrivateDataKeys = ['number', 'exp_month', 'exp_year', 'cvc'];
    // ! basics

    protected $_canAuthorize = true;
    protected $_canVoid = false;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_canSaveCc = false;
    protected $_canFetchTransactionInfo = false;
    // Fake to avoid calling au authorize ou capture before redirect
    protected $_isInitializeNeeded = true;
    protected $_formBlockType = 'Sofinco\Epayment\Block\Checkout\Payment';
    protected $_infoBlockType = 'Sofinco\Epayment\Block\Info';

    /**
     * Sofinco specific options
     */
    protected $_3dsAllowed = false;
    protected $_3dsMandatory = false;
    protected $_allowDeferredDebit = false;
    protected $_allowImmediatDebit = true;
    protected $_allowManualDebit = false;
    protected $_allowRefund = false;
    protected $_hasCctypes = false;
    protected $_processingTransaction = null;
    protected $_objectManager = null;
    protected $_logger = null;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
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

        $this->_scopeConfig = $scopeConfig;
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        // $this->_logger = $logger;

        $config = $this->getSofincoConfig();
        if ($config->getSubscription() == \Sofinco\Epayment\Model\Config::SUBSCRIPTION_OFFER2 || $config->getSubscription() == \Sofinco\Epayment\Model\Config::SUBSCRIPTION_OFFER3) {
            $this->_canRefund = $this->getAllowRefund();
            $this->_canCapturePartial = ($this->getSofincoAction() == self::PBXACTION_MANUAL);
            $this->_canRefundInvoicePartial = $this->_canRefund;
        } else {
            $this->_canRefund = false;
            $this->_canCapturePartial = false;
            $this->_canRefundInvoicePartial = false;
        }
        $this->_canCapture = true;

        $this->_minAmount = $this->getConfigData('min_order_total');
        $this->_maxAmount = $this->getConfigData('max_order_total');
    }

    /**
     *
     * @param Mage_Sales_Model_Order $order
     * @param string                 $type
     * @param array                  $data
     * @param type                   $closed
     * @param array                  $infos
     * @return Mage_Sales_Model_Order_Payment_Transaction
     */
    protected function _addSofincoTransaction(Order $order, $type, array $data, $closed, array $infos = [])
    {
        $withCapture = $this->getConfigPaymentAction() != AbstractMethod::ACTION_AUTHORIZE;

        $payment = $order->getPayment();

        $txnId = $this->_createTransactionId($data);
        if (empty($txnId)) {
            if (!empty($parent)) {
                $txnId = $parent->getAdditionalInformation(self::TRANSACTION_NUMBER);
            } else {
                throw new \LogicException('Invalid transaction id ' . $txnId);
            }
        }

        $payment->setTransactionId($txnId);
        $payment->setParentTransactionId(null);
        $transaction = $type;
        $transaction = $payment->addTransaction($transaction);
        $transaction->setAdditionalInformation(Transaction::RAW_DETAILS, $data);

        foreach ($infos as $key => $value) {
            $transaction->setAdditionalInformation($key, $value);
        }
        if (!empty($parent)) {
            $transaction->setParentTxnId($parent->getTxnId());
        }

        $transaction->setIsClosed($closed === true);

        $this->_processingTransaction = $transaction;

        return $transaction;
    }

    /**
     *
     * @param Mage_Sales_Model_Order $order
     * @param string                 $type
     * @param array                  $data
     * @param type                   $closed
     * @param array                  $infos
     * @return Mage_Sales_Model_Order_Payment_Transaction
     */
    protected function _addSofincoDirectTransaction(Order $order, $type, array $data, $closed, array $infos, Transaction $parent)
    {
        $withCapture = $this->getConfigPaymentAction() != AbstractMethod::ACTION_AUTHORIZE;

        $payment = $order->getPayment();
        $txnId = intval($parent->getAdditionalInformation(self::TRANSACTION_NUMBER));
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $txnId .= '/' . $now->format('dmYHis');
        $payment->setTransactionId($txnId);
        $payment->setParentTransactionId($parent->getTxnId());
        $transaction = $type;
        $transaction = $payment->addTransaction($transaction);
        $transaction->setAdditionalInformation(Transaction::RAW_DETAILS, $data);
        foreach ($infos as $key => $value) {
            $transaction->setAdditionalInformation($key, $value);
        }

        $transaction->setIsClosed($closed === true);

        $this->_processingTransaction = $transaction;

        return $transaction;
    }

    /**
     * Create transaction ID from sofinco data
     */
    protected function _createTransactionId(array $sofincoData)
    {
        $call = (int) (isset($sofincoData['transaction']) ? $sofincoData['transaction'] : $sofincoData['NUMTRANS']);
        return $call;
    }

    public function getSofincoTransaction(InfoInterface $payment, $type, $openedOnly = false)
    {
        $order = $payment->getOrder();

        // Find transaction
        $collection = $this->_objectManager->get('Magento\Sales\Model\Order\Payment\Transaction')->getCollection()
            ->setOrderFilter($order)
            ->addPaymentIdFilter($payment->getId())
            ->addTxnTypeFilter($type);

        if ($collection->getSize() == 0) {
            return null;
        }

        if ($openedOnly) {
            foreach ($collection as $item) {
                if ((!is_null($item)) && (!is_null($item->getTransactionId())) && (!$item->getIsClosed())) {
                    return $item;
                }
            }
            return null;
        }

        $item = $collection->getFirstItem();
        if (is_null($item) || is_null($item->getTransactionId())) {
            return null;
        }

        // Transaction found
        return $item;
    }

    /**
     * Assign corresponding data
     *
     * @param  \Magento\Framework\DataObject|mixed $data
     * @return $this
     * @throws LocalizedException
     */
    public function assignData(DataObject $data)
    {
        parent::assignData($data);
        if (!($data instanceof DataObject)) {
            $data = new DataObject($data);
        }

        $additionnalData = new DataObject($data->getAdditionalData());

        $info = $this->getInfoInstance();
        $info->setCcType($additionnalData->getCcType());
        return $this;
    }

    /**
     * Cancel payment abstract method
     *
     * @param Varien_Object $payment
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function cancel(InfoInterface $payment)
    {
        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $order = $payment->getOrder();
        $order->addStatusHistoryComment('Call to cancel()');
        $order->save();
        return;
    }

    /**
     * Capture payment
     *
     * @param Varien_Object $payment
     * @param float         $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function capture(InfoInterface $payment, $amount)
    {
        $order = $payment->getOrder();
        $this->logDebug(sprintf('Order %s: Capture for %f', $order->getIncrementId(), $amount));

        // Currently processing a transaction ? Use it.
        if (!is_null($this->_processingTransaction)) {
            $txn = $this->_processingTransaction;

            switch ($txn->getTxnType()) {
                // Already captured
                case Transaction::TYPE_CAPTURE:
                    $trxData = $txn->getAdditionalInformation(Transaction::RAW_DETAILS);
                    if (!is_array($trxData)) {
                        throw new \LogicException('No transaction found.');
                    }

                    $payment->setTransactionId($txn->getTransactionId());
                    // $payment->setSkipTransactionCreation(true);
                    $payment->setIsTransactionClosed(0);
                    return $this;

                case Transaction::TYPE_AUTH:
                    // Nothing to do
                    break;

                default:
                    throw new \LogicException('Unsupported transaction type ' . $txn->getTxnType());
            }
        } // Otherwise, find the good transaction
        else {
            // Find capture transaction
            $txn = $this->getSofincoTransaction($payment, Transaction::TYPE_CAPTURE);
            if (!is_null($txn)) {
                // Find Sofinco data
                $trxData = $txn->getAdditionalInformation(Transaction::RAW_DETAILS);
                if (!is_array($trxData)) {
                    throw new \LogicException('No transaction found.');
                }

                // Already captured
                $payment->setTransactionId($txn->getTransactionId());
                // $payment->setSkipTransactionCreation(true);
                $payment->setIsTransactionClosed(0);
                return $this;
            }

            // Find authorization transaction
            $txn = $this->getSofincoTransaction($payment, Transaction::TYPE_AUTH, true);
            if (is_null($txn)) {
                throw new \LogicException('Payment never authorized.');
            }
        }

        $this->logDebug(sprintf('Order %s: Capture - transaction %d', $order->getIncrementId(), $txn->getTransactionId()));

        // Call Sofinco Direct
        $sofinco = $this->getSofinco();
        $this->logDebug(sprintf('Order %s: Capture - calling directCapture with amount of %f', $order->getIncrementId(), $amount));
        $data = $sofinco->directCapture($amount, $order, $txn);
        $this->logDebug(sprintf('Order %s: Capture - response code %s', $order->getIncrementId(), $data['CODEREPONSE']));

        // Fix possible invalid utf-8 chars
        $data = array_map([Utf8Data::class, 'decode'], $data);

        // Message
        if ($data['CODEREPONSE'] == '00000') {
            $message = 'Payment was captured by Sofinco.';
            $close = true;
        } else {
            $message = 'Sofinco direct error (' . $data['CODEREPONSE'] . ': ' . $data['COMMENTAIRE'] . ')';
            $close = false;
        }
        $data['status'] = $message;
        $this->logDebug(sprintf('Order %s: Capture - %s', $order->getIncrementId(), $message));

        // Transaction
        $type = Transaction::TYPE_CAPTURE;
        $captureTxn = $this->_addSofincoDirectTransaction(
            $order,
            $type,
            $data,
            $close,
            [
            self::CALL_NUMBER => $data['NUMAPPEL'],
            self::TRANSACTION_NUMBER => $data['NUMTRANS'],
            ],
            $txn
        );
        $captureTxn->save();
        if ($close) {
            $captureTxn->close();
            $payment->setSfcoCapture(serialize($data));
        }

        // Avoid automatic transaction creation
        // $payment->setSkipTransactionCreation(true);
        $payment->setIsTransactionClosed(0);
        $payment->save();

        // If Sofinco returned an error, throw an exception
        if ($data['CODEREPONSE'] != '00000') {
            throw new \Exception($message);
        }

        // Change order state and create history entry
        $status = $this->getConfigPaidStatus();
        $state = Order::STATE_PROCESSING;
        $order->setState($state, $status, __($message));
        $order->setIsInProgress(true);
        $order->save();

        return $this;
    }

    /**
     * Checks parameter send by Sofinco to IPN.
     *
     * @param Mage_Sales_Model_Order $order  Order
     * @param array                  $params Parsed call parameters
     */
    public function checkIpnParams(Order $order, array $params)
    {
        // Check required parameters
        $requiredParams = ['amount', 'transaction', 'error', 'reference', 'sign'];
        foreach ($requiredParams as $requiredParam) {
            if (!isset($params[$requiredParam])) {
                $message = __('Missing ' . $requiredParam . ' parameter in Sofinco call');
                $this->logFatal(sprintf('Order %s: (IPN) %s', $order->getIncrementId(), $message));
                throw new \Exception($message);
            }
        }
    }

    public function getAllowDeferredDebit()
    {
        return $this->_allowDeferredDebit;
    }

    public function getAllowImmediatDebit()
    {
        return $this->_allowImmediatDebit;
    }

    public function getAllowManualDebit()
    {
        return $this->_allowManualDebit;
    }

    public function getAllowRefund()
    {
        return $this->_allowRefund;
    }

    public function getCards()
    {
        return $this->getConfigData('cards');
    }

    public function getConfigPaymentAction()
    {
        if ($this->getSofincoAction() == self::PBXACTION_MANUAL) {
            return AbstractMethod::ACTION_AUTHORIZE;
        }
        return AbstractMethod::ACTION_AUTHORIZE_CAPTURE;
    }

    public function getConfigAuthorizedStatus()
    {
        return $this->getConfigData('status/authorized');
    }

    public function getConfigPaidStatus()
    {
        return $this->getConfigData('status/paid');
    }

    public function getConfigAutoCaptureStatus()
    {
        return $this->getConfigData('status/auto_capture');
    }

    public function getConfigAutoCaptureMode()
    {
        return $this->getConfigData('status_mode');
    }

    public function getConfigAutoCaptureModeStatus()
    {
        return $this->getConfigData('status_auto_capture_mode');
    }

    public function getHasCctypes()
    {
        return $this->_hasCctypes;
    }

    public function getOrderPlaceRedirectUrl()
    {
        return $this->getUrl('sfco/payment/redirect', ['_secure' => true]);
        // To not send *invoice* email (invoice != order)
        // return false;
    }

    public function getSofincoAction()
    {
        $config = $this->getSofincoConfig();
        $action = $this->getConfigData('action');
        switch ($action) {
            case self::PBXACTION_DEFERRED:
                if (!$this->getAllowDeferredDebit()) {
                    return self::PBXACTION_IMMEDIATE;
                }
                break;
            case self::PBXACTION_IMMEDIATE:
                if (!$this->getAllowImmediatDebit()) {
                    // Not possible
                    throw new \LogicException('Unexpected condition in getSofincoAction');
                }
                break;
            case self::PBXACTION_MANUAL:
                if ((($config->getSubscription() != \Sofinco\Epayment\Model\Config::SUBSCRIPTION_OFFER2)
                && ($config->getSubscription() != \Sofinco\Epayment\Model\Config::SUBSCRIPTION_OFFER3))
                || !$this->getAllowManualDebit()
                    ) {
                    return self::PBXACTION_IMMEDIATE;
                }
                break;
            default:
                $action = self::PBXACTION_IMMEDIATE;
        }
        return $action;
    }

    /**
     * @return Sofinco\Epayment\Model\Config Sofinco configuration object
     */
    public function getSofincoConfig()
    {
        return $this->_objectManager->get('Sofinco\Epayment\Model\Config');
    }

    /**
     * @return Sofinco\Epayment\Model\Config Sofinco configuration object
     */
    public function getSofinco()
    {
        return $this->_objectManager->get('Sofinco\Epayment\Model\Sofinco');
    }

    /**
     * Check whether there are CC types set in configuration
     *
     * @param  Mage_Sales_Model_Quote|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (parent::isAvailable($quote)) {// This order total is between min and max amount configuration
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
            $minAmount = $this->_scopeConfig->getValue('sfco/merchant/min_amount', $storeScope);
            $maxAmount = $this->_scopeConfig->getValue('sfco/merchant/max_amount', $storeScope);
            $total = $quote->getGrandTotal();
            if (!($total >= $minAmount && $total <= $maxAmount)) {
                return false;
            }

            if ($this->getHasCctypes()) {
                $cctypes = $this->getConfigData('cctypes', ($quote ? $quote->getStoreId() : null));
                $cctypes = preg_replace('/NONE,?/', '', $cctypes);
                return !empty($cctypes);
            }
            return true;
        }
        return false;
    }

    /**
     * Check whether 3DS is enabled
     *
     * @param  Mage_Sales_Model_Order $order
     * @return boolean
     */
    public function is3DSEnabled(Order $order)
    {
        // If 3DS is mandatory, answer is simple
        if ($this->_3dsMandatory) {
            return true;
        }

        // If 3DS is not allowed, answer is simple
        if (!$this->_3dsAllowed) {
            return false;
        }

        // Otherwise lets see the configuration
        switch ($this->getConfigData('tds_active')) {
            case 'always':
                return true;
            case 'condition':
                // Minimum order total
                $value = $this->getConfigData('tds_min_order_total');
                if (!empty($value)) {
                    $total = round($order->getGrandTotal(), 2);
                    if ($total >= round($value, 2)) {
                        return true;
                    }
                }
                return false;
        }

        // Always off
        return false;
    }

    public function logDebug($message)
    {
        $this->_logger->debug($message);
    }

    public function logWarning($message)
    {
        $this->_logger->warning($message);
    }

    public function logError($message)
    {
        $this->_logger->error($message);
    }

    public function logFatal($message)
    {
        $this->_logger->critical($message);
    }

    public function makeCapture(Order $order)
    {
        $payment = $order->getPayment();
        $txn = $this->getSofincoTransaction($payment, Transaction::TYPE_AUTH, true);

        if (empty($txn)) {
            return false;
        }
        if ($txn->getIsClosed()) {
            return false;
        }
        if (!$order->canInvoice()) {
            return false;
        }

        $invoice = $order->prepareInvoice();
        $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
        $invoice->setTransactionId($txn->getTransactionId());
        $invoice->register();
        $invoice->pay();

        // $transactionSave = $this->_objectManager->get('Magento\Framework\Model\ResourceModel\Db\TransactionManager')
        //         ->addObject($invoice)
        //         ->addObject($order);
        // $transactionSave->save();

        return true;
    }

    /**
     * Refund specified amount for payment
     *
     * @param Varien_Object $payment
     * @param float         $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function refund(InfoInterface $payment, $amount)
    {
        $order = $payment->getOrder();

        // Find capture transaction
        $collection = $this->_objectManager->get('Magento\Sales\Model\Order\Payment\Transaction')->getCollection()
            ->setOrderFilter($order)
            ->addPaymentIdFilter($payment->getId())
            ->addTxnTypeFilter(Transaction::TYPE_CAPTURE);
        if ($collection->getSize() == 0) {
            // If none, error
            throw new \LogicException('No payment or capture transaction. Unable to refund.');
        }

        // Transaction found
        $txn = $collection->getFirstItem();

        // Transaction not captured
        if (!$txn->getIsClosed()) {
            throw new \LogicException('Payment was not fully captured. Unable to refund.');
        }

        // Call Sofinco Direct
        $connector = $this->getSofinco();
        $data = $connector->directRefund((float) $amount, $order, $txn);

        // Fix possible invalid utf-8 chars
        $data = array_map([Utf8Data::class, 'decode'], $data);

        // Message
        if ($data['CODEREPONSE'] == '00000') {
            $message = 'Payment was refund by Sofinco.';
        } else {
            $message = 'Sofinco direct error (' . $data['CODEREPONSE'] . ': ' . $data['COMMENTAIRE'] . ')';
        }
        $data['status'] = $message;

        // Transaction
        $transaction = $this->_addSofincoDirectTransaction($order, Transaction::TYPE_REFUND, $data, true, [], $txn);
        $transaction->save();

        // Avoid automatic transaction creation
        // $payment->setSkipTransactionCreation(true);
        $payment->setIsTransactionClosed(0);

        // If Sofinco returned an error, throw an exception
        if ($data['CODEREPONSE'] != '00000') {
            throw new \Exception($message);
        }

        // Add message to history
        $order->addStatusHistoryComment(__($message));

        return $this;
    }

    /**
     * Validate payment method information object
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function validate()
    {
        parent::validate();

        if ($this->getHasCctypes()) {
            $paymentInfo = $this->getInfoInstance();

            $cctype = $paymentInfo->getCcType();

            if (empty($cctype)) {
                $cctype = $paymentInfo->getAdditionalInformation('cc_type');
                // If the cc_type wasn't provided, we might be in the XHR request made after a new payment method
                // selection, which does not provide the field. We can continue, the field will be validated when
                // using the place order button.
                if (empty($cctype)) {
                    return $this;
                }
            }

            $selected = explode(',', $this->getConfigData('cctypes'));
            if (!in_array($cctype, $selected)) {
                $errorMsg = 'Please select a valid credit card type';
                throw new \LogicException(__($errorMsg));
            }
        }

        return $this;
    }

    /**
     * When the visitor come back from Sofinco using the cancel URL
     */
    public function onPaymentCanceled(Order $order)
    {
        // Cancel order
        $order->cancel();

        // Add a message
        $message = 'Payment was canceled by user on Sofinco payment page.';
        $message = __($message);
        $status = $order->addStatusHistoryComment($message);

        $this->logDebug(sprintf('Order %s: %s', $order->getIncrementId(), $message));

        $order->save();
    }

    /**
     * When the visitor come back from Sofinco using the failure URL
     */
    public function onPaymentFailed(Order $order)
    {
        // Message
        $message = 'Customer is back from Sofinco payment page.';
        $message = __($message);
        $status = $order->addStatusHistoryComment($message);

        $status->save();
    }

    /**
     * When the visitor is redirected to Sofinco
     */
    public function onPaymentRedirect(Order $order)
    {
        $info = $this->getInfoInstance();
        $info->setSfcoPaymentAction($this->getConfigPaymentAction());
        $info->setSfcoSofincoAction($this->getSofincoAction());
        $info->save();
        // Keep track of this redirection in order history
        $message = 'Redirecting customer to Sofinco payment page.';
        $status = $order->addStatusHistoryComment(__($message));

        $this->logDebug(sprintf('Order %s: %s', $order->getIncrementId(), $message));

        $status->save();
    }

    /**
     * When the visitor come back from Sofinco using the success URL
     */
    public function onPaymentSuccess(Order $order, array $data)
    {
        // Message
        $message = 'Customer is back from Sofinco payment page.';
        $message = __($message);
        $status = $order->addStatusHistoryComment($message);

        $status->save();
    }

    /**
     * When the IPN is called
     */
    public function onIPNCalled(Order $order, array $params)
    {
        try {
            // Check parameters
            $this->checkIpnParams($order, $params);

            // Look for transaction
            $txnId = $this->_createTransactionId($params);
            $txn = $this->_objectManager->get('Magento\Sales\Model\Order\Payment\Transaction\Repository');
            if ($txn->getByTransactionId($txnId, $order->getPayment()->getId(), $order->getId()) !== false) {
                return false;
            }

            // Payment success
            if (in_array($params['error'], ['00000', '00200', '00201', '00300', '00301', '00302', '00303'])) {
                $this->onIPNSuccess($order, $params);
            } // Payment refused
            else {
                $this->onIPNFailed($order, $params);
            }

            return true;
        } catch (\Exception $e) {
            $this->onIPNError($order, $params, $e);
            throw $e;
        }
    }

    /**
     * When an error has occured in the IPN handler
     *
     * 1.0.10 Fix incoherent Exception
     *
     * @version 1.0.10
     */
    public function onIPNError(Order $order, array $data, \Exception $e = null)
    {
        $withCapture = $this->getConfigPaymentAction() != AbstractMethod::ACTION_AUTHORIZE;

        // Message
        $message = 'An unexpected error have occured while processing Sofinco payment (%s).';
        $error = is_null($e) ? 'unknown error' : $e->getMessage();
        $error = __($error);
        $message = __($message, $error);
        $data['status'] = $message;
        $status = $order->addStatusHistoryComment($message);
        $status->save();
        $this->logFatal(sprintf('Order %s: (IPN) %s', $order->getIncrementId(), $message));

        // Transaction
        if (is_null($this->_processingTransaction)) {
            // $type = $withCapture ?
            //         Transaction::TYPE_CAPTURE :
            //         Transaction::TYPE_AUTH;
            $type = Transaction::TYPE_VOID;
            $this->_addSofincoTransaction($order, $type, $data, true);
        } else {
            $this->_processingTransaction->setAdditionalInformation(Transaction::RAW_DETAILS, $data);
        }

        $order->save();
    }

    /**
     * When the IPN is called to refuse a payment
     */
    public function onIPNFailed(Order $order, array $data)
    {
        $withCapture = $this->getConfigPaymentAction() != AbstractMethod::ACTION_AUTHORIZE;

        // Message
        $message = 'Payment was refused by Sofinco (%s).';
        $error = $this->getSofinco()->toErrorMessage($data['error']);
        $message = __($message, $error);
        $data['status'] = $message;
        $order->addStatusHistoryComment($message);
        $this->logDebug(sprintf('Order %s: (IPN) %s', $order->getIncrementId(), $message));

        // Transaction
        // $type = $withCapture ?
        //         Transaction::TYPE_CAPTURE :
        //         Transaction::TYPE_AUTH;
        $type = Transaction::TYPE_VOID;
        $this->_addSofincoTransaction($order, $type, $data, true);

        $order->save();
    }

    /**
     * When the IPN is called to validate a payment
     */
    public function onIPNSuccess(Order $order, array $data)
    {
        $this->logDebug(sprintf('Order %s: Standard IPN', $order->getIncrementId()));

        $payment = $order->getPayment();

        $withCapture = $this->getConfigPaymentAction() != AbstractMethod::ACTION_AUTHORIZE;

        // Message
        if ($withCapture) {
            $message = 'Payment was authorized and captured by Sofinco.';
            $status = $this->getConfigPaidStatus();
            $state = Order::STATE_PROCESSING;
            $allowedStates = [
                Order::STATE_NEW,
                Order::STATE_PENDING_PAYMENT,
                Order::STATE_PROCESSING,
            ];
        } else {
            $message = 'Payment was authorized by Sofinco.';
            $status = $this->getConfigAuthorizedStatus();
            $state = Order::STATE_PENDING_PAYMENT;
            $allowedStates = [
                Order::STATE_NEW,
                Order::STATE_PENDING_PAYMENT,
            ];
        }
        $data['status'] = $message;

        // Status and message
        $current = $order->getState();
        $message = __($message);

        // Create transaction
        $type = $withCapture ?
                Transaction::TYPE_CAPTURE :
                Transaction::TYPE_AUTH;
        $txn = $this->_addSofincoTransaction(
            $order,
            $type,
            $data,
            $withCapture,
            [
            self::CALL_NUMBER => $data['call'],
            self::TRANSACTION_NUMBER => $data['transaction'],
            ]
        );

        // Associate data to payment
        $payment->setSfcoAction($this->getSofincoAction());
        $payment->setSfcoDelay((int) $this->getConfigData('delay'));
        $payment->setSfcoAuthorization(serialize($data));
        if ($withCapture) {
            $payment->setSfcoCapture(serialize($data));
        }

        // Set status
        if (in_array($current, $allowedStates)) {
            $order->setState($state);
            $this->logDebug(sprintf('Order %s: Change status to %s', $order->getIncrementId(), $state));
        }
        $order->addStatusHistoryComment($message);
        $this->logDebug(sprintf('Order %s: %s', $order->getIncrementId(), $message));

        if ($withCapture) {
            $this->_createInvoice($payment, $order, $txn);
        }

        // Send email confirmation
        $emailSender = $this->_objectManager->create('\Magento\Sales\Model\Order\Email\Sender\OrderSender');
        $emailSender->send($order);
        $order->setIsCustomerNotified(true);

        $payment->save();
        $order->save();
    }

    /**
     *
     * @param Mage_Sales_Model_Order                     $order
     * @param Mage_Sales_Model_Order_Payment_Transaction $txn
     * @return Mage_Sales_Model_Order_Invoice
     */
    protected function _createInvoice($payment, $order, $txn)
    {
        $invoice = $order->prepareInvoice();
        $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
        $invoice->setTransactionId($txn->getTransactionId());
        $invoice->register();
        $invoice->pay();
        $invoice->save();

        if ($invoice && !$invoice->getEmailSent()) {
            $invoiceSender = $this->_objectManager->get('Magento\Sales\Model\Order\Email\Sender\InvoiceSender');
            $invoiceSender->send($invoice);

            $order->addRelatedObject($invoice);
            $order->addStatusHistoryComment(__('You notified customer about invoice #%1.', $invoice->getIncrementId()))
                ->setIsCustomerNotified(true);
        }

        return $invoice;
    }
}
