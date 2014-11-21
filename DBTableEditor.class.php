<?php
/**
 * Classes to enable wp-db-table-editor to work
 *
 * @package wp-db-table-editor
 */

/*
 * The primary entrypoint to configuring wp-db-table-editor's
 * creates a DBTableEditor instance and puts it in the global 
 * configuration array
 */
function add_db_table_editor($args=null){
  global $DBTE_INSTANCES;
  $o = new DBTableEditor($args);
  $DBTE_INSTANCES[] = $o;
  return $o;
}

  /**
   * A data table containing column objects and row arrays
   * for convenience also contains an array of columnNames
   *
   * Can be initialized by passing sql, where, or rows & columns
   * as arguments associative array
   */

function insert_where($sql, $where){
  $whereIdx = strrpos(strtolower($sql) ,'where');
  $orderIdx = strrpos(strtolower($sql) ,'order');
  //echo "$whereIdx - $orderIdx <br />";
  if(!($whereIdx === FALSE)) $where = "  (" . $where.') AND ';
  else $where = " WHERE (".$where.') ';

  if($orderIdx<0 && $whereIdx<0 || ($orderIdx===FALSE && $whereIdx===FALSE))  $sql .= $where;
  else if(!($whereIdx===FALSE)) $sql = substr_replace($sql, $where, $whereIdx+5, 0);
  else if(!($orderIdx===FALSE)) $sql = substr_replace($sql, $where, $orderIdx, 0);
  return $sql;
}

class DBTE_DataTable {
  var $rows,$columns, $columnNames;
  function DBTE_DataTable($args=null){
    global $wpdb;
    $args = wp_parse_args($args);
    $sql = @$args['sql'];
    $where = @$args['where'];
    if($sql){ 
      if($where){
        if(is_array($where)) $where = implode(' AND ', $where);
        $sql = insert_where ($sql, $where);
      }
      $this->rows = $wpdb->get_results($sql, ARRAY_N);
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

  /**
   * A class to contain the configuration state for each DBTableEditor 
   * that is available
   * @access public
   */
class DBTableEditor {
  var $table, $title, $sql, $dataFn, $id, $data, $cap, $jsFile, 
    $noedit, $editcap, $noedit_columns, $hide_columns, $default_values,
    $columnFilters, $columnNameMap, $insert_cb, $update_cb, $delete_cb,
    $id_column, $auto_date;
  function DBTableEditor($args=null){
    $args = wp_parse_args($args, array('cap'=>'edit_others_posts'));
    foreach($args as $k => $v) $this->{$k} = $v;
    if(!$this->id) $this->id = $this->table;
    if(!$this->title) $this->title = $this->table;
    if(!$this->id_column) $this->id_column = 'id';
    if(!isset($args['auto_date'])) $this->auto_date=true;
  }
  /*
   * Gets data from the data source (either sql, or dataFn (prefering sql)
   * default is to SELECT * FROM {table}
   */
  function getData($args=null){
    if(!$args)$args=Array();
    $fn = $this->dataFn;
    $sql = $this->sql;

    if($sql) $args['sql'] = $sql;
    else if($fn) $args['rows'] = $fn($args);
    else $args["sql"] ="SELECT * FROM $this->table";
    $this->data = new DBTE_DataTable($args);
    return $this->data;
  }
}