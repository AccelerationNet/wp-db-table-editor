if(typeof(console)=='undefined')console={log:function(){}};
if(typeof(DBTableEditor)=='undefined') DBTableEditor={};

DBTableEditor.commandQueue =[];
DBTableEditor.queueAndExecuteCommand = function(item, column, editCommand){
  DBTableEditor.commandQueue.push(editCommand);
  editCommand.execute();
};

DBTableEditor.saveFailCB = function(err, resp){
  console.log('SAVE FAILED', err, resp);
  jQuery('button.save').attr("disabled", null);
  var src = jQuery('button.save img').attr('src');
  jQuery('button.save img').attr('src',src.replace('loading.gif','accept.png'));

};
DBTableEditor.saveCB = function(data){
  console.log('Save Success');
  jQuery('button.save').attr("disabled", null);
  var src = jQuery('button.save img').attr('src');
  jQuery('button.save img').attr('src',src.replace('loading.gif','accept.png'));

  var pair;
  while((pair = data.pop())){
    var item = DBTableEditor.dataView.getItemById( pair.rowId );
    item[DBTableEditor.columnMap['id']] = pair.dbid;
    DBTableEditor.dataView.updateItem(pair.rowId, item);
  }
  DBTableEditor.clearPendingSaves();
};

DBTableEditor.clearFilters = function(){
  DBTableEditor.columnFilters = {};
  jQuery(DBTableEditor.grid.getHeaderRow())
   .find(':input').each(function(){jQuery(this).val('');});
  DBTableEditor.dataView.refresh();
};

DBTableEditor.save = function(){
  jQuery('button.save').attr("disabled", "disabled");
  var src = jQuery('button.save img').attr('src');
  jQuery('button.save img').attr('src',src.replace('accept.png','loading.gif'));

  // the last time we modified a row should contain all the final modifications
  var h = {},i,r, toSave=[], mod = DBTableEditor.modifiedRows.slice(0);
  while(( r = mod.pop() )){
    if(h[r.item.id]) continue;
    h[r.item.id] = true;
    toSave.push(r.item);
  }
  //console.log(toSave);
  var cols = DBTableEditor.data.columns.map(function(c){return c.name;});
  cols.shift(); // remove buttons
  var toSend = JSON.stringify({
    columns:cols,
    rows:toSave
  });

  jQuery.post(ajaxurl, {action:'dbte_save', data:toSend, table:DBTableEditor.table})
    .success(DBTableEditor.saveCB)
    .error(DBTableEditor.saveFailCB);

};

DBTableEditor.undo = function () {
  var command = DBTableEditor.commandQueue.pop();
  if (command && Slick.GlobalEditorLock.cancelCurrentEdit()) {
    command.undo();
    DBTableEditor.popPendingSaves(2);// remove the undo, and the undone thing
    DBTableEditor.grid.gotoCell(command.row, command.cell, false);
  }
  return false;
};
DBTableEditor.gotoNewRow = function () {
  DBTableEditor.grid.gotoCell(DBTableEditor.grid.getDataLength(), 0, true);
};


DBTableEditor.filterRow = function (item) {
  //console.log(item);
  var columnFilters = DBTableEditor.columnFilters,
      grid = DBTableEditor.grid;
  for (var columnId in columnFilters) {
    if (columnId !== undefined && columnFilters[columnId] !== "") {
      var c = grid.getColumns()[grid.getColumnIndex(columnId)];
      var filterVal = columnFilters[columnId];
      var re = new RegExp(filterVal,'i');
      if (item[c.field].toString().search(re) < 0) {
        return false;
      }
    }
  }
  return true;
};

DBTableEditor.deleteSuccess = function(data, id, rowId){
  console.log('Removed', data, id, rowId);
  DBTableEditor.dataView.deleteItem(rowId);
};

DBTableEditor.deleteFail = function(err, resp){
  console.log('delete failed', err, resp);
};

