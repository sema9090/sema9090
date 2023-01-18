<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Sale\Internals\DiscountCouponTable;
use Bitrix\Sale\Internals\DiscountTable;
use Bitrix\Main\Type\DateTime;
use Samsonpost\Discount\DiscountHelper;
use Bitrix\Main\Loader;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Security\Random;
use Samsonpost\Discount\DiscountTable as SDiscountTable;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Error;

class addCouponComponent extends CBitrixComponent implements Controllerable
{
    public function onPrepareComponentParams($params)
    {
        return parent::onPrepareComponentParams($params);
    }

    public function actionPreFilters(): array
    {
        return [
            new HttpMethod([HttpMethod::METHOD_POST]),
            new Authentication(),
        ];
    }

    public function configureActions(): array
    {
        return [
            'checkCoupon' => [
                'prefilters' => array_merge([],$this->actionPreFilters()),
            ],
            'addCoupon' => [
                'prefilters' => array_merge([],$this->actionPreFilters()),
            ],

        ];
    }

    public function checkCouponAction()
    {
        Loader::includeModule('sale');
        Loader::includeModule('samsonpost.discount');
        $context = Application::getInstance()->getContext();
        $request = Context::getCurrent()->getRequest();
        $postList = $request->getPostList()->getValues();

        $errorCollection = new ErrorCollection();
        if( $postList['coupon'] ) {
            $date = new DateTime();
            $date2 = clone $date;
            $date2->add('-3 hours');

            try {
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
                        ->logic(ConditionTree::LOGIC_AND)
                        ->where('CREATE_DATE','>=',$date2)
                        ->where('CREATE_DATE','<',$date)
                        ->where('CODE',$postList['coupon'])
                        ->where('USER_ID',(int)CurrentUser::get()->getId())
                );
                $sDiscountQuery->setOrder(['ID'=>'DESC']);

                $sDiscountResult = $sDiscountQuery->fetch();

                if( $sDiscountResult['ID']) {
                    return AjaxJson::createSuccess([
                        'COUPON_CODE' => '',
                        'DISCOUNT_VAL' => $sDiscountResult['DISCOUNT_VALUE']
                    ]);
                }else{
                    $errorCollection->add([new Error('Скидка недоступна','NO_COUPON')]);
                    return AjaxJson::createError($errorCollection, []);
                }
            } catch (\Bitrix\Main\ArgumentException | \Bitrix\Main\SystemException $e) {
                //лог
            }

        }else{
            $errorCollection->add([new Error('введите купон','NO_COUPON')]);
            return AjaxJson::createError($errorCollection, []);
        }
    }

    public function addCouponAction()
    {
        $date = new DateTime();
        $date2 = clone $date;
        $date2->add('-1 hours');
        Loader::includeModule('samsonpost.discount');
        Loader::includeModule('sale');

        $errorCollection = new ErrorCollection();
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
            'CODE' => 'COUPON.COUPON'
        ]);

        $sDiscountQuery->where(
            Query::filter()
                ->logic(ConditionTree::LOGIC_AND)
                ->where('CREATE_DATE','>=',$date2)
                ->where('CREATE_DATE','<',$date)
        );
        $sDiscountQuery->setOrder(['ID'=>'DESC']);
        $sDiscountResult = $sDiscountQuery->fetch();
        if($sDiscountResult['ID']) {
            return AjaxJson::createSuccess([
                'COUPON_CODE' => $sDiscountResult['CODE'],
                'DISCOUNT_VAL' => $sDiscountResult['DISCOUNT_VALUE']
            ]);
        }else{
            $entityDiscount = DiscountTable::getEntity();
            /** @var \Bitrix\Main\ORM\Entity  $entityDiscount */
            $discountQuery = ($entityDiscount->getDataClass())::query();
            $discountQuery->setSelect([
                'ID',
            ]);

            $discountQuery->where('NAME',DiscountHelper::NAME);
            $result = $discountQuery->fetch();
            if( $result['ID'] ) {
                $date3 = clone $date;
                $date3->add('+3 hours');
                $coupon = DiscountCouponTable::generateCoupon(true);
                $addCoupon = ($discountCouponEntity->getDataClass())::add([
                    'DISCOUNT_ID' => $result['ID'],
                    'ACTIVE_FROM' => $date,
                    'DATE_CREATE' => $date,
                    'ACTIVE_TO' => $date3,
                    'COUPON' => $coupon,
                    'CREATED_BY' => (int)CurrentUser::get()->getId(),
                    'USER_ID' => (int)CurrentUser::get()->getId(),
                    'TYPE' => DiscountCouponTable::TYPE_ONE_ORDER
                ]);

                if( $id = $addCoupon->getId() ) {
                    $val = Random::getInt(SDiscountTable::DISCOUNT_VALUE_MIN,SDiscountTable::DISCOUNT_VALUE_MAX);
                    $discountVal = SDiscountTable::add([
                        'USER_ID' => (int)CurrentUser::get()->getId(),
                        'CREATE_DATE' => $date,
                        'DISCOUNT_VALUE' => $val,
                        'COUPON_ID' => $id,
                    ]);
                    if($discountVal->getId()){
                        return AjaxJson::createSuccess([
                            'COUPON_CODE' => $coupon,
                            'DISCOUNT_VAL' => $val
                        ]);
                    }
                }

            }
        }
        $errorCollection->add([new Error('техошибка','TECH')]);
        return AjaxJson::createError($errorCollection, []);
    }
    public function executeComponent()
    {
        $this->includeComponentTemplate();
    }
}