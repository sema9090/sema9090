<?php
use Bitrix\Main\EventManager;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data\DataManager;
use Samsonpost\Discount\DiscountHelper;

class samsonpost_discount extends CModule
{
    function __construct()
    {
        $arModuleVersion = [];

        include(dirname(__FILE__).'/version.php');
        $this->MODULE_ID = 'samsonpost.discount';
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = 'Тестовый модуль случайной скидки';
        $this->MODULE_DESCRIPTION = 'описание модуля';
        $this->PARTNER_NAME = '1223 456';
        $this->PARTNER_URI = 'https://t.me/not_object';
    }

    /**
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\SystemException
     * @throws \Bitrix\Main\ArgumentException
     */
    function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallEvents();
        if ( Loader::includeModule($this->MODULE_ID)) {
            $this->createEntity();
        }
        CopyDirFiles(__DIR__ . '/components', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/'.$this->MODULE_ID, true, true);
    }

    /**
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    function createEntity()
    {
        if (!Application::getConnection()->isTableExists(Base::getInstance('\Samsonpost\Discount\DiscountTable')->getDBTableName())) {
            Base::getInstance('\Samsonpost\Discount\DiscountTable')->createDBTable();
        }

        if ( Loader::includeModule('sale')) {
            DiscountHelper::addRuleRandomDiscount();
        }
    }
    function InstallEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->registerEventHandler(
            'sale',
            '\Bitrix\Sale\Internals\Discount::'.DataManager::EVENT_ON_BEFORE_ADD,
            $this->MODULE_ID,
            'Samsonpost\Discount\DiscountHelper',
            'beforeAdd'
        );
        $eventManager->registerEventHandler(
            'sale',
            '\Bitrix\Sale\Internals\Discount::'.DataManager::EVENT_ON_BEFORE_UPDATE,
            $this->MODULE_ID,
            'Samsonpost\Discount\DiscountHelper',
            'beforeAdd'
        );
    }

    function DoUninstall()
    {
        if ( Loader::includeModule($this->MODULE_ID)) {
            $this->deleteEntity();
        }
        DeleteDirFiles('/components', $_SERVER['DOCUMENT_ROOT'].'/bitrix/components/'.$this->MODULE_ID);
        $this->UnInstallEvents();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    /**
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\DB\SqlQueryException
     * @throws \Bitrix\Main\SystemException
     */
    function deleteEntity()
    {
        $connection = Application::getConnection();
        if (Application::getConnection()->isTableExists(Base::getInstance('\Samsonpost\Discount\DiscountTable')->getDBTableName())) {
            $connection->dropTable(Base::getInstance('\Samsonpost\Discount\DiscountTable')->getDBTableName());
        }
        if ( Loader::includeModule('sale')) {
            DiscountHelper::deleteRuleRandomDiscount();
        }
    }

    function UnInstallEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler(
            'sale',
            '\Bitrix\Sale\Internals\Discount::'.DataManager::EVENT_ON_BEFORE_ADD,
            $this->MODULE_ID,
            'Samsonpost\Discount\DiscountHelper',
            'beforeAdd'
        );
        $eventManager->unRegisterEventHandler(
            'sale',
            '\Bitrix\Sale\Internals\Discount::'.DataManager::EVENT_ON_BEFORE_UPDATE,
            $this->MODULE_ID,
            'Samsonpost\Discount\DiscountHelper',
            'beforeAdd'
        );
    }
}