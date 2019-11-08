+(function ($) {
    $.pdns = {
        isDevMode: function () {
            var path = location.pathname.split("/");
            if (path.length > 1 && path[1] == 'app_dev.php') {
                return true;
            } else {
                this.urlPrefix = '';
                return false;
            }
        },
        inherit: function (Child, Parent) {
            var F = function () { };
            F.prototype = Parent.prototype;
            Child.prototype = new F();
            Child.prototype.constructor = Child;
            Child.superclass = Parent.prototype;
        },
        getUrlPrefix: function () {
            this.setUrlPrefix();
            return this.prefix;
        },
        setUrlPrefix: function (prefix) {
            if (this.isDevMode()) {
                this.prefix = '/app_dev.php';
            } else {
                this.prefix = '';
            }
        },
        getZoneId: function () {
            this.zone_selector = 'table';
            this.zone_prefix = '-';
            var id = $(this.zone_selector).attr("id");
            var start = id.indexOf(this.zone_prefix);
            return id.substr((start + 1));
        },
        createForm: function (element, options) {
            var mode = options.mode;
            var Constructor = $.pdns.newdata[mode];
            return new Constructor(element, options);

        }
    };
    $.pdns.message = {

        show: function (selector, msg, alertType) {
            if (typeof alertType === 'undefined' || alertType === null) {
                var alertType = 'alert-success';
            }
            $(selector).find('span').text(msg);
            $(selector).children('div .alert').addClass(alertType);
            if (alertType == 'alert-success') {
                $(selector).fadeIn().stop(true, true).delay(1000).fadeOut(900);
            } else {
                $('.pdns-status-message').fadeIn();
            }
        },
        error: function (selector, msg) {
            this.show(selector, msg, 'alert-danger');
        },
        success: function (selector, msg) {
            this.show(selector, msg, 'alert-success');
        },

    }
    $.pdns.ajax = {
        post: function () {

            var request = $.ajax({
                type: 'POST',
                data: this.data,
                url: this.url,
            });
            resHandler = $.proxy(function (data, textStatus, xhr) {
                if (data.code >= 200 && data.code <= 204) {
                    $.pdns.message.show('.pdns-status-message', data.msg);
                    if (this.successHandler !== undefined && typeof this.successHandler == 'function')
                        this.successHandler();
                } else {
                    $.pdns.message.show('.pdns-status-message', data.msg, 'alert-danger');
                }
            }, this);
            errorHandler = $.proxy(function (data, textStatus, xhr) {
                $.pdns.message.show('.pdns-status-message', data.statusText, 'alert-danger');
                if (this.errorHandler !== undefined && typeof this.errorHandler == 'function')
                    this.errorHandler();
            }, this);
            request.done(resHandler);
            request.fail(errorHandler);
            return request;
        },
    }
    $.pdns.newdata = {};

})(jQuery);
+function ($) {
    "use strict";
    var Tablerow = function (element, options) {
        this.element = element;
        //get the selector so we can get the element dynamically
        this.container = element.className || element.id;
        this.options = $.extend({}, $.fn.tablerow.defaults, options);
        this.$element = $(element);
        this.selector = this.options.selector;
        this.$tbody = $(this.selector);
        var $newrowTpl = this.$tbody.find('tr').eq(0).clone();
        this.$newrowTpl = ($newrowTpl.length > 0) ? $newrowTpl : $(this.options.newrowTpl);
        this.ajaxurl = this.options.ajaxurl || $.pdns.getUrlPrefix() + '/pdns/record/create';
        this.init();
    };
    Tablerow.prototype = {
        constructor: Tablerow,
        init: function () {
            //$.fn.popupform.defaults.input.push({name: 'ttl', text: 'TTL', type: 'text'});
            $(this.options.target).on('click.tablerow.addrow', $.proxy(this.render, this));
            var saveHandler = function (e, formdata) {
                e.stopPropagation();
                this.save(this.ajaxurl, formdata);
            };
            this.$element.on('saved.popupform saved.tablerow.linline', saveHandler.bind(this));
        },
        render: function () {
            this.$container = this.element.className ? $("." + this.container) : $("#" + this.container);
            this.form = $.pdns.createForm(this.$container, this.options);
            this.form.render();
        },
        refresh: function () {
            this.$tbody = $(this.selector);
        },
        //form data come from inline or popup form class using triggerHandler
        save: function (url, formdata) {
            this.refresh();
            //this.$tbody.pdnsconfirm();
            $.pdns.ajax.url = url;
            $.pdns.ajax.data = formdata;
            var resp = $.pdns.ajax.post();
            this.formdata = formdata;
            var saveHandler = this.afterSave.bind(this);
            $.pdns.ajax.successHandler = saveHandler(this.options.saveCallback);
        },
        afterSave: function (callback) {
            var formdata = this.formdata;
            var t = typeof callback;
            if (t !== undefined && t === 'function') {
                callback.call(this, formdata, this.$newrowTpl);

            } else {
                for (var key in formdata) {
                    var selector = '.' + key;
                    if (this.$newrowTpl.find(selector + " a").length > 0) {
                        this.$newrowTpl.find(selector + " a").text(formdata[key]);
                    } else {
                        this.$newrowTpl.find(selector).text(formdata[key]);
                    }
                }
            }
            this.$tbody.prepend(this.$newrowTpl);

            if (this.options.mode === 'inline') {
                this.$container.find("#" + this.options.newrowSelector).remove();
            }

        },
        show: function () {
            this.$tpl.show();
        },
        hide: function () {
            this.$tpl.hide();
        },

    }
    $.fn.tablerow = function (option) {
        return this.each(function () {
            var $this = $(this),
                    data = $this.data('tablerow'),
                    options = typeof option === 'object' && option;
            if (!data) {
                $this.data('tablerow', (data = new Tablerow(this, options)));
            }
        })
    };
    $.fn.tablerow.defaults = {
        target: '.new-record',
        selector: '.table-content'
    };
    $.fn.tablerow.defaults.newrowTpl = '<tr class="primary last-row" id="updated-row" >\
                        <td class="name"><a href="#" data-type="text" data-pk="" data-url="/pdns/record/update" data-title="">[]</a></td>\
                        <td class="dnstype"><span data-type="text" data-pk="" data-url="/pdns/record/update" data-title="Enter dns type">[]</span></td>\
                        <td class="ttl" ><a href="#" data-type="text" data-pk="" data-url="/pdns/record/update" data-title="Enter TTL VALUE">[]</a></td>\
                        <td class="content"><a href="#" data-type="text" data-pk="" data-url="/pdns/record/update" data-title="Enter TTL VALUE">[]</a></td>\
                        <td class="actions">\
                            <button type="button" class="removal-button btn btn-default"><span class="glyphicon glyphicon-trash"></span>Delete</button>\
                       </td>\
                    </tr>';
    $.fn.tablerow.defaults.newrowSelector = 'newtablerow';
}(jQuery);
+(function ($) {
    var PopupForm = function (element, options) {
        this.$element = $(element);
        this.options = $.extend({}, $.fn.popupform.defaults, options);
        this.input = this.options.input;
        this.select = this.options.select;
        this.init();
    };
    PopupForm.prototype = {
        constructor: PopupForm,
        init: function () {
            this.$modal = $(this.options.template);
            this.$form = $(this.options.formTpl);
            this.inputElement = this.getInput();
            this.$inputDiv = this.$form.find('.popup-input');
            for (var key in this.inputElement) {
                this.$inputDiv.append(this.inputElement[key]);
            }
            if (this.select !== undefined) {
                this.selectElement = this.getSelect();
                for (var key in this.selectElement) {
                    this.$inputDiv.append(this.selectElement[key]);
                }
            }

            this.$modal.find(".popup-body").append(this.$form);
            $("body").on('click.popupform', ".close,.popup-cancel", function (e) {
                $(".popup-container").remove();
            });

        },
        render: function () {
            this.$element.append(this.$modal);
            this.$submitbtn = this.$element.find(".popup-submit");
            this.$submitbtn.on('click.popup.submit', $.proxy(this.save, this));
        },
        save: function () {
            formdata = this.getFormData();
            if ($.isEmptyObject(formdata)) {
                return;
            }
            formdata.zone_id = $.pdns.getZoneId();
            this.$element.trigger('saved.popupform', [formdata]);
        },

        getFormData: function () {
            var formData = this.$element.find('form').serializeArray();
            var obj = formData.reduce(function (accu, item) {
                accu[item.name] = item.value;
                return accu;
            }, {});
            return obj;
        },
        getInput: function () {
            var input = [];
            var that = this;
            for (var i = 0; i < this.input.length; i++) {
                input.push(this.createInput(this.input[i]));
            }
            return input;
        },
        getSelect: function () {
            var select = [];
            var that = this;
            for (var i = 0; i < this.select.length; i++) {
                var options = {};
                options.id = this.select[i].id;
                options.data = this.select[i].data
                select.push(this.createSelect(options));
            }
            return select;
        },
        createSelect: function (options) {
            var label = document.createElement('label');
            var content = document.createTextNode(options.id);
            label.appendChild(content);
            var selectList = document.createElement("select");
            selectList.id = options.id;
            selectList.name = options.id;
            for (var i = 0; i < options.data.length; i++) {
                var option = document.createElement("option");
                option.value = options.data[i].value;
                option.text = options.data[i].text;
                selectList.appendChild(option);
            }
            label.appendChild(selectList)
            return label;
        },
        createInput: function (options) {
            var label = document.createElement('label');
            var content = document.createTextNode(options.text);
            label.appendChild(content);
            var input = document.createElement("input");
            input.type = options.type;
            input.name = options.name;
            input.placeholder = "Enter " + options.name;
            input.className = "popup-input" + options.name;
            input.value = options.value || '';
            label.appendChild(input);
            return label;
        }
    }
    $.fn.popupform = function (option) {
        return this.each(function () {
            var $this = $(this),
                    data = $this.data('popupform'),
                    options = typeof option === 'object' && option;
            if (!data) {
                $this.data('popupform', (data = new PopupForm(this, options)));
            }
        })
    };
    $.fn.popupform.constructor = PopupForm;
    $.fn.popupform.defaults = {
        target: '.new-record',
    };
    $.fn.popupform.defaults.template = '\
<div class="popup-container alert" >\
    <div class="popup-content">\
     <div class="popup-header">\
        <button type="button" class="close" >\
          <span aria-hidden="true">&times;</span>\
        </button>\
        </div>\
      <div class="popup-body">\
      </div>\
      <div class="popup-footer">\
        <div class="btn-container">\
        <button type="button" class="popup-cancel btn btn-warning">cancel</button>\
        <button type="submit" class="popup-submit btn btn-primary">save</button>\
        </div>\
      </div>\
    </div>\
</div>';
    $.fn.popupform.defaults.formTpl = '<form class="form-inline">' +
            '<div class="control-group">' +
            '<div class="popup-input"></div>' +
            '<div class="popup-error-block"></div>' +
            '</div>' +
            '</form>';
    $.fn.popupform.defaults.buttons = '<button type="submit" class="popup-submit">ok</button>' +
            '<button type="button" class="editable-cancel">cancel</button>';
    var input = [];
    input.push({'name': 'content', 'type': 'text', 'text': 'DNS Value', value: '10.10.10.10'});
    input.push({'name': 'ttl', 'type': 'text', 'text': 'TTL Value', value: 600});
    input.push({'name': 'name', 'type': 'text', 'text': 'DNS Name'});
    $.fn.popupform.defaults.input = input;
    $.extend($.pdns.newdata, {popup: PopupForm});
})(jQuery);
+function ($) {

    var InlineForm = function (element, options) {
        this.$element = $(element);
        this.options = $.extend({}, $.fn.inlineform.defaults, options);
        this.$cancelbtn = $(this.options.cancelButton);
        this.$savebtn = $(this.options.saveButton);
        this.newrowSelector = this.options.newrowSelector;
        this.init();
    }

    InlineForm.prototype = {
        constructor: InlineForm,
        init: function () {
            this.$element.on('click.tablerow.cancel', this.options.cancelTarget, $.proxy(this.cancel, this));
        },
        render: function () {
            this.$tpl = $(this.options.template);
            this.$tpl.attr('id', this.newrowSelector);
            this.newrowSelector = (this.newrowSelector.charAt(0) == '#') ? this.newrowSelector : '#' + this.newrowSelector;
            this.$tpl.find(".actions").append(this.options.cancelButton);
            this.$tpl.find(".actions").append(this.options.saveButton);
            this.$element.find(this.options.selector).append(this.$tpl);
            this.$newrow = $(this.newrowSelector);
            this.$element.on('click.tablerow.save', this.options.saveTarget, $.proxy(this.save, this));
        },
        cancel: function () {
            this.$cancelbtn.triggerHandler('cancel.tablerow');
            //this.closest('tr').remove();
            this.remove();
        },
        remove: function () {
            this.$newrow.remove();
        },
        save: function () {
            var formdata = this.getValues();
            if ($.isEmptyObject(formdata)) {
                return;
            }
            formdata.zone_id = $.pdns.getZoneId();

            this.$element.triggerHandler('saved.tablerow.linline', [formdata]);
        },
        getValues: function () {
            var formdata = {};
            var $input = $(this.newrowSelector).find('input, select,textarea');
            $input.each(function () {
                var value = $(this).val();
                var key = $(this).attr('id');
                formdata[key] = value;
            });
            return formdata;
        }
    }

    $.fn.inlineform = function (option) {
        return this.each(function () {
            var $this = $(this),
                    data = $this.data('inlineform'),
                    options = typeof option === 'object' && option;
            if (!data) {
                $this.data('inlineform', (data = new InlineForm(this, options)));
            }
        })
    };
    $.fn.inlineform.defaults = {
        target: '.new-record',
    }
    $.fn.inlineform.defaults.template = '<tr>\
				<td class="name" title="secondary domain name"><input id="name" class="inline-inputname" type="text" value="" ></td>\
				<td class="dnstype"><select class="dns-options" id="dnstype"><option>A</option><option>AAAA</option><option>CNAME</option><option>MX</option><option>NS</option></select></td>\
				<td class="ttl" ><input id="ttl" type="text" value="600"></td>\
				<td class="content"><input id="content" type="text" value="10.10.10.10"></td>\
				<td class="actions">\
				</tr>';
    $.fn.inlineform.defaults.cancelButton = '<button type="button"  class="cancel-button btn btn-default"><span class="glyphicon glyphicon-trash"></span>Cancel</button>';
    //Dnsrecord.newrowTpl = newrowTpl.replace('subdomain name', pdns.getZoneId());
    $.fn.inlineform.defaults.saveButton = '<button type="button"  class="save-button btn btn-default"><span class="glyphicon glyphicon-save"></span>Save</button>';
    $.fn.inlineform.defaults.loading = '<button class="pdns-loading btn btn-default" type="button" style="display:none">Loading</button>'
    $.fn.inlineform.defaults.cancelTarget = '.cancel-button';
    $.fn.inlineform.defaults.saveTarget = '.save-button';
    $.extend($.pdns.newdata, {inline: InlineForm});
}(jQuery);

