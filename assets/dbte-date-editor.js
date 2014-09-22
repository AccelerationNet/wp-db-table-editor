if(typeof(console)=='undefined')console={log:function(){}};
if(typeof(DBTableEditor)=='undefined') DBTableEditor={};

DBTableEditor.defaultOffset = new Date().getTimezoneOffset();
DBTableEditor.dateFormats = ["MM-DD-YYYY", "YYYY-MM-DD", moment.ISO_8601];
DBTableEditor.parseMoment = function(ds){
  if(!ds) return null;
  if(ds.toDate) return ds;
  if(ds.getTime) return moment(ds);
  var m;
  // try MY locale -- dont know how to get it to parse to the actual locale
  // Locale Dates are very device / browser dependednt
  m = moment(ds, DBTableEditor.dateFormats);
  if(!m.isValid) return null;
  return m;
};
DBTableEditor.parseDate = function(ds){
  if(!ds || (ds.length && ds.length==0)) return null;
  if(ds && ds.getTime) return ds;
  var d = DBTableEditor.parseMoment(ds);
  return d && d.toDate();
};

DBTableEditor.toISO8601 = function(ds, return_unmodified){
  var d = DBTableEditor.parseMoment(ds);
  if(d) return d.format();
  if(return_unmodified) return ds;
  return null;
};

DBTableEditor.toLocaleDate = function(ds, return_unmodified){
  var d = DBTableEditor.parseMoment(ds);
  if(d) return d.toDate().toLocaleDateString();
  if(return_unmodified) return ds;
  return null;
};

(function($){
 DBTableEditor.DateEditor = function(args) {
    var $input;
    var defaultValue;
    var scope = this;
    var calendarOpen = false;

    this.init = function () {
      $input = $("<INPUT type=text class='editor-text' />");
      $input.appendTo(args.container);
      $input.focus().select();
      $input.datepicker({
        showOn: "button",
        beforeShow: function () {
          calendarOpen = true;
        },
        onClose: function () {
          calendarOpen = false;
          $input.focus().select();
        }
      });
      $input.width($input.width() - 18);
    };

    this.destroy = function () {
      $.datepicker.dpDiv.stop(true, true);
      $input.datepicker("hide");
      $input.datepicker("destroy");
      $input.remove();
    };

    this.show = function () {
      if (calendarOpen) {
        $.datepicker.dpDiv.stop(true, true).show();
      }
    };

    this.hide = function () {
      if (calendarOpen) {
        $.datepicker.dpDiv.stop(true, true).hide();
      }
    };

    this.position = function (position) {
      if (!calendarOpen) {
        return;
      }
      $.datepicker.dpDiv
          .css("top", position.top + 30)
          .css("left", position.left);
    };

    this.focus = function () {
      $input.focus();
    };

    this.loadValue = function (item) {
      defaultValue = DBTableEditor.toLocaleDate(item[args.column.field], true);
      // console.log('loading ',defaultValue);
      $input.val(defaultValue);
      $input[0].defaultValue = defaultValue;
      $input.select();
    };

    this.serializeValue = function () {
      var dv = DBTableEditor.toISO8601($input.val(), true);
      // console.log('saving ',dv);
      return dv;
    };

    this.applyValue = function (item, state) {
      item[args.column.field] = state;
    };

    this.isValueChanged = function () {
      return (!($input.val() == "" && defaultValue == null)) && ($input.val() != defaultValue);
    };

    this.validate = function () {
      return {
        valid: true,
        msg: null
      };
    };

    this.init();
  };
})(jQuery);