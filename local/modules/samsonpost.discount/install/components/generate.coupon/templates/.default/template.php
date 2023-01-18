<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
?>
<form id='formAdd' enctype='multipart/form-data'>
    <button type='submit'>Получить скидку</button>
</form>
<div id="formAdd_result">

</div>
<form id='formCheck' enctype='multipart/form-data'>
    <input type='text' name='coupon' value=''>
    <button type='submit'>Отправить</button>
</form>
<div id="formCheck_result">

</div>