DBTableEditor.deleteHandler = function(el){
  var btn = jQuery(el);
  var id = btn.data('id');
  var rowid = btn.data('rowid');
  btn.parents('.slick-row').addClass('active');
  if(!id) return;
  if(!btn.is('button'))btn = btn.parents('button');
  if (!confirm('Are you sure you wish to remove this row')) return;
  jQuery.post(ajaxurl, {action:'dbte_delete', dataid:id, rowid:rowid, table:DBTableEditor.table})
   .success(function(data){DBTableEditor.deleteSuccess(data, id, rowid);})
   .error(DBTableEditor.deleteFail);
  return false;
};
DBTableEditor.extraButtons=[];
DBTableEditor.rowButtonFormatter = function(row, cell, value, columnDef, dataContext) {
  // if(row==0)console.log(row,cell, value, columnDef, dataContext);
  var id = dataContext[DBTableEditor.columnMap['id']];
  var rowid = dataContext['id'];
  if(!id) return null;
  var url = DBTableEditor.baseUrl+'/assets/images/delete.png';
  var out = '<button title="Delete this Row" class="delete" onclick="DBTableEditor.deleteHandler(this);return false;"'+
    ' data-rowid="'+rowid+'" '+
    ' data-id="'+id+'" />'+
    '<img src="'+url+'"/></button>';
  jQuery.each(DBTableEditor.extraButtons, function(i,fn){
    out += fn(row, cell, value, columnDef, dataContext);
  });
  return out;
};

DBTableEditor.exportCSV = function(){
  var url = jQuery(DBTableEditor.grid.getHeaderRow())
   .find(':input').filter(function(){return jQuery(this).val().length>0;})
   .serialize();
  var url = ajaxurl+'?action=dbte_export_csv&table='+DBTableEditor.table+'&'+url;
  console.log('Redirecting to export:', url);
  window.location=url;
};

DBTableEditor.updatePagingInfo = function(){
  var cnt = DBTableEditor.dataView.getPagingInfo()["totalRows"];
  jQuery('.db-table-editor-row-count').text ("Showing "+cnt+" of "+DBTableEditor.data.rows.length+" rows");
};

DBTableEditor._ids_ ={};
DBTableEditor.newId = function(id){
  var newid=id;
  while(!newid || DBTableEditor._ids_[newid]){
    newid = Math.floor(Math.random() * 100000)*100000;}
  DBTableEditor._ids_[newid]=true;
  return newid;
};

DBTableEditor.popPendingSaves = function(n){
  if(n==null) n = 1;
  var rtn, it;
  if(n == 1)
    rtn = DBTableEditor.modifiedRows.pop();
  else {
    rtn=[];
    // while we havent popped enough and there are things to pop
    while(n-->0 && (it=DBTableEditor.modifiedRows.pop())) rtn.push(it);
  }
  jQuery('.pending-save-count').text(DBTableEditor.modifiedRows.length);
  return rtn;
};

DBTableEditor.clearPendingSaves = function(){
  DBTableEditor.modifiedRows = [];
  jQuery('.pending-save-count').text(DBTableEditor.modifiedRows.length);
};
DBTableEditor.addPendingSave = function(args){
  DBTableEditor.modifiedRows.push(args);
  jQuery('.pending-save-count').text(DBTableEditor.modifiedRows.length);
};

