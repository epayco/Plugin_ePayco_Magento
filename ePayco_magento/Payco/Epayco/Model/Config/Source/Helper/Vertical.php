<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Payco\Epayco\Model\Config\Source\Helper;

/**
 * Order Status source model
 */

class Vertical implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'false', 'label' => __('Onpage Checkout')],
            ['value' => 'true', 'label' => __('Standart Checkout')],
            
        ];
    }
}