+function ($) {
    //'use strict';
    var pdns = $.pdns;
    var ajaxPrefix = pdns.getUrlPrefix();

    var PdnsConfirm = function (element, options) {
        this.element = element;
        this.options = $.extend({}, PdnsConfirm.defaults, options);
        this.init();
    }
    PdnsConfirm.prototype = {
        init: function () {
            this.$element = $(this.element);
            this.$tpl = $(this.options.msgTpl);
            this.$yesbtn = $(this.options.yesbutton);
            this.$nobtn = $(this.options.nobutton);
            this.selector = this.options.selector;
            this.$removebtn = this.$element.find(this.selector);
            this.$element.on('click.pdnsconfirm.popup', this.selector, $.proxy(this.popup, this));
            this.$element.on('confirm.pdnsconfirm.yes', $.proxy(this.confirm, this));
        },

        popup: function (e) {
            this.render(e);
            this.$nobtn.on("click.pdnconfirm.cancel", $.proxy(this.removePopup, this));
        },
        render: function (e) {
            //console.log(e);
            var that = this;
            this.$tpl.find('.btn-container').append(this.$nobtn);
            this.$tpl.find('.btn-container').append(this.$yesbtn);
            var $target = $(e.target);
            var $currentRow = $target.closest('tr');
            this.$selectedRow = $currentRow;
            this.$tpl.find(".popup-body").append(this.createTable(that.getData()));
            this.$element.append(this.$tpl);
            var shownEvent = $.Event('shown.pdnsconfirm');
            this.$element.triggerHandler(shownEvent, [$currentRow]);
            this.$element.on('click.pdnsconfirm.yes', ".popup-confirm", function (e) {
                that.$element.triggerHandler('confirm.pdnsconfirm', [$currentRow]);
            });
        },
        confirm: function (e, $tablerow) {
            var data = this.getData();
            this.delete(data);
            //prevent the handler from being run multiple times when the content is updated dynamically
            this.$element.off('click.pdnsconfirm.yes')
        },
        //it relies on class name to identify table cells and return value in them. the values is stored in a object keyed by the class name
        getData: function () {
            var data = {};
            var tablecell = this.$selectedRow.find('td');
            for (var key in tablecell) {
                if (tablecell[key].innerText !== undefined && tablecell[key].innerText.length >= 1 && /^[a-zA-Z0-9\._-]*$/i.test(tablecell[key].innerText)) {
                    data[tablecell[key].className] = tablecell[key].innerText;
                }
            }
            return data;
        },
        remove: function () {
            this.removePopup();
            this.$selectedRow.remove();
            this.removeTable();
        },
        removeTable: function () {
            this.$tpl.find('table').remove();
        },
        removePopup: function (e) {
            this.removeTable();
            this.$tpl.remove();
        },
        //tablecells must be an object literal keyed by heading,eg{name:'myname','test':'true'}
        createTable: function (tablecells) {
            var table = document.createElement('table');
            //console.log(table);
            //table.createThead();
            table.className = "table";
            var thead = document.createElement('thead');
            var tbody = document.createElement('tbody');
            var headRow = thead.insertRow(-1);
            var dataRow = tbody.insertRow(-1);
            for (var prop in tablecells) {
                if (prop == 'actions') {
                    continue;
                }
                var headCell = headRow.insertCell();
                var headText = document.createTextNode(prop);
                headCell.appendChild(headText);

                var dataCell = dataRow.insertCell();
                var dataText = document.createTextNode(tablecells[prop]);
                dataCell.appendChild(dataText);
            }

            table.appendChild(thead);
            table.appendChild(tbody);

            return table;
        },

        delete: function (data) {
            //var $this = $(e.target);
            //var data_pk = $this.parent().parent().children('.content').find('a').attr("data-pk");
            //var data = JSON.parse(data_pk);
            data.zonename = $.pdns.getZoneId();
            //var that = this;
            $.pdns.ajax.url = $.pdns.getUrlPrefix() + '/pdns/record/delete';
            $.pdns.ajax.data = data;
            //needs to bind this to the currect context or it will execute using the $.pdns context
            $.pdns.ajax.successHandler = $.proxy(this.remove, this);
            $.pdns.ajax.post();
        },
    }
    PdnsConfirm.defaults = {
        selector: '.removal-button',
    };
    PdnsConfirm.defaults.msgTpl = '\
<div class="popup-container" >\
    <div class="popup-content">\
     <div class="popup-header">\
     </div>\
      <div class="popup-body">\
      </div>\
      <div class="popup-footer">\
        <div class="btn-container">\
        </div>\
     </div>\
    </div>\
</div>';
    PdnsConfirm.defaults.yesbutton = '<button type="submit" class="popup-confirm btn btn-primary">Confirm</button>';
    PdnsConfirm.defaults.nobutton = '<button type="button" class="popup-cancel btn btn-warning">Cancel</button>';

    var Plugin = function (option) {
        return this.each(function () {
            var $this = $(this),
                    data = $this.data('pdnsconfirm'),
                    options = typeof option === 'object' && option;
            if (!data) {
                $this.data('pdnsconfirm', (data = new PdnsConfirm(this, options)));
            }
        })
    };
    $.fn.pdnsconfirm = Plugin;
    $.fn.pdnsconfirm.Constructor = PdnsConfirm;

}(jQuery);

