### Hi there 👋
Тестовое выполнено модулем Тестовый модуль случайной скидки
при установке выполняется:
1.копирование компонента 
2.создание правила работы с корзиной /bitrix/admin/sale_discount.php?lang=ru
3.события до добавления и до изменения правила
4.таблица samsonpost_discount_list для хранения значения скидки
5.события меняют APPLICATION в b_sale_discount
6.вызов компонента 

<?php
$APPLICATION->IncludeComponent(
    "samsonpost.discount:generate.coupon",
    ""
);?>

решать задачу было интересно, исполнять куда как скучнее.от того в модуле нет полноценного логирования ошибок и тестовых данных.
по срокам , дело в том что страница модулей грузится 5 минут, те установка удаление модуля ~10минут что добило интерес к написанию.
по этой же причине отсутсвуют тестовые пользователи и предустановленные купоны
