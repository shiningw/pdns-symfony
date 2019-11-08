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
            {'value': 'A', 'text': 'A'},
            {'value': 'CNAME', 'text': 'CNAME'},
            {'value': 'MX', 'text': 'MX'},
            {'value': 'SOA', 'text': 'SOA'},
            {'value': 'NS', 'text': 'NS'},
            {'value': 'AAAA', 'text': 'AAAA'},
        ];
        var opt = {
            id: 'dnstype',
            data: source,
        };
        selectOpt.push(opt);
        $("#dns-record-sets").tablerow({selector: 'tbody', mode: 'inline', select: selectOpt}).pdnsconfirm();

        $("body").on('change', '.popup-inputname,.inline-inputname', function (e) {
            var zonename = $.pdns.getZoneId();
            var value = $(this).val();
            //append zone id if the user does not enter it along with sub domain
            if (value.indexOf(zonename) === -1) {
                var value = $(this).val() + '.' + zonename;
            }
            $(this).val(value);
        });
        $("body").on("focus",'.popup-inputname,.inline-inputname',function(e){
             if(($(this).val().length) > 4) {
                 $(this).val('');
             }
        });


        $('.ttl,.name').editable({
            selector: 'a',
            mode: 'popup',
            params: {'zone_id': $.pdns.getZoneId()},
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
        var options = {ajaxOptions: {
                dataType: 'json',
            }};
        // console.log($('.dnstype').editableform(options));
        $('.content').editable({
            selector: 'a',

            mode: 'inline',
            params: {'zone_id': $.pdns.getZoneId()},
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









