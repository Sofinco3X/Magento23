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

namespace Sofinco\Epayment\Model;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use \SimpleXMLElement;
use Sofinco\Epayment\Model\Payment\AbstractPayment;
use Sofinco\Epayment\Helper\Utf8Data;

class Sofinco
{
    private $_currencyDecimals = [
        '008' => 2,
        '012' => 2,
        '032' => 2,
        '036' => 2,
        '044' => 2,
        '048' => 3,
        '050' => 2,
        '051' => 2,
        '052' => 2,
        '060' => 2,
        '064' => 2,
        '068' => 2,
        '072' => 2,
        '084' => 2,
        '090' => 2,
        '096' => 2,
        '104' => 2,
        '108' => 0,
        '116' => 2,
        '124' => 2,
        '132' => 2,
        '136' => 2,
        '144' => 2,
        '152' => 0,
        '156' => 2,
        '170' => 2,
        '174' => 0,
        '188' => 2,
        '191' => 2,
        '192' => 2,
        '203' => 2,
        '208' => 2,
        '214' => 2,
        '222' => 2,
        '230' => 2,
        '232' => 2,
        '238' => 2,
        '242' => 2,
        '262' => 0,
        '270' => 2,
        '292' => 2,
        '320' => 2,
        '324' => 0,
        '328' => 2,
        '332' => 2,
        '340' => 2,
        '344' => 2,
        '348' => 2,
        '352' => 0,
        '356' => 2,
        '360' => 2,
        '364' => 2,
        '368' => 3,
        '376' => 2,
        '388' => 2,
        '392' => 0,
        '398' => 2,
        '400' => 3,
        '404' => 2,
        '408' => 2,
        '410' => 0,
        '414' => 3,
        '417' => 2,
        '418' => 2,
        '422' => 2,
        '426' => 2,
        '428' => 2,
        '430' => 2,
        '434' => 3,
        '440' => 2,
        '446' => 2,
        '454' => 2,
        '458' => 2,
        '462' => 2,
        '478' => 2,
        '480' => 2,
        '484' => 2,
        '496' => 2,
        '498' => 2,
        '504' => 2,
        '504' => 2,
        '512' => 3,
        '516' => 2,
        '524' => 2,
        '532' => 2,
        '532' => 2,
        '533' => 2,
        '548' => 0,
        '554' => 2,
        '558' => 2,
        '566' => 2,
        '578' => 2,
        '586' => 2,
        '590' => 2,
        '598' => 2,
        '600' => 0,
        '604' => 2,
        '608' => 2,
        '634' => 2,
        '643' => 2,
        '646' => 0,
        '654' => 2,
        '678' => 2,
        '682' => 2,
        '690' => 2,
        '694' => 2,
        '702' => 2,
        '704' => 0,
        '706' => 2,
        '710' => 2,
        '728' => 2,
        '748' => 2,
        '752' => 2,
        '756' => 2,
        '760' => 2,
        '764' => 2,
        '776' => 2,
        '780' => 2,
        '784' => 2,
        '788' => 3,
        '800' => 2,
        '807' => 2,
        '818' => 2,
        '826' => 2,
        '834' => 2,
        '840' => 2,
        '858' => 2,
        '860' => 2,
        '882' => 2,
        '886' => 2,
        '901' => 2,
        '931' => 2,
        '932' => 2,
        '934' => 2,
        '936' => 2,
        '937' => 2,
        '938' => 2,
        '940' => 0,
        '941' => 2,
        '943' => 2,
        '944' => 2,
        '946' => 2,
        '947' => 2,
        '948' => 2,
        '949' => 2,
        '950' => 0,
        '951' => 2,
        '952' => 0,
        '953' => 0,
        '967' => 2,
        '968' => 2,
        '969' => 2,
        '970' => 2,
        '971' => 2,
        '972' => 2,
        '973' => 2,
        '974' => 0,
        '975' => 2,
        '976' => 2,
        '977' => 2,
        '978' => 2,
        '979' => 2,
        '980' => 2,
        '981' => 2,
        '984' => 2,
        '985' => 2,
        '986' => 2,
        '990' => 0,
        '997' => 2,
        '998' => 2,
    ];
    private $_errorCode = [
        '00000' => 'Successful operation',
        '00001' => 'Payment system not available',
        '00003' => 'Paybor error',
        '00004' => 'Card number or invalid cryptogram',
        '00006' => 'Access denied or invalid identification',
        '00008' => 'Invalid validity date',
        '00009' => 'Subscription creation failed',
        '00010' => 'Unknown currency',
        '00011' => 'Invalid amount',
        '00015' => 'Payment already done',
        '00016' => 'Existing subscriber',
        '00021' => 'Unauthorized card',
        '00029' => 'Invalid card',
        '00030' => 'Timeout',
        '00033' => 'Unauthorized IP country',
        '00040' => 'No 3-D Secure',
    ];
    private $_resultMapping = [
        'M' => 'amount',
        'R' => 'reference',
        'T' => 'call',
        'A' => 'authorization',
        'B' => 'subscription',
        'C' => 'cardType',
        'D' => 'validity',
        'E' => 'error',
        'F' => '3ds',
        'G' => '3dsWarranty',
        'H' => 'imprint',
        'I' => 'ip',
        'J' => 'lastNumbers',
        'K' => 'sign',
        'N' => 'firstNumbers',
        'O' => '3dsInlistment',
        'o' => 'celetemType',
        'P' => 'paymentType',
        'Q' => 'time',
        'S' => 'transaction',
        'U' => 'subscriptionData',
        'W' => 'date',
        'Y' => 'country',
        'Z' => 'paymentIndex',
    ];
    protected $_objectManager = null;
    protected $_storeManager = null;
    protected $_urlInterface = null;
    protected $_helper = null;
    protected $_logger = null;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\UrlInterface $urlInterface,
        \Sofinco\Epayment\Helper\Data $helper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_objectManager = $objectManager;
        $this->_urlInterface = $urlInterface;
        $this->_helper = $helper;
        $this->_logger = $logger;