+function ($) {
    var ZoneConfirm = function (element, options) {
        this.element = element;
        this.options = $.extend({}, $.fn.pdnsconfirm.Constructor.defaults, options);
        this.init();
    };

    $.pdns.inherit(ZoneConfirm, $.fn.pdnsconfirm.Constructor);
    $.extend(ZoneConfirm.prototype, {
        getData: function () {
            var data = {};
            data.zonename = this.$selectedRow.attr('id');
            return data;
        },
        delete: function (data) {
            $.pdns.ajax.url = $.pdns.getUrlPrefix() + '/pdns/zone/remove';
            $.pdns.ajax.data = data;
            //needs to bind this to the currect context or it will execute using the $.pdns context
            $.pdns.ajax.successHandler = $.proxy(this.remove, this);
            $.pdns.ajax.post();
        }
    });

    $.fn.zoneconfirm = function (option) {
        return this.each(function () {
            var $this = $(this),
                    data = $this.data('zoneconfirm'),
                    options = typeof option === 'object' && option;
            if (!data) {
                $this.data('zoneconfirm', (data = new ZoneConfirm(this, options)));
            }
        });
    };
}(jQuery);
HTMLTableRowElement.prototype.insertCell = (function (oldInsertCell) {
    return function (index) {
        if (this.parentElement.tagName.toUpperCase() == "THEAD") {
            if (index < -1 || index > this.cells.length) {
                // This case is suppose to throw a DOMException, but we can't construct one
                // Just let the real function do it.
            } else {
                let th = document.createElement("TH");
                if (arguments.length == 0 || index == -1 || index == this.cells.length) {
                    return this.appendChild(th);
                } else {
                    return this.insertBefore(th, this.children[index]);
                }
            }
        }
        return oldInsertCell.apply(this, arguments);
    }
})(HTMLTableRowElement.prototype.insertCell);