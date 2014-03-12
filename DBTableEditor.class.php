<?php
function add_db_table_editor($args){
  global $DBTE_INSTANCES;
  $o = new DBTableEditor($args);
  $DBTE_INSTANCES[] = $o;
  return $o;
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
    $this->data = $fn($args);
    return $this->data;
  }
}