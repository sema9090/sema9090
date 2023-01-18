<?php
namespace Samsonpost\Discount;

use Bitrix\Main\ORM;
use Bitrix\Main\Type\DateTime;

class DiscountTable extends ORM\Data\DataManager
{
    const DISCOUNT_VALUE_MIN = 1;
    const DISCOUNT_VALUE_MAX = 50;
    public static function getTableName(): string
    {
        return 'samsonpost_discount_list';
    }

    public static function getMap(): array
    {
        return [
            (new ORM\Fields\IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),
            (new ORM\Fields\IntegerField('USER_ID'))
                ->configureRequired(),
            (new ORM\Fields\IntegerField('DISCOUNT_VALUE'))
                ->addValidator(new ORM\Fields\Validators\RangeValidator(self::DISCOUNT_VALUE_MIN,self::DISCOUNT_VALUE_MAX))
                ->configureRequired(),
            (new ORM\Fields\IntegerField('COUPON_ID'))
                ->configureRequired(),
            (new ORM\Fields\DatetimeField('CREATE_DATE'))
                ->configureDefaultValue(new DateTime())
                ->configureRequired(),
        ];
    }

}