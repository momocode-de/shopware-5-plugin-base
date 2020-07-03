<?php

namespace Momocode\ShopwareBase\Tests\Functional\Helper;

use Zend_Db_Adapter_Exception;

/**
 * @author Moritz Müller <moritz@momocode.de>
 */
class ConfigHelper
{
    /**
     * @param $name
     * @param $value
     *
     * @throws Zend_Db_Adapter_Exception
     */
    public static function saveConfig($name, $value)
    {
        $formattedValue = sprintf('s:%d:"%s";', strlen($value), $value);
        Shopware()->Db()->query(
            'UPDATE s_core_config_elements SET value = ? WHERE name = ?',
            [$formattedValue, $name]
        );
        Shopware()->Container()->get('cache')->clean();
    }
}
