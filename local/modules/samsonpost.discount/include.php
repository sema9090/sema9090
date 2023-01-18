<?php
use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses(
    'samsonpost.discount',
    [
        'Samsonpost\Discount\DiscountTable' => 'lib/discount.php',
        'Samsonpost\Discount\DiscountHelper' => 'lib/discount_helper.php',
        'Samsonpost\Discount\DiscountCouponHelper' => 'lib/discount_coupon_helper.php',
    ]
);