        $this->_storeManager = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface');
    }

    protected function _buildUrl($url)
    {
        $url = $this->_urlInterface->getUrl($url, ['_secure' => true]);
        $url = $this->_urlInterface->sessionUrlVar($url);
        return $url;
    }

    protected function _callDirect($type, $amount, Order $order, Transaction $transaction)
    {
        $config = $this->getConfig();

        $amountScale = $this->getCurrencyScale($order);
        $amount = round($amount * $amountScale);

        // Transaction information
        $callNumber = $transaction->getAdditionalInformation(AbstractPayment::CALL_NUMBER);
        $transNumber = $transaction->getAdditionalInformation(AbstractPayment::TRANSACTION_NUMBER);

        $version = '00103';
        $password = $config->getPassword();
        if ($config->getSubscription() == 'plus') {
            $version = '00104';
            $password = $config->getPasswordplus();
        }

        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $fields = [
            'ACTIVITE' => '024',
            'VERSION' => $version,
            'CLE' => $password,
            'DATEQ' => $now->format('dmYHis'),
            'DEVISE' => sprintf('%03d', $this->getCurrency($order)),
            'IDENTIFIANT' => $config->getIdentifier(),
            'MONTANT' => sprintf('%010d', $amount),
            'NUMAPPEL' => sprintf('%010d', $callNumber),
            'NUMQUESTION' => sprintf('%010d', $now->format('U')),
            'NUMTRANS' => sprintf('%010d', $transNumber),
            'RANG' => sprintf('%02d', $config->getRank()),
            'REFERENCE' => $this->tokenizeOrder($order),
            'SITE' => sprintf('%07d', $config->getSite()),
            'TYPE' => sprintf('%05d', (int) $type),
        ];

        // Specific Paypal
        $details = $transaction->getAdditionalInformation(Transaction::RAW_DETAILS);
        switch ($details['cardType']) {
            case 'PAYPAL':
                $fields['ACQUEREUR'] = 'PAYPAL';
                break;
        }

        // Sort parameters
        ksort($fields);

        // Sign values
        $sign = $this->signValues($fields);

        // Hash HMAC
        $fields['HMAC'] = $sign;

        $urls = $config->getDirectUrls();
        $url = $this->checkUrls($urls);

        // Init client
        $client = new \GuzzleHttp\Client([
            'allow_redirects' => false,
            'headers' => [
                'User-Agent' => 'Magento Sofinco module',
            ],
            'timeout' => 5,
        ]);

        // Do call
        try {
            $response = $client->request('POST', $url, [
                'body' => http_build_query($fields),
            ]);

            // Process result
            $result = [];
            parse_str($response->getBody(), $result);
            return $result;
        } catch (\Exception $e) {
            // Here, there's a problem
            throw new \LogicException(__('Sofinco not available. Please try again later.'));
        }
    }

    public function buildSystemParams(Order $order, AbstractPayment $payment)
    {
        $config = $this->getConfig();

        // URLs
        $baseUrl = 'sfco/payment';
        $values = [
            'PBX_ANNULE' => $this->_buildUrl($baseUrl . '/cancel'),
            'PBX_EFFECTUE' => $this->_buildUrl($baseUrl . '/success'),
            'PBX_REFUSE' => $this->_buildUrl($baseUrl . '/failed'),
            'PBX_REPONDRE_A' => $this->_buildUrl($baseUrl . '/ipn'),
        ];

        // Merchant information
        $values['PBX_SITE'] = $config->getSite();
        $values['PBX_RANG'] = substr(sprintf('%02d', $config->getRank()), -2);
        $values['PBX_IDENTIFIANT'] = $config->getIdentifier();

        // Card information
        $cards = $payment->getCards();
        if ($payment->getHasCctypes()) {
            $code = $order->getPayment()->getData('cc_type');
        } else {
            $code = array_keys($cards);
            $code = $code[0];
        }

        if (!isset($cards[$code])) {
            $message = 'No card with code %s.';
            throw new \LogicException(__($message, $code));
        }
        $card = $cards[$code];
        $values['PBX_TYPEPAIEMENT'] = $card['payment'];
        $values['PBX_TYPECARTE'] = $card['card'];

        // Order information
        $values['PBX_PORTEUR'] = $this->getBillingEmail($order);
        $values['PBX_DEVISE'] = $this->getCurrency($order);
        $values['PBX_CMD'] = $this->tokenizeOrder($order);

        // Customer information
        $values['PBX_CUSTOMER'] = $this->getCustomerInformation($order);

        // Billing information
        $values['PBX_BILLING'] = $this->getBillingInformation($order);
        $values['PBX_SHOPPINGCART'] = $this->getXmlShoppingCartInformation($order);

        // Amount
        $currencies = $this->_storeManager->getStore()->getAvailableCurrencyCodes();
        if (count($currencies) > 1 && $this->getConfig()->getCurrencyConfig() == 0) {
            $orderAmount = $order->getGrandTotal();
        } else {
            $orderAmount = $order->getBaseGrandTotal();
        }

        $amountScale = $this->_currencyDecimals[$values['PBX_DEVISE']];
        $amountScale = pow(10, $amountScale);
        if ($payment->getCode() == 'sfco_threetime') {
            $amounts = $this->computeThreetimePayments($orderAmount, $amountScale);
            foreach ($amounts as $k => $v) {
                $values[$k] = $v;
            }
        } else {
            $values['PBX_TOTAL'] = sprintf('%03d', round($orderAmount * $amountScale));
            switch ($payment->getSofincoAction()) {
                case AbstractPayment::PBXACTION_MANUAL:
                    $values['PBX_AUTOSEULE'] = 'O';
                    break;

                case AbstractPayment::PBXACTION_DEFERRED:
                    $delay = (int) $payment->getConfigData('delay');
                    if ($delay < 1) {
                        $delay = 1;
                    } elseif ($delay > 7) {
                        $delay = 7;
                    }
                        $values['PBX_DIFF'] = sprintf('%02d', $delay);
                    break;
            }
        }

        // 3-D Secure
        if (!$payment->is3DSEnabled($order)) {
            $values['PBX_3DS'] = 'N';
        }

        // Sofinco => Magento
        $values['PBX_RETOUR'] = 'M:M;R:R;T:T;A:A;B:B;C:C;D:D;E:E;F:F;G:G;H:H;I:I;J:J;N:N;O:O;P:P;Q:Q;S:S;W:W;Y:Y;K:K';
        $values['PBX_RUF1'] = 'POST';

        // Choose correct language
        $lang = $this->_objectManager->get('Magento\Framework\Locale\Resolver');
        if (!empty($lang)) {
            $lang = preg_replace('#_.*$#', '', $lang->getLocale());
        }
        $languages = $config->getLanguages();
        if (!array_key_exists($lang, $languages)) {
            $lang = 'default';
        }
        $lang = $languages[$lang];
        $values['PBX_LANGUE'] = $lang;

        // PayPal specific code
        /*
        if ($payment->getCode() == 'sfco_paypal') {
            $separator = '#';
            $address = $order->getBillingAddress();
            $customer = $this->_objectManager->get('Magento\Customer\Model\Customer')->load($order->getCustomerId());
            $data_Paypal = $this->cleanForPaypalData($this->getBillingName($order), 32);
            $data_Paypal .= $separator;
            $data_Paypal .= $this->cleanForPaypalData($address->getStreet(1), 100);
            $data_Paypal .= $separator;
            $data_Paypal .= $this->cleanForPaypalData($address->getStreet(2), 100);
            $data_Paypal .= $separator;
            $data_Paypal .= $this->cleanForPaypalData($address->getCity(), 40);
            $data_Paypal .= $separator;
            // $data_Paypal .= $this->cleanForPaypalData($address->getRegion(),40);
            $data_Paypal .= $separator;
            $data_Paypal .= $this->cleanForPaypalData($address->getPostcode(), 20);
            $data_Paypal .= $separator;
            $data_Paypal .= $this->cleanForPaypalData($address->getCountry(), 2);
            $data_Paypal .= $separator;
            $data_Paypal .= $this->cleanForPaypalData($address->getTelephone(), 20);
            $data_Paypal .= $separator;
            $items = $order->getAllVisibleItems();
            $products = [];
            foreach ($items as $item) {
                $products[] = $item->getName();
            }
            $data_Paypal .= $this->cleanForPaypalData(implode('-', $products), 127);
            $values['PBX_PAYPAL_DATA'] = $this->cleanForPaypalData($data_Paypal, 490);
        }
        */

        // Misc.
        $values['PBX_TIME'] = date('c');
        $values['PBX_HASH'] = strtoupper($config->getHmacAlgo());

        // Card specific workaround
        if (($card['payment'] == 'LEETCHI') && ($card['card'] == 'LEETCHI')) {
            $values['PBX_EFFECTUE'] .= '?R=' . urlencode($values['PBX_CMD']);
            $values['PBX_REFUSE'] .= '?R=' . urlencode($values['PBX_CMD']);
        } elseif (($card['payment'] == 'PREPAYEE') && ($card['card'] == 'IDEAL')) {
            $s = '?C=IDEAL&P=PREPAYEE';
            $values['PBX_ANNULE'] .= $s;
            $values['PBX_EFFECTUE'] .= $s;
            $values['PBX_REFUSE'] .= $s;
            $values['PBX_REPONDRE_A'] .= $s;
        }

        // PBX_VERSION
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        $moduleInfo = $this->_objectManager->get('Magento\Framework\Module\ModuleList')->getOne('Sofinco_Epayment');
        $values['PBX_VERSION'] = 'Magento_' . $productMetadata->getVersion() . '-' . 'sofinco' . '_' . $moduleInfo['setup_version'];

        // Sort parameters for simpler debug
        ksort($values);

        // Sign values
        $sign = $this->signValues($values);

        // Hash HMAC
        $values['PBX_HMAC'] = $sign;

        return $values;
    }

    public function cleanForPaypalData($string, $nbCaracter = 0)
    {
        $filter = new \Magento\Framework\Filter\RemoveAccents();
        if (is_array($string)) {
            $string = $string[0];
        }
        $string = trim(preg_replace("/[^-+#. a-zA-Z0-9]/", " ", $filter->filter($string)));
        if ($nbCaracter > 0) {
            $string = substr($string, 0, $nbCaracter);
        }
        return $string;
    }

    public function checkUrls(array $urls)
    {
        // Init client
        $client = new \GuzzleHttp\Client([
            'allow_redirects' => false,
            'headers' => [
                'User-Agent' => 'Magento Sofinco module',
            ],
            'timeout' => 5,
        ]);

        $error = null;
        foreach ($urls as $url) {
            $testUrl = preg_replace('#^([a-zA-Z0-9]+://[^/]+)(/.*)?$#', '\1/load.html', $url);

            try {
                $response = $client->request('GET', $testUrl);
                if ($response->getStatusCode() == 200) {
                    return $url;
                }
            } catch (\Exception $e) {
                $error = $e;
            }
        }

        // Here, there's a problem
        throw new \LogicException(__('Sofinco not available. Please try again later.'));
    }

    public function computeThreetimePayments($orderAmount, $amountScale)
    {
        $values = [];
        // Compute each payment amount
        $step = round($orderAmount * $amountScale / 3);
        $firstStep = ($orderAmount * $amountScale) - 2 * $step;
        $values['PBX_TOTAL'] = sprintf('%03d', $firstStep);
        $values['PBX_2MONT1'] = sprintf('%03d', $step);
        $values['PBX_2MONT2'] = sprintf('%03d', $step);

        // Payment dates
        $now = new \DateTime();
        $now->modify('1 month');
        $values['PBX_DATE1'] = $now->format('d/m/Y');
        $now->modify('1 month');
        $values['PBX_DATE2'] = $now->format('d/m/Y');


        // Force validity date of card
        $values['PBX_DATEVALMAX'] = $now->format('ym');
        return $values;
    }

    public function convertParams(array $params)
    {
        $result = [];
        foreach ($this->_resultMapping as $param => $key) {
            if (isset($params[$param])) {
                $result[$key] = Utf8Data::encode($params[$param]);
            }
        }

        return $result;
    }

    /**
     * Create transaction ID from Sofinco data
     */
    protected function createTransactionId(array $sofincoData)
    {
        $transaction = (int) (isset($sofincoData['transaction']) ? $sofincoData['transaction'] : $sofincoData['NUMTRANS']);
        $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
        return $transaction . '/' . $now->format('U');
    }

    public function directCapture($amount, Order $order, Transaction $transaction)
    {
        return $this->_callDirect(2, $amount, $order, $transaction);
    }

    public function directRefund($amount, Order $order, Transaction $transaction)
    {
        return $this->_callDirect(14, $amount, $order, $transaction);
    }

    public function getBillingEmail(Order $order)
    {
        return $order->getCustomerEmail();
    }

    public function getBillingName(Order $order)
    {
        return trim(preg_replace("/[^-. a-zA-Z0-9]/", " ", $this->_objectManager->get('Magento\Framework\Filter\RemoveAccents')->filter($order->getCustomerName())));
    }

    public function getCustomerInformation(Order $order)
    {
        if (!empty($order->getCustomerId())) {
            $id = $order->getCustomerId();
        } else {
            $id = 1;
        }
        $simpleXMLElement = new SimpleXMLElement("<Customer/>");
        $simpleXMLElement->addChild('Id', $id);
        return trim(substr($simpleXMLElement->asXML(), 21));
    }

    /**
     * Format a value to respect specific rules
     *
     * @param string $value
     * @param string $type
     * @param int $maxLength
     * @return string
     */
    protected function formatTextValue($value, $type, $maxLength = null)
    {
        /*
        AN : Alphanumerical without special characters
        ANP : Alphanumerical with spaces and special characters
        ANS : Alphanumerical with special characters
        N : Numerical only
        A : Alphabetic only
        */

        // Handle possible null values
        if (!is_string($value)) {
            $value = '';
        }

        switch ($type) {
            default:
            case 'AN':
                $value = $this->_objectManager->get('Magento\Framework\Filter\RemoveAccents')->filter($value);
                break;
            case 'ANP':
                $value = $this->_objectManager->get('Magento\Framework\Filter\RemoveAccents')->filter($value);
                $value = preg_replace('/[^-. a-zA-Z0-9]/', '', $value);
                break;
            case 'ANS':
                $value = $this->_objectManager->get('Magento\Framework\Filter\RemoveAccents')->filter($value);
                break;
            case 'N':
                $value = preg_replace('/[^0-9.]/', '', $value);
                break;
            case 'A':
                $value = $this->_objectManager->get('Magento\Framework\Filter\RemoveAccents')->filter($value);
                $value = preg_replace('/[^A-Za-z]/', '', $value);
                break;
        }
        // Remove carriage return characters, specials chars
        $value = trim(preg_replace("/\r|\n|&|<|>/", '', $value));

        // Cut the string when needed
        if (!empty($maxLength) && is_numeric($maxLength) && $maxLength > 0) {
            if (function_exists('mb_strlen')) {
                if (mb_strlen($value) > $maxLength) {
                    $value = mb_substr($value, 0, $maxLength);
                }
            } elseif (strlen($value) > $maxLength) {
                $value = substr($value, 0, $maxLength);
            }
        }

        return trim($value);
    }

    public function getBillingInformation(Order $order)
    {
        $address = $order->getBillingAddress();
        if ($order->getCustomerFirstname() != "") {
            $firstName = $this->removeAccents($order->getCustomerFirstname());
        } else {
            $firstName = $this->removeAccents($address->getFirstname());
        }
        if ($order->getCustomerLastname() != "") {
            $lastName = $this->removeAccents($order->getCustomerLastname());
        } else {
            $lastName = $this->removeAccents($address->getLastname());
        }
        $title = $order->getCustomerGender();
        if (empty($title)) {
            $title = "Mr";
        }
        $street = $address->getStreet();
        $address1 = $street;
        $address2 = '';
        if (is_array($street)) {
            if (!empty($street[0])) {
                $address1 = $street[0];
            }
            if (!empty($street[1])) {
                $address2 = $street[1];
            }
        }
        $address1 = str_replace(".", " ", $this->removeAccents($address1));
        $address2 = str_replace(".", " ", $this->removeAccents($address2));
        $zipCode = $address->getPostcode();
        $city = $this->removeAccents($address->getCity());
        $countryCode = $this->getCountryCode($address->getCountryId());
        $countryName = $this->removeAccents($address->getCountryId());
        $countryCodeHomePhone = $this->getCountryPhoneCode($address->getCountryId());
        $homePhone = substr($address->getTelephone(), -9);
        $countryCodeMobilePhone = $this->getCountryPhoneCode($address->getCountryId());
        $mobilePhone = substr($address->getTelephone(), -9);

        $simpleXMLElement = new SimpleXMLElement("<Billing/>");
        // $billingXML = $simpleXMLElement->addChild('Billing');
        $addressXML = $simpleXMLElement->addChild('Address');
        $addressXML->addChild('Title', $title);
        $addressXML->addChild('FirstName', $this->formatTextValue($firstName, 'ANS', 20));
        $addressXML->addChild('LastName', $this->formatTextValue($lastName, 'ANS', 20));
        $addressXML->addChild('Address1', $this->formatTextValue($address1, 'ANS', 50));
        $addressXML->addChild('Address2', $this->formatTextValue($address2, 'ANS', 50));
        $addressXML->addChild('ZipCode', $this->formatTextValue($zipCode, 'ANS', 16));
        $addressXML->addChild('City', $this->formatTextValue($city, 'ANS', 50));
        $addressXML->addChild('CountryCode', $countryCode);
        $addressXML->addChild('CountryName', $this->formatTextValue($countryName, 'ANS', 50));
        $addressXML->addChild('CountryCodeHomePhone', $countryCodeHomePhone);
        $addressXML->addChild('HomePhone', $homePhone);
        $addressXML->addChild('CountryCodeMobilePhone', $countryCodeMobilePhone);
        $addressXML->addChild('MobilePhone', $mobilePhone);

        return trim(substr($simpleXMLElement->asXML(), 21));
    }

    /**
     * Generate XML value for PBX_SHOPPINGCART parameter
     *
     * @param  Mage_Sales_Model_Order $order
     * @return string
     */
    public function getXmlShoppingCartInformation(Order $order)
    {
        $totalQuantity = 0;
        foreach ($order->getAllVisibleItems() as $item) {
            $totalQuantity += (int)$item->getQtyOrdered();
        }
        $totalQuantity = max(1, min($totalQuantity, 99));

        return sprintf('<?xml version="1.0" encoding="utf-8"?><shoppingcart><total><totalQuantity>%d</totalQuantity></total></shoppingcart>', $totalQuantity);
    }

    private function removeAccents($string)
    {
        $table = array(
            'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
            'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
            'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
            'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
            'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
            'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b',
            'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r',
        );

        // Remove carriage return characters, specials chars
        $string = trim(preg_replace("/\r|\n|&|<|>/", '', $string));

        return strtr($string, $table);
    }

    public function getCountryCode($countryCode)
    {
        $countryMapper = $this->_objectManager->get('Sofinco\Epayment\Model\IsoCountry');

        return $countryMapper->getIsoCode($countryCode);
    }

    public function getCountryPhoneCode($countryCode)
    {
        $countryMapper = $this->_objectManager->get('Sofinco\Epayment\Model\IsoCountry');

        return $countryMapper->getPhoneCode($countryCode);
    }

    /**
     * @return Sofinco\Epayment\Model\Config Sofinco configuration object
     */
    public function getConfig()
    {
        return $this->_objectManager->get('Sofinco\Epayment\Model\Config');
    }

    public function getCurrency(Order $order)
    {
        $currencyMapper = $this->_objectManager->get('Sofinco\Epayment\Model\Iso4217Currency');

        $currencies = $this->_storeManager->getStore()->getAvailableCurrencyCodes();
        if (count($currencies) > 1 && $this->getConfig()->getCurrencyConfig() == 0) {
            $currency = $order->getOrderCurrencyCode();
        } else {
            $currency = $order->getBaseCurrencyCode();
        }
        return $currencyMapper->getIsoCode($currency);
    }

    public function getCurrencyDecimals($cartOrOrder)
    {
        return $this->_currencyDecimals[$this->getCurrency($cartOrOrder)];
    }

    public function getCurrencyScale($cartOrOrder)
    {
        return pow(10, $this->getCurrencyDecimals($cartOrOrder));
    }

    public function getParams($logParams = false, $checkSign = true)
    {
        // Retrieves data
        $data = file_get_contents('php://input');
        if (empty($data)) {
            $data = $_SERVER['QUERY_STRING'];
        }
        if (empty($data)) {
            throw new \LogicException("Error Processing Request");
            (__('An unexpected error in Sofinco call has occured: no parameters.'));
        }

        // Log params if needed
        if ($logParams) {
            $this->logDebug(sprintf('Call params: %s', $data));
        }

        // Check signature if needed
        if ($checkSign) {
            // Extract signature
            $matches = [];
            if (!preg_match('#^(.*)&K=(.*)$#', $data, $matches)) {
                throw new \LogicException("Error Processing Request");
                (__('An unexpected error in Sofinco call has occured: missing signature.'));
            }

            // Check signature
            $signature = base64_decode(urldecode($matches[2]));
            $pubkey = file_get_contents(dirname(__FILE__) . '/../etc/pubkey.pem');
            $res = (boolean) openssl_verify($matches[1], $signature, $pubkey);

            if (!$res) {
                if (preg_match('#^C=IDEAL&P=PREPAYEE&(.*)&K=(.*)$#', $data, $matches)) {
                    $signature = base64_decode(urldecode($matches[2]));
                    $res = (boolean) openssl_verify($matches[1], $signature, $pubkey);
                }

                if (!$res) {
                    throw new \LogicException("Error Processing Request");
                    (__('An unexpected error in Sofinco call has occured: invalid signature.'));
                }
            }
        }

        $rawParams = [];
        parse_str($data, $rawParams);

        // Decrypt params
        $params = $this->convertParams($rawParams);
        if (empty($params)) {
            throw new \LogicException(__('An unexpected error in Sofinco call has occured.'));
        }

        return $params;
    }

    public function getSystemUrl()
    {
        $config = $this->getConfig();
        $urls = $config->getSystemUrls();
        if (empty($urls)) {
            $message = 'Missing URL for Sofinco system in configuration';
            throw new \LogicException(__($message));
        }

        $url = $this->checkUrls($urls);

        return $url;
    }

    public function getKwixoUrl()
    {
        $config = $this->getConfig();
        $urls = $config->getKwixoUrls();
        if (empty($urls)) {
            $message = 'Missing URL for Sofinco system in configuration';
            throw new \LogicException(__($message));
        }

        $url = $this->checkUrls($urls);

        return $url;
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

    public function signValues(array $values)
    {
        $config = $this->getConfig();

        // Serialize values
        $query = [];
        foreach ($values as $name => $value) {
            $query[] = $name . '=' . $value;
        }
        $query = implode('&', $query);

        // Prepare key
        $hmac = $config->getHmacKey();
        $key = pack('H*', $hmac);

        // Sign values
        $sign = hash_hmac($config->getHmacAlgo(), $query, $key);
        if ($sign === false) {
            $errorMsg = 'Unable to create hmac signature. Maybe a wrong configuration.';
            throw new \LogicException(__($errorMsg));
        }

        return strtoupper($sign);
    }

    public function toErrorMessage($code)
    {
        if (isset($this->_errorCode[$code])) {
            return $this->_errorCode[$code];
        }
        return 'Unknown error ' . $code;
    }

    public function tokenizeOrder(Order $order)
    {
        $reference = [];
        $reference[] = $order->getRealOrderId();
        $reference[] = $this->getBillingName($order);
        $reference = implode(' - ', $reference);
        return $reference;
    }

    /**
     * Load order from the $token
     *
     * @param  string $token Token (@see tokenizeOrder)
     * @return Mage_Sales_Model_Order
     */
    public function untokenizeOrder($token)
    {
        $parts = explode(' - ', $token, 2);
        if (count($parts) < 2) {
            $message = 'Invalid decrypted token "%s"';
            throw new \LogicException(__($message, $token));
        }

        // Retrieves order
        $order = $this->_objectManager->get('\Magento\Sales\Model\Order')->loadByIncrementId($parts[0]);
        if (empty($order)) {
            $message = 'Not existing order id from decrypted token "%s"';
            throw new \LogicException(__($message, $token));
        }
        if (is_null($order->getId())) {
            $message = 'Not existing order id from decrypted token "%s"';
            throw new \LogicException(__($message, $token));
        }

        $goodName = $this->getBillingName($order);
        if (($goodName != Utf8Data::decode($parts[1])) && ($goodName != $parts[1])) {
            $message = 'Consistency error on descrypted token "%s"';
            throw new \LogicException(__($message, $token));
        }

        return $order;
    }
}