DBTableEditor.onload = function(opts){
  //console.log('Loading db table');
  jQuery.extend(DBTableEditor, opts);
  if(!DBTableEditor.data){ return console.log("No Data for DBTableEditor");}
  var rows = DBTableEditor.data.rows;
  var columns = DBTableEditor.data.columns;
  var columnMap = DBTableEditor.columnMap = {};

  // init columns
  for( var i=0, c ; c=columns[i] ; i++){
    c.id=c.name.toLowerCase();
    c.field = i;
    c.sortable = true;
    //account for buttons column at 0 if needed
    columnMap[c.id] = i; //DBTableEditor.noedit ? i : i+1;

    if(c.id!="id" && !c.editor){
      if(c.id.search('date')>=0){
        c.editor = Slick.Editors.Date;
      }
      var maxLen = 0;
      for(var j=0 ; j < 100 ; j++){
        if(rows[j] && rows[j][c.field]){
          maxLen = Math.max(rows[j][c.field].toString().length, maxLen);
        }
        else{
          // console.log(j, rows[j], c.field, rows[j][c.field]);
        }
      }
      if(maxLen < 65) c.editor = Slick.Editors.Text;
      else c.editor = Slick.Editors.LongText;
    }
  }
  if(columnMap['id']==null) DBTableEditor.noedit = true;
  if(!DBTableEditor.noedit)
    columns.unshift({id: 'buttons', formatter:DBTableEditor.rowButtonFormatter, width:75});

  //init rows
  for(var i=0, r ; r=rows[i] ; i++){
    // r.shift(null);
    var rid = DBTableEditor.newId((columnMap["id"]!=null) && r[columnMap["id"]]);
    r["id"] = rid;
    if(!DBTableEditor.noedit) r.push(null);
  }

  var options = {
    enableCellNavigation: true,
    enableColumnReorder: true,
    editable: true,
    enableAddRow: true,
    multiColumnSort:true,
    autoEdit:false,
    editCommandHandler: DBTableEditor.queueAndExecuteCommand,
    showHeaderRow: true,
    headerRowHeight: 30,
    defaultColumnWidth:120,
    explicitInitialization: true
  };
  DBTableEditor.columnFilters = DBTableEditor.columnFilters || {};
  var dataView = DBTableEditor.dataView = new Slick.Data.DataView({ inlineFilters: true });
  var grid = DBTableEditor.grid = new Slick.Grid('.db-table-editor', dataView, columns, options);
  grid.setSelectionModel(new Slick.CellSelectionModel());
  var nextCell = function (args){
    var ri = args.row === null ? rows.length-1 : args.row,
        ci = args.cell=== null ? 1 : args.cell + 1 ;
    if(ci >= columns.length){
      ci=0;
      ri++;
    }
    //console.log("going to:", ri, ci, args);
    grid.gotoCell(ri, ci, true);
  };

  DBTableEditor.clearPendingSaves();
  grid.onAddNewRow.subscribe(function (e, args) {
    var item = args.item;
    grid.invalidateRow(rows.length);
    item.id = DBTableEditor.newId();
    dataView.addItem(item);
    grid.updateRowCount();
    grid.render();
    DBTableEditor.addPendingSave(args);
    DBTableEditor.mostRecentEdit = new Date();
    nextCell(args);
  });

  grid.onCellChange.subscribe(function(e, args){
    var item = args.item;
    //console.log('edit', e, args, item);
    DBTableEditor.addPendingSave(args);
    DBTableEditor.mostRecentEdit = new Date();
    nextCell(args);
  });

  grid.onSort.subscribe(function(e, args){ // args: sort information.
    var cols = args.sortCols;
    var typedVal = function(c, r, n){
      var v = r[n];
      if(c.type == 'int') return Number(v);
      else if(c.id.search('date')>=0) return new Date(v);
      return v && v.toLowerCase();
    };
    var rowSorter = function (r1, r2) {
      for (var c, i=0; c=cols[i]; i++) {
        var field = c.sortCol.field;
        var sign = c.sortAsc ? 1 : -1;
        var value1 = typedVal(c.sortCol,r1,field),
            value2 = typedVal(c.sortCol,r2,field);
        var result = (value1 == value2 ? 0 : (value1 > value2 ? 1 : -1)) * sign;
        if (result != 0) {
          return result;
        }
      }
      return 0;
    };
    dataView.sort(rowSorter);
    grid.invalidate();
    grid.render();
  });

  dataView.onRowCountChanged.subscribe(function (e, args) {
    grid.updateRowCount();
    grid.render();
    DBTableEditor.updatePagingInfo();
  });

  dataView.onRowsChanged.subscribe(function (e, args) {
    grid.invalidateRows(args.rows);
    grid.render();
    DBTableEditor.updatePagingInfo();
  });

  jQuery(grid.getHeaderRow()).delegate(":input", "change keyup", function (e) {
    var columnId = jQuery(this).data("columnId");
    if (columnId != null) {
      DBTableEditor.columnFilters[columnId] = jQuery.trim(jQuery(this).val());
      dataView.refresh();
    }
  });

  grid.onHeaderRowCellRendered.subscribe(function(e, args) {
      jQuery(args.node).empty();
      if(args.column.id == "buttons") return;
      jQuery("<input type='text'>")
         .data("columnId", args.column.id)
         .val(DBTableEditor.columnFilters[args.column.id])
         .attr('name','filter-'+args.column.id)
         .appendTo(args.node);
  });

  grid.init();
  if(columns.length < 8) grid.autosizeColumns();
  dataView.beginUpdate();
  dataView.setItems(rows);
  dataView.setFilter(DBTableEditor.filterRow);
  dataView.endUpdate();
  dataView.refresh();
  DBTableEditor.updatePagingInfo();

  jQuery('button.save').attr("disabled", null);


  //console.log('Finished loading db table');
};
