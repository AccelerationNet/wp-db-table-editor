<?php
/**
 * Classes to enable wp-db-table-editor to work
 *
 * @package wp-db-table-editor
 */

use PHPSQL\Parser;
use PHPSQL\Creator;

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
  if(is_array($where)) $where = implode(' AND ', $where);

  $sqlparser = new Parser();
  $sqlcreator = new Creator();
  $parsed = $sqlparser->parse($sql);
  // a bit of a hack because we are abusing the constant node, but whatever
  $whereExp = Array("expr_type"=>"const", "base_expr"=>$where, "sub_tree"=>null);
  if(!@$parsed['WHERE']) $parsed['WHERE'] = Array();
  else $whereExp['base_expr']=' AND ('.$whereExp['base_expr'].')';
  $parsed['WHERE'][]=$whereExp;
  $sql = $sqlcreator->create($parsed);
  return $sql;
}



class DBTE_DataTable {
  var $rows,$columns, $columnNames, $totalRows, $offset, $page_size, $page_idx;
  function  __construct($args=null){
    global $wpdb;
    $args = wp_parse_args($args);
    $sql = @$args['sql'];
    $where = @$args['where'];
    $this->page_size = @$args['page_size'];
    $this->page_idx = @$args['page_idx'];
    $limit = $this->page_size;
    $offset = $this->page_size * $this->page_idx;
    
    if($sql){
      $sql = preg_replace('/SELECT/i', 'SELECT SQL_CALC_FOUND_ROWS', $sql, 1);
      if($where){
        $sql = insert_where ($sql, $where);
      }
      $haslimit = preg_match('/limit\s+\d+/i', $sql);
      if(!$haslimit){
        $sql .= ' LIMIT '.$limit.' OFFSET '.$offset;
      }
      $this->rows = $wpdb->get_results($sql, ARRAY_N);
      $this->offset = $offset;
      if(!@$args['columns']){
        $this->columnNames = $cnames = $wpdb->get_col_info('name');
        $ctypes = $wpdb->get_col_info('type');
        $this->columns = Array();
        for($i=0; $i < count($cnames) ; $i++){
          $this->columns[]=Array('name'=>$cnames[$i], 'type'=>$ctypes[$i]);
        }
      }

      $this->totalRows = intval($wpdb->get_var('SELECT FOUND_ROWS();'));
    }else if(@$args['rows']){
      $this->rows = $args['rows'];
    }
    if(@$args['columns']){
      $this->columns = $args['columns'];
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
    $noedit, $nodelete, $noinsert, $editcap, $noedit_columns, $hide_columns, $default_values,
      $columnFilters, $columnNameMap, $save_cb, $insert_cb, $update_cb, $delete_cb,
      $id_column, $auto_date, $async_data, $page_size, $page_idx, $offset;
  function __construct($args=null){
    $args = wp_parse_args($args, array('cap'=>'edit_others_posts'));
    foreach($args as $k => $v) $this->{$k} = $v;
    if(!$this->id) $this->id = $this->table;
    if(!$this->title) $this->title = $this->table;
    if(!$this->id_column) $this->id_column = 'id';
    if(!$this->page_size) $this->page_size = 10000;
    if(!$this->page_idx) $this->page_idx = rval('dbte_page_num');
    if(!$this->page_idx) $this->page_idx = 0;
    if(!$this->offset) $this->offset = $this->page_size * $this->page_idx;
    if(is_null($this->nodelete)) $this->nodelete = $this->noedit;
    if(is_null($this->noinsert)) $this->noinsert = $this->noedit;
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
    $args['page_idx'] = $this->page_idx;
    $args['page_size'] = $this->page_size;
    $args['offset'] = $this->offset;
    
    $this->data = new DBTE_DataTable($args);
    return $this->data;
  }
}
