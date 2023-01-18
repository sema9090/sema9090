<?php
namespace Samsonpost\Discount;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\EventResult;
use Bitrix\Main\Application;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CSaleDiscount;
use Bitrix\Main\GroupTable;
use Bitrix\Main\ORM;
use Bitrix\Sale\Internals\DiscountTable;
use Bitrix\Main\Engine\CurrentUser;
use Samsonpost\Discount\DiscountTable as SDiscountTable;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Sale\Internals\DiscountCouponTable;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Sale\DiscountCouponsManager;
/**
 * Class DiscountHelper
 * @package Samsonpost\Discount
 */
class DiscountHelper
{
    const NAME = 'Случайная скидка';
    const APPLICATION = 'function (&$arOrder){Bitrix\Main\Loader::includeModule(\'samsonpost.discount\');\Bitrix\Sale\Discount\Actions::applyToBasket($arOrder, array (\'VALUE\' => \Samsonpost\Discount\DiscountHelper::getUserDiscount(),\'UNIT\' => \'P\',\'LIMIT_VALUE\' => 0,), "");};';

    /**
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     */
    public function addRuleRandomDiscount()
    {
        $context = Application::getInstance()->getContext();
        try {
            $groups = GroupTable::getList([
                'select' => ['ID'],
                'order' => ['ID' => 'ASC']
            ])->fetchCollection();

            $arFields = [
                'LID' => \CSite::GetDefSite(),
                'NAME' => self::NAME,
                'ACTIVE' => 'Y',
                'CONDITIONS' => [
                    'CLASS_ID' => 'CondGroup',
                    'DATA' => [
                        'All' => 'AND',
                        'True' => True
                    ],
                    'CHILDREN' => []
                ],
                'ACTIONS' => [
                    'CLASS_ID' => 'CondGroup',
                    'DATA' => [
                        'All' => 'AND'
                    ],
                    'CHILDREN' => [
                        [
                            'CLASS_ID' => 'ActSaleBsktGrp',
                            'DATA' => [
                                'Type' => 'Discount',
                                'Value' => '10',
                                'Unit' => 'Perc',
                                'Max' => '0',
                                'All' => 'AND',
                                'True' => True
                            ],
                            'CHILDREN' => []
                        ]
                    ]
                ],
                'USER_GROUPS' => $groups->getIdList(),
            ];
            $discountID = (int)CSaleDiscount::Add($arFields);
        } catch (ObjectPropertyException | ArgumentException | SystemException $e) {
            //логируем ошибку
        }

        return $discountID;
    }
    public function deleteRuleRandomDiscount()
    {

        try {
            $entityDiscount = DiscountTable::getEntity();
            /** @var \Bitrix\Main\ORM\Entity  $entityDiscount */
            $discountQuery = ($entityDiscount->getDataClass())::query();
            $discountQuery->setSelect([
                'ID',
                'NAME'
            ]);

            $discountQuery->where('NAME',self::NAME);
            $result = $discountQuery->fetch();
            if( (int)$result['ID'] > 0 ) {
                $id = $result['ID'];
                DiscountTable::Delete($id);
            }
        } catch (ArgumentException | SystemException $e) {
            //логируем ошибку
        }

    }

    /**
     * @return float
     */
    public static function getUserDiscount()
    {
        $coupons = DiscountCouponsManager::get(true, [], false, true);
        $keys = array_keys($coupons);

        if( $keys[0] ) {
            $sDiscountEntity = SDiscountTable::getEntity();
            $discountCouponEntity = DiscountCouponTable::getEntity();
            $sDiscountEntity->addField(
                (
                new Reference(
                    'COUPON',
                    $discountCouponEntity,
                    Join::on('this.COUPON_ID', 'ref.ID')
                )
                )->configureJoinType(Join::TYPE_LEFT)
            );

            $sDiscountQuery = ($sDiscountEntity->getDataClass())::query();
            $sDiscountQuery->setSelect([
                'ID',
                'DISCOUNT_VALUE',
                'COUPON_ID',
                'USER_ID',
                'CODE' => 'COUPON.COUPON'
            ]);

            $sDiscountQuery->where(
                Query::filter()
                    ->where('CODE',$keys[0])
            );
            $sDiscountQuery->setOrder(['ID'=>'DESC']);

            $sDiscountResult = $sDiscountQuery->fetch();
            if( $sDiscountResult['DISCOUNT_VALUE'] ) {
                return '-'.number_format((float)$sDiscountResult['DISCOUNT_VALUE'], 1, '.', '');
            }
        }
        return 0;
    }

    /**
     * @throws SystemException
     * @throws ArgumentException
     */
    public static function beforeAdd(ORM\Event $event): ORM\EventResult
    {
        $fields = $event->getParameter('fields');
        $paramId = $event->getParameter('id');
        $result = new EventResult();

        if($paramId['ID'] > 0) {
            try {
                $entityDiscount = DiscountTable::getEntity();
                /** @var \Bitrix\Main\ORM\Entity  $entityDiscount */
                $discountQuery = ($entityDiscount->getDataClass())::query();
                $discountQuery->setSelect([
                    'ID',
                    'NAME'
                ]);
                $discountQuery->where('ID',$paramId['ID']);
                $discountResult = $discountQuery->fetch();
                //не меняем имя правилу кастомному
                if( $discountResult['NAME'] == self::NAME) {
                    $result->unsetField('NAME');
                }
            } catch (ArgumentException | ObjectPropertyException | SystemException $e) {
                //лог
            }
        }

        $result->modifyFields([
            'APPLICATION' => self::APPLICATION
        ]);

        return $result;
    }
}