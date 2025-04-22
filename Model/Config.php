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
 * @version   1.0.11-hmac
 * @author    BM Services <contact@bm-services.com>
 * @copyright 2012-2017 Sofinco
 * @license   http://opensource.org/licenses/OSL-3.0
 * @link      http://www.paybox.com/
 */

namespace Sofinco\Epayment\Model;

class Config extends \Magento\Payment\Model\Config
{
    protected $_dataStorage;
    protected $_scopeConfig;
    protected $_objectManager;

    const SUBSCRIPTION_OFFER1 = 'essential';
    const SUBSCRIPTION_OFFER2 = 'flexible';
    const SUBSCRIPTION_OFFER3 = 'plus';

    private $_store;
    private $_configCache = [];
    private $_configMapping = [
        'allowedIps' => 'allowedips',
        'environment' => 'environment',
        'debug' => 'debug',
        'hmacAlgo' => 'merchant/hmacalgo',
        'hmacKey' => 'merchant/hmackey',
        'identifier' => 'merchant/identifier',
        'languages' => 'languages',
        'password' => 'merchant/password',
        'rank' => 'merchant/rank',
        'site' => 'merchant/site',
        'subscription' => 'merchant/subscription',
    ];
    private $_urls = [
        'system' => [
            'test' => [
                'https://preprod-tpeweb.paybox.com/php/'
            ],
            'production' => [
                'https://tpeweb1.paybox.com/php/',
                'https://tpeweb.paybox.com/php/',
            ],
        ],
        'direct' => [
            'test' => [
                'https://preprod-ppps.paybox.com/PPPS.php'
            ],
            'production' => [
                'https://ppps1.paybox.com/PPPS.php',
                'https://ppps.paybox.com/PPPS.php',
            ],
        ]
    ];

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Factory $paymentMethodFactory,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Framework\Config\DataInterface $dataStorage,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        parent::__construct($scopeConfig, $paymentMethodFactory, $localeResolver, $dataStorage, $date);
        $this->_dataStorage = $dataStorage;
        $this->_scopeConfig = $scopeConfig;
        $this->_objectManager = $objectManager;
    }

    public function __call($name, $args)
    {
        if (preg_match('#^get(.)(.*)$#', $name, $matches)) {
            $prop = strtolower($matches[1]) . $matches[2];
            if (isset($this->_configCache[$prop])) {
                return $this->_configCache[$prop];
            } elseif (isset($this->_configMapping[$prop])) {
                $key = 'sfco/' . $this->_configMapping[$prop];
                $value = $this->_getConfigValue($key);
                $this->_configCache[$prop] = $value;
                return $value;
            }
        } elseif (preg_match('#^is(.)(.*)$#', $name, $matches)) {
            $prop = strtolower($matches[1]) . $matches[2];
            if (isset($this->_configCache[$prop])) {
                return $this->_configCache[$prop] == 1;
            } elseif (isset($this->_configMapping[$prop])) {
                $key = 'sfco/' . $this->_configMapping[$prop];
                $value = $this->_getConfigValue($key);
                $this->_configCache[$prop] = $value;
                return $value == 1;
            }
        }
        throw new \LogicException('No function ' . $name);
    }

    public function getStore()
    {
        if (is_null($this->_store)) {
            $manager = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface');
            $this->_store = $manager->getStore();
        }
        return $this->_store;
    }

    private function _getConfigValue($name)
    {
        return $this->_scopeConfig->getValue($name, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    protected function _getUrls($type, $environment = null)
    {
        if (is_null($environment)) {
            $environment = $this->getEnvironment();
        }
        $environment = strtolower($environment);
        if (isset($this->_urls[$type][$environment])) {
            return $this->_urls[$type][$environment];
        }
        return [];
    }

    public function getEnvironment()
    {
        return $this->_getConfigValue('sfco/merchant/environment');
    }

    public function getSubscription()
    {
        return $this->_getConfigValue('sfco/merchant/subscription');
    }

    public function getHmacKey()
    {
        $value = $this->_getConfigValue('sfco/merchant/hmackey');
        return $value;
    }

    public function getPassword()
    {
        $value = $this->_getConfigValue('sfco/merchant/password');
        return $value;
    }

    public function getPasswordplus()
    {
        $value = $this->_getConfigValue('sfco/merchant/passwordplus');
        return $value;
    }

    public function getSystemUrls($environment = null)
    {
        return $this->_getUrls('system', $environment);
    }

    public function getDirectUrls($environment = null)
    {
        return $this->_getUrls('direct', $environment);
    }

    public function getDefaultNewOrderStatus()
    {
        return $this->_getConfigValue('sfco/defaultoption/new_order_status');
    }

    public function getDefaultCapturedStatus()
    {
        return $this->_getConfigValue('sfco/defaultoption/payment_captured_status');
    }

    public function getDefaultAuthorizedStatus()
    {
        return $this->_getConfigValue('sfco/defaultoption/payment_authorized_status');
    }

    public function getAutomaticInvoice()
    {
        $value = $this->_getConfigValue('sfco/automatic_invoice');
        if (is_null($value)) {
            $value = 0;
        }
        return (int) $value;
    }

    public function getShowInfoToCustomer()
    {
        $value = $this->_getConfigValue('sfco/info_to_customer');
        if (is_null($value)) {
            $value = 1;
        }
        return (int) $value;
    }

    public function getCurrencyConfig()
    {
        $value = $this->_getConfigValue('sfco/info/currency');
        if (is_null($value)) {
            $value = 1;
        }
        return (int) $value;
    }
}
