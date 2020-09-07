<?php

namespace Momocode\ShopwareBase\Tests\Functional\Helper;

use Shopware\Models\Shop\Repository;
use Shopware\Models\Shop\Shop;

/**
 * @author Moritz Müller <moritz@momocode.de>
 */
class LoginHelper
{
    /**
     * Login as a frontend user
     */
    public function loginFrontendUser()
    {
        Shopware()->Front()->setRequest(new \Enlight_Controller_Request_RequestHttp());
        $user = Shopware()->Db()->fetchRow(
            'SELECT id, email, password, subshopID, language FROM s_user WHERE id = 1'
        );

        /** @var Repository $repository */
        $repository = Shopware()->Models()->getRepository(Shop::class);
        $shop = $repository->getActiveById($user['language']);

        if (Shopware()->Container()->has('shopware.components.shop_registration_service')) {
            Shopware()->Container()->get('shopware.components.shop_registration_service')->registerShop($shop);
        } else {
            $shop->registerResources();
        }

        Shopware()->Session()->Admin = true;
        Shopware()->System()->_POST = [
            'email' => $user['email'],
            'passwordMD5' => $user['password'],
        ];
        Shopware()->Modules()->Admin()->sLogin(true);
    }
}
