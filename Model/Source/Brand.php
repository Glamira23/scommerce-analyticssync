<?php
/**
 * Scommerce AnalyticsSync Brand source model
 *
 * @category   Scommerce
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\AnalyticsSync\Model\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Catalog\Model\Product;
use Scommerce\AnalyticsSync\Helper\Data;

/**
 * Class Brand source model
 */
class Brand extends AbstractSource
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Product
     */
    protected $product;

    /**
     * Brand constructor.
     * @param Data $helper
     * @param Product $product
     */
    public function __construct(
        Data $helper,
        Product $product
    ) {
        $this->helper = $helper;
        $this->product = $product;
    }

    /**
     * @return array
     */
    public function getAllOptions()
    {
        $attributes = $this->product->getAttributes();
        $attributeArray[] = ['label' => __('Please select'), 'value' => ''];
        foreach ($attributes as $attribute) {
            $attributeArray[] = [
                'label' => $attribute->getName(),
                'value' => $attribute->getName()
            ];
        }

        return $attributeArray;
    }
}
