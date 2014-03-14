<?php
function add_db_table_editor($args=null){
  global $DBTE_INSTANCES;
  $o = new DBTableEditor($args);
  $DBTE_INSTANCES[] = $o;
  return $o;
}
class DBTE_DataTable {
  var $rows,$columns, $columnNames;
  function DBTE_DataTable($args=null){
    global $wpdb;
    $args = wp_parse_args($args);
    if(@$args['sql']){
      $this->rows = $wpdb->get_results($args['sql'], ARRAY_N);
    }else if(@$args['rows']){
      $this->rows = $args['rows'];
    }
    if(@$args['columns']){
      $this->columns = $args['columns'];
    }
    else{ // handle building columns from wpdb
      $this->columnNames = $cnames = $wpdb->get_col_info('name');
      $ctypes = $wpdb->get_col_info('type');
      $this->columns = Array();
      for($i=0; $i < count($cnames) ; $i++){
        $this->columns[]=Array('name'=>$cnames[$i], 'type'=>$ctypes[$i]);
      }
    } 
  }
}
class DBTableEditor {
  var $table, $title, $sql, $dataFn, $id, $data, $cap, $jsFile, $noedit, $editcap;
  function DBTableEditor($args=null){
    $args = wp_parse_args($args, array('cap'=>'edit_others_posts'));
    $this->table=@$args['table'];
    $this->id = @$args['id'];
    if(!$this->id) $this->id = $this->table;
    $this->title = @$args['title'];
    if(!$this->title) $this->title = $this->table;
    $this->dataFn = @$args['dataFn'];
    $this->sql = @$args['sql'];
    $this->cap = @$args['cap'];
    $this->editcap = @$args['editcap'];
    $this->jsFile = @$args['jsFile'];
    $this->noedit = @$args['noedit'];
  }
  function getData($args=null){
    $fn = $this->dataFn;
    $sql = $this->sql;
    if($sql){
      $this->data = new DBTE_DataTable(Array("sql"=>$sql));
    }
    else if($fn){
      $this->data = new DBTE_DataTable(Array("rows"=>$fn($args)));
    }
    else{
      $this->data = new DBTE_DataTable(Array("sql"=>"SELECT * FROM $this->table;"));
    }
    return $this->data;
  }
}