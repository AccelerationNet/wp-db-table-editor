if(typeof(NS) == 'undefined') NS={};
(function(){ "use strict";
var $ = jQuery;

NS._doUpdateWidths = function(){
  // not sure why this was required, but I was getting infinite loops without its, so whatever
  if(NS._widthUpdateDone) return;
  NS._widthUpdateDone = true;
  NS._widthTimeout = null;
  var cs = DBTableEditor.grid.getColumns();
  cs[0].width = 175;
  DBTableEditor.grid.setColumns(cs);
};

NS._updateWidthsNext = function(){
  if(NS._widthTimeout) window.clearTimeout(NS._widthTimeout);
  NS._widthTimeout = window.setTimeout(NS._doUpdateWidths, 100);
};

DBTableEditor.extraButtons.push( function(row, cell, value, col, dataContext){
  var id = dataContext[DBTableEditor.columnMap[DBTableEditor.id_column]];
  var rowid = dataContext.id; // uses id, NOT id_column
  var url = '/wp-content/themes/mytheme/arrow_turn_left.png';
  var dbteurl = window.location.toString();
  var out ="";
  if(dbteurl.search('reports')>=0){
    out += '<a href="/members/report-a-find/?id='+id
   +'" title="Load this object in the form" class="sb-create-payment">'
   +'<img src="'+url+'" /> Load'
      +'</a>';
  }else if(dbteurl.search('memberdata')>=0){
    out += '<a href="/members/account-information/?id='+id
   +'" title="Load this object in the form" class="sb-create-payment">'
   +'<img src="'+url+'" /> Load'
      +'</a>';
  }
  // not sure why this was required, but I was getting infinite loops without its, so whatever
  if(!NS._widthUpdateDone) NS._updateWidthsNext();
  return out;
});

})();
