+(function ($) {

    'use strict';
    var zoneSelector = 'table';
    var zonePrefix = '-';
    var removal = ".cancel-button";
    var pdns = $.pdns;
    var ajaxPrefix = pdns.getUrlPrefix();

    $(document).ready(function () {
        //$("body").dnsrecord();
        var selectOpt = [];
        var source = [
            { 'value': 'A', 'text': 'A' },
            { 'value': 'CNAME', 'text': 'CNAME' },
            { 'value': 'MX', 'text': 'MX' },
            { 'value': 'SOA', 'text': 'SOA' },
            { 'value': 'NS', 'text': 'NS' },
            { 'value': 'AAAA', 'text': 'AAAA' },
            { 'value': 'TXT', 'text': 'TXT' },
        ];
        var opt1 = {
            id: 'recordtype',
            data: source,
            name: "Record Type",
        };
        selectOpt.push(opt1);

        $("#dns-record-sets").tablerow({ selector: 'tbody', mode: 'popup', select: selectOpt }).pdnsconfirm();

        const completeDomain = function (e) {

            if (!e.target.classList.contains('popup-inputname') && !e.target.classList.contains('inline-inputname'))
                return;

            if (e.type == "change") {
                var zonename = $.pdns.getZoneId();
               // zonename = $.pdns.trim(zonename, '.');
                var value = e.target.value;
                //append zone id if the user does not enter it along with sub domain
                if (value.indexOf(zonename) === -1 && value.length != 0) {
                    var value = value + '.' + zonename;
                } else if (value.length == 0) {
                    var value = zonename;
                }
                e.target.value = value;
            }
            if (e.type == "focusout") {
                if (e.target.value == '') {
                    var zonename = $.pdns.getZoneId();
                    e.target.value = zonename;
                }
            }
            if (e.type == "focus") {
                if (e.target.value.length > 3) {
                    e.target.value = '';
                }
            }

        }
        document.querySelector("body").addEventListener("focusout", completeDomain, false);
        //focus support capturing but not bubbling
        document.querySelector("body").addEventListener("focus", completeDomain, {capture:true});
        document.querySelector("body").addEventListener("change", completeDomain, false);
        $('.ttl,.name').editable({
            selector: 'a',
            mode: 'popup',
            params: { 'zone_id': $.pdns.getZoneId() },
            ajaxOptions: {
                dataType: 'json',
            },
            success: function (response, value) {
                //console.log(response);
                if (!response) {
                    return 'unknown network error';
                } else {
                    if (response.code === 422) {
                        return response.msg;
                    }
                }
            }

        });
        var options = {
            ajaxOptions: {
                dataType: 'json',
            }
        };
        // console.log($('.recordtype').editableform(options));
        $('.content').editable({
            selector: 'a',

            mode: 'inline',
            params: { 'zone_id': $.pdns.getZoneId() },
            //tpl: "<input type='textarea' style='width: 400px'>",

            ajaxOptions: {
                dataType: 'json',
            },
            success: function (response, value) {
                //console.log(response);
                if (!response) {
                    return 'unknown network error';
                } else {
                    if (response.code === 422) {
                        return response.msg;
                    }
                }
            }

        });

    });
})(jQuery);










