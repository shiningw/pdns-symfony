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
            {'value': 'TXT','text':'TXT'},
        ];
        var opt1 = {
            id: 'recordtype',
            data: source,
            name:"Record Type",
        };
        var opt2 = {
            id:"isp",
            data:[{"value":1,"text":"电信"},{"value":2,"text":"联通"},{"value":3,"text":"移动"}],
            name:"ISP"
        }
        selectOpt.push(opt1,opt2);

        $("#dns-record-sets").tablerow({selector: 'tbody', mode: 'popup', select: selectOpt}).pdnsconfirm();

        $("body").on('change', '.popup-inputname,.inline-inputname', function (e) {
            var zonename = $.pdns.getZoneId();
            console.log(zonename);
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
        // console.log($('.recordtype').editableform(options));
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










