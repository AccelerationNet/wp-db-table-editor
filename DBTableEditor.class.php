<?php
function add_db_table_editor($args){
  global $DBTE_INSTANCES;
  $o = new DBTableEditor($args);
  $DBTE_INSTANCES[] = $o;
  return $o;
}
class DBTE_DataTable {
  var $rows,$columns;
  function DBTE_DataTable($sql){
    global $wpdb;
    $this->rows = $wpdb->get_results($sql, ARRAY_N);
    $cnames = $wpdb->get_col_info('name');
    $ctypes = $wpdb->get_col_info('type');
    $this->columns = Array();
    for($i=0; $i < count($cnames) ; $i++){
      $this->columns[]=Array('name'=>$cnames[$i], 'type'=>$ctypes[$i]);
    }
  }
}
class DBTableEditor {
  var $table, $title, $dataFn, $id, $data, $cap, $jsFile;
  function DBTableEditor($args){
    $args = wp_parse_args($args, array('cap'=>'edit_others_posts'));
    $this->table=@$args['table'];
    $this->id = @$args['id'];
    if(!$this->id) $this->id = $this->table;
    $this->title = @$args['title'];
    if(!$this->title) $this->title = $this->table;
    $this->dataFn = @$args['dataFn'];
    $this->cap = @$args['cap'];
    $this->jsFile = @$args['jsFile'];
  }
  function getData($args){
    $fn = $this->dataFn;
    if($fn){
      $this->data = $fn($args);
    }
    else{
      $this->data = new DBTE_DataTable("SELECT * FROM $this->table;");
    }
    return $this->data;
  }
}