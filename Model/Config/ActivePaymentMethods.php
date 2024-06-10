<?php

namespace Scommerce\AnalyticsSync\Model\Config;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Payment\Model\Config;

class ActivePaymentMethods implements OptionSourceInterface
{
    /**
     * @var Config
     */
    protected $paymentMethods;

    public function __construct (Config $paymentMethods)
    {
        $this->paymentMethods = $paymentMethods;
    }

    public function toOptionArray()
    {
        $payments = $this->paymentMethods->getActiveMethods();
        $options = [[
            'label' => ' ',
            'value' => '_'
        ]];
        foreach ($payments as $paymentCode => $paymentModel) {
            $options[] = [
                'label' => $paymentModel->getTitle(),
                'value' => $paymentCode
            ];
        }

        return $options;
    }
}
