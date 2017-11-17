Validation.addAllThese([
    [
        'validate-order-max', 'Maximum order total can not be less then minimum order total', function (v) {
        var f = document.getElementById('yehhpay_yehhpay_group_min_order_total').getAttribute('value') * 1;
        var v = v * 1;

        if (f > v) {
            return false;
        } else {
            return true;
        }

    },
        'validate-order-min', 'Minimum order total can not be more then maximum order total', function (v) {
        var f = document.getElementById('yehhpay_yehhpay_group_max_order_total').getAttribute('value') * 1;
        var v = v * 1;

        if (v > f) {
            return false;
        } else {
            return true;
        }

    }
    ]
]);