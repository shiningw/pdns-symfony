/**
 custom Text input
 override Text input from x-editable
 **/
(function ($) {
    "use strict";

    var pdnsText = function (options) {
        this.init('pdnstext', options, pdnsText.defaults);

    };

    $.fn.editableutils.inherit(pdnsText, $.fn.editabletypes.abstractinput);

    $.extend(pdnsText.prototype, {
        render: function () {
            this.renderClear();
            this.setClass();
            this.setAttr('placeholder');
        },

        activate: function () {
            if (this.$input.is(':visible')) {
                this.$input.focus();
                $.fn.editableutils.setCursorPosition(this.$input.get(0), this.$input.val().length);
                if (this.toggleClear) {
                    this.toggleClear();
                }
            }
            var inputLen = this.$input.val().length;
            if (inputLen > 50) {
                this.$input.attr('size', inputLen + 5);
            }

        },

        //render clear button
        renderClear: function () {
            if (this.options.clear) {
                this.$clear = $('<span class="editable-clear-x"></span>');
                this.$input.after(this.$clear)
                        .css('padding-right', 24)
                        .keyup($.proxy(function (e) {
                            //arrows, enter, tab, etc
                            if (~$.inArray(e.keyCode, [40, 38, 9, 13, 27])) {
                                return;
                            }

                            clearTimeout(this.t);
                            var that = this;
                            this.t = setTimeout(function () {
                                that.toggleClear(e);
                            }, 100);

                        }, this))
                        .parent().css('position', 'relative');

                this.$clear.click($.proxy(this.clear, this));
            }
        },

        postrender: function () {
            /*
             //now `clear` is positioned via css
             if(this.$clear) {
             //can position clear button only here, when form is shown and height can be calculated
             //                var h = this.$input.outerHeight(true) || 20,
             var h = this.$clear.parent().height(),
             delta = (h - this.$clear.height()) / 2;
             
             //this.$clear.css({bottom: delta, right: delta});
             }
             */
        },

        //show / hide clear button
        toggleClear: function (e) {
            if (!this.$clear) {
                return;
            }

            var len = this.$input.val().length,
                    visible = this.$clear.is(':visible');

            if (len && !visible) {
                this.$clear.show();
            }

            if (!len && visible) {
                this.$clear.hide();
            }
        },

        clear: function () {
            this.$clear.hide();
            this.$input.val('').focus();
        }
    });

    pdnsText.defaults = $.extend({}, $.fn.editabletypes.abstractinput.defaults, {
        /**
         @property tpl 
         @default <input type="text">
         **/
        tpl: "<input type='text'>",
        /**
         Placeholder attribute of input. Shown when input is empty.
         
         @property placeholder 
         @type string
         @default null
         **/
        placeholder: null,

        /**
         Whether to show `clear` button 
         
         @property clear 
         @type boolean
         @default true        
         **/
        clear: true
    });

    $.fn.editabletypes.text = pdnsText;

}(window.jQuery));
