+(function ($) {
    $(document).ready(function () {
        var input = [];
        input.push({'name': 'zonename', 'type': 'text', 'text': 'New Zone Name'});
        var newrowTpl = '<tr id="rowtpl" class="rowtpl">\
     <td><a class="zone-id" href=""></a></td>\
     <td class="zone-serial"></td>\
     <td class="zone-kind"></td>\
     <td class="actions">\
     <button type="button" class="removal-button-zone btn btn-default" data-zone_id="" ><span class="glyphicon glyphicon-trash"></span>Delete</button>\
     </td>\
     </tr>';
        var url = $.pdns.getUrlPrefix() + '/pdns/zone/create';
        var callback = function(formdata,$tpl){
            $tpl.find('.zone-id').text(formdata.zonename);
        }
        $("#zone-table-list").tablerow({saveCallback:callback,newrowTpl:newrowTpl,ajaxurl:url,selector: 'tbody', mode: 'popup',input:input,target:".add-zone-button"}).zoneconfirm();
         //$("#zone-table-list").zoneconfirm();
    });
})(jQuery);



