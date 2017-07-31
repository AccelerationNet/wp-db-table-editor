jQuery(function(){
  var doUpdateWidths = function(){
    // not sure why this was required, but I was getting infinite loops without its, so whatever
    if(!DBTableEditor || !DBTableEditor.grid) return;
    var cs = DBTableEditor.grid.getColumns();
    cs[0].width = 175; // set widths like so
    DBTableEditor.grid.setColumns(cs);
  };
  setTimeout(function(){
    NS.doUpdateWidths();
  }, 125);
});
