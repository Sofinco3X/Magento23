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
 * @version   1.0.7-psr
 * @author    BM Services <contact@bm-services.com>
 * @copyright 2012-2017 Sofinco
 * @license   http://opensource.org/licenses/OSL-3.0
 * @link      http://www.paybox.com/
 */

namespace Sofinco\Epayment\Block;

class Redirect extends \Magento\Framework\View\Element\Template
{
    protected $_objectManager;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }

    public function getFormFields()
    {
        $registry = $this->_objectManager->get('Magento\Framework\Registry');
        $current_order_id = $this->_objectManager->get('Magento\Checkout\Model\Session')->getCurrentSfcoOrderId();
        $order = $registry->registry('sfco/order_'.$current_order_id);
        $payment = $order->getPayment()->getMethodInstance();
        $cntr = $this->_objectManager->get('Sofinco\Epayment\Model\Sofinco');
        return $cntr->buildSystemParams($order, $payment);
    }

    public function getInputType()
    {
        $config = $this->_objectManager->get('Sofinco\Epayment\Model\Config');
        if ($config->isDebug()) {
            return 'text';
        }
        return 'hidden';
    }

    public function getSystemUrl()
    {
        $sofinco = $this->_objectManager->get('Sofinco\Epayment\Model\Sofinco');
        $urls = $sofinco->getConfig()->getSystemUrls();
        return $sofinco->checkUrls($urls);
    }
}
