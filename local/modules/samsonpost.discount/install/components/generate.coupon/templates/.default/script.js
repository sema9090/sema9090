;(function ()
{
    'use strict';
    var Form = function ()
    {
        this.formAdd = document.getElementById('formAdd');
        this.formAdd_result = document.getElementById('formAdd_result');
        this.formCheck = document.getElementById('formCheck');
        this.formCheck_result = document.getElementById('formCheck_result');
        this.form_submit();
    }
    Form.prototype.form_submit = function() {
        let obj = this;
        this.formAdd.addEventListener('submit', function (e) {
            obj.formAdd_result.innerHTML = '';
            obj.submit(e,'addCoupon');
        });
        this.formCheck.addEventListener('submit',function (e) {
            obj.formCheck_result.innerHTML = '';
            obj.submit(e,'checkCoupon');
        });
    };

    Form.prototype.submit = function (e, action) {
        let obj = this;
        e.preventDefault();

        let form = e.target;
        let formData = new FormData();

        let inputs_text = form.querySelectorAll('input[type=\'text\']');
        inputs_text.forEach(function (input){
            formData.append(input.getAttribute('name'), input.value);
        });

        BX.ajax.runComponentAction(
            'samsonpost.discount:generate.coupon',
            action,
            {
                mode: 'class',
                data: formData
            })
        .then(function (response) {
            if( response.status == 'success' ) {
                let result = form.getAttribute('id')+'_result';
                obj[result].innerHTML = (response.data['COUPON_CODE'].length > 0 ?'Код: '+response.data['COUPON_CODE']:response.data['COUPON_CODE'])+' Скидка: '+response.data['DISCOUNT_VAL']+'%';
            }
        }, function (reject){
            if( reject.status == 'error' ){
                if( reject.errors ) {
                    for (const [key, value] of Object.entries(reject.errors)) {
                        alert(value.message);
                    }
                }
            }
        });
    };
    document.addEventListener("DOMContentLoaded", function(event) {
        new Form();
    });
})(window);