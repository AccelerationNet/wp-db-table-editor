<?php
/*
Plugin Name: DB-table-editor
Plugin URI: http://github.com/AcceleratioNet/wp-db-table-editor
Description: A plugin that adds "tools" pages to edit database tables
Version: 1.0.0
Author: Russ Tyndall @ Acceleration.net
Author URI: http://www.acceleration.net
License: BSD

Copyright (c) 2014, Russ Tyndall, Acceleration.net
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:
    * Redistributions of source code must retain the above copyright
      notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright
      notice, this list of conditions and the following disclaimer in the
      documentation and/or other materials provided with the distribution.
    * Neither the name of the <organization> nor the
      names of its contributors may be used to endorse or promote products
      derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/

/* Variables to store the list of configred wp-db-table-editors and 
 * the currently selected instance
 */
global $DBTE_INSTANCES, $DBTE_CURRENT;
$DBTE_INSTANCES = Array();
$DBTE_CURRENT = null;

include('DBTableEditor.class.php');

/* TODO: could be used to pull current config from the db if needed
add_action('plugins_loaded','db_table_editor_init');
function db_table_editor_init(){
}
*/

/*
 * Gets the DBTE_DataTable of the current DBTE instance
 */
function dbte_get_data_table(){
  global $wpdb;
  $cur = dbte_current();
  if(!$cur){ return "null"; }
  $data = $cur->getData(array('type'=>ARRAY_N));
  if(!is_a($data, "DBTE_DataTable")) echo "DB-Table-Editor Cannot READ DATA SOURCE";
  $rows = $data->rows;
  $columns = $data->columns;
  return json_encode(Array('columns'=>$columns, 'rows'=>$rows));
}

/*
 * Enqueue all scripts and styles need to render this plugin
 *
 * we will also doaction db-table-editor_enqueue_scripts
 * so that users can enqueue their own files
 *
 * we will also enqueue the current DBTE instance's jsFile
 * which should be the name of a registered script
 */
function dbte_scripts($hook){
  global $DBTE_INSTANCES, $DBTE_CURRENT;
  $tbl = str_replace('db-table-editor_page_dbte_', '', $hook);
  $cur = dbte_current($tbl);
  if(!$cur) return;
  $base = plugins_url('wp-db-table-editor');

  wp_enqueue_style('slick-grid-css', 
    $base.'/assets/SlickGrid/slick.grid.css');
  wp_enqueue_style('slick-grid-jquery-ui', 
    $base.'/assets/SlickGrid/css/smoothness/jquery-ui-1.8.16.custom.css');
  wp_enqueue_style('db-table-editor-css', 
    $base.'/assets/db-table-editor.css');

  wp_enqueue_script('jquery-event-drag', 
    $base.'/assets/jquery.event.drag.js',
    array('jquery'));
  wp_enqueue_script('jquery-event-drop', 
    $base.'/assets/jquery.event.drop.js',
    array('jquery-event-drag'));
  wp_enqueue_script('slick-core-js', 
    $base.'/assets/SlickGrid/slick.core.js', 
    array('jquery-event-drop','jquery-event-drag', 'jquery-ui-sortable'));


  wp_enqueue_script('slick-grid-cellrangedecorator', 
    $base.'/assets/SlickGrid/plugins/slick.cellrangedecorator.js',
    array('slick-core-js'));
  wp_enqueue_script('slick-grid-cellrangeselector', 
    $base.'/assets/SlickGrid/plugins/slick.cellrangeselector.js',
    array('slick-core-js'));
  wp_enqueue_script('slick-grid-cellselectionmodel', 
    $base.'/assets/SlickGrid/plugins/slick.cellselectionmodel.js',
    array('slick-core-js'));
  wp_enqueue_script('slick-grid-formatters', 
    $base.'/assets/SlickGrid/slick.formatters.js',
    array('slick-core-js'));
  wp_enqueue_script('slick-grid-editors', 
    $base.'/assets/SlickGrid/slick.editors.js',
    array('slick-core-js'));

  wp_enqueue_script('slick-dataview', 
    $base.'/assets/SlickGrid/slick.dataview.js',
    array('slick-core-js'));

  wp_enqueue_script('slick-grid-js', 
    $base.'/assets/SlickGrid/slick.grid.js',
    array('slick-core-js', 'slick-grid-cellrangedecorator',
          'slick-grid-cellrangeselector', 'slick-grid-cellselectionmodel',
          'slick-grid-formatters', 'slick-grid-editors', 'slick-dataview'));

  wp_enqueue_script('db-table-editor-js', 
    $base.'/assets/db-table-editor.js', 
    array('slick-grid-js', 'json2'));

  do_action('db-table-editor_enqueue_scripts');
  if($cur->jsFile) wp_enqueue_script($cur->jsFile);
}
add_action('admin_enqueue_scripts','dbte_scripts');

/*
 * Looks up and sets the current table instance (by id)
 */
function dbte_current($tbl=null){
  global $DBTE_CURRENT, $DBTE_INSTANCES;
  $cur = $DBTE_CURRENT;
  if($cur) return $cur;
  if($tbl) foreach($DBTE_INSTANCES as $o){
    if($tbl == $o->id) $cur = $DBTE_CURRENT = $o;
  }
  return $cur;
}

/*
 * Renders the DBTE page for the current instance
 */
function dbte_render(){
  $cur = dbte_current();
  if(!$cur){
    echo "No Database Table Configured to Edit";
    return;
  }
  $base = plugins_url('wp-db-table-editor');
  $noedit = $cur->noedit;
  $pendingSaveCnt = "";
  $pendingSaveHeader = "";
  $buttons="";
  if($cur->editcap && !current_user_can($cur->editcap)){
    $noedit = true;
  }
  if( !$noedit ){
    $pendingSaveCnt = '<span class="pending-save-count">0</span>';
    $pendingSaveHeader = "<div class=\"pending-save-header\">There are $pendingSaveCnt unsaved changes</div>";
    $buttons = <<<EOT
    <button class="save" onclick="DBTableEditor.save();"><img src="$base/assets/images/accept.png" align="absmiddle">Save $pendingSaveCnt Changes</button>
    <button onclick="DBTableEditor.gotoNewRow();"><img src="$base/assets/images/add.png" align="absmiddle">New</button>
    <button onclick="DBTableEditor.undo();"><img src="$base/assets/images/arrow_undo.png" align="absmiddle">Undo</button>
EOT;
  }
  $noedit = $noedit ? "true" : "false";
  $data = dbte_get_data_table();
  $columnFiltersJson = json_encode($cur->columnFilters);
  $columnNameMap = json_encode($cur->columnNameMap);
  $o = <<<EOT
  <div class="dbte-page">
    <h1>$cur->title</h1>
    $pendingSaveHeader
    <div class="db-table-editor-buttons">
    <button class="export" onclick="DBTableEditor.exportCSV();"><img src="$base/assets/images/download.png" align="absmiddle">Export to CSV</button>
    <button class="clear" onclick="DBTableEditor.clearFilters();">
      <img src="$base/assets/images/clear.png" align="absmiddle">
      Clear Filters</button>
    $buttons
    </div>
    <div class="db-table-editor-row-count" >Showing 0 of 0 rows</div>
    <div class="db-table-editor"></div>
    <script type="text/javascript">
jQuery(function(){
    DBTableEditor.onload({'table':"$cur->id", "baseUrl":"$base", 'noedit':$noedit,
          "data": $data, "columnFilters":$columnFiltersJson, "columnNameMap":$columnNameMap});
});

if(window.addEventListener)
window.addEventListener("beforeunload", function (e) {
  if(DBTableEditor.modifiedRows.length == 0) return;
  var confirmationMessage = "You have unsaved data, are you sure you want to quit";
  (e || window.event).returnValue = confirmationMessage;     //Gecko + IE
  return confirmationMessage;                                //Webkit, Safari, Chrome etc.
});   
       </script>
  </div>
EOT;
  echo $o;
}

/*
 * Creates all the menu items based on each of the $DBTE_INSTANCES
 * puts them in a DB Table Editor menu on admin
 */
function dbte_menu(){
  global $DBTE_INSTANCES;
  $ico = plugins_url('wp-db-table-editor/assets/images/database_edit.png');
  add_menu_page('DB Table Editor', 'DB Table Editor', 'read', 'wp-db-table-editor',
                'dbte_main_page', $ico, 50);
  foreach($DBTE_INSTANCES as $o){
    $cap = $o->cap;
    // shouldnt be null, but lets be defensive
    if(!$cap) $cap = 'edit_others_posts';
    add_submenu_page('wp-db-table-editor', $o->title, $o->title, $cap, 'dbte_'.$o->id, 'dbte_render' );
  }

}
add_action('admin_menu', 'dbte_menu');

/*
 * A page for the main menu. Currently just has links to each interface
 * and a bit of explanitory text
 */
function dbte_main_page(){
  global $DBTE_INSTANCES;
  echo <<<EOT
<h2>DB Table Editor</h2>
<a href="https://github.com/AccelerationNet/wp-db-table-editor/"><h4>
   Github - DB Table Editor</h4></a>
<p style="max-width:600px;">
  This plugin allows viewing, editing, and exporting of database tables 
  in your wordpress database through the admin interface.  See the
  <a href="https://github.com/AccelerationNet/wp-db-table-editor/blob/master/README.md">README.md</a> 
  for information on configuring this plugin.
</p>
<h3> Configured Database Tables </h3>
<ul>

EOT;
  foreach($DBTE_INSTANCES as $o){
    echo "<li><a href=\"admin.php?page=dbte_$o->id\">$o->title</a></li>";
  }
  echo "</ul>";
}

/*
 * Ajax Save Handler, called with json rows/columns data
 */
add_action( 'wp_ajax_dbte_save', 'dbte_save_cb' );
function dbte_save_cb() {
  global $wpdb; // this is how you get access to the database
  $d = $_REQUEST['data'];
  $tbl= $_REQUEST['table'];
  $cur = dbte_current($tbl);
  if(!$cur) return;
  if($cur->noedit || ($cur->editcap && !current_user_can($cur->editcap))) return;
  // not sure why teh stripslashes is required, but it wont decode without it
  $d = json_decode(htmlspecialchars_decode(stripslashes($d)), true);


  //var_dump($d);die();
  $cols = $d["columns"];
  $rows = $d["rows"];
  $len = count($cols);

  $idIdx = 0; 
  $i=0;
  foreach($cols as $c){
    if($c=='id') $idIdx = $i;
    $i++;
  }
  // echo "id: $idIdx, "; print_r($rows);
  $i=0;
  $new_ids = Array();
  foreach($rows as $r){
    $id=@$r[$idIdx];
    $up = array();
    for($i=0 ; $i < $len ; $i++) {
      if($i != $idIdx){
        $up[$cols[$i]] = @$r[$i];
      }
    }
    if($id != null){
      $where = array('id'=>$id);
      $wpdb->update($cur->table, $up , $where);
    }
    else{
      $wpdb->insert($cur->table, $up);
      $new_ids[] = Array('rowId'=>$r["id"], 'dbid'=>$wpdb->insert_id);
    }
  }
  header('Content-type: application/json');
  echo json_encode($new_ids);
  die();
}

/*
 * Ajax Delete Handler - called from delete buttons on each row of the clickgrid
 */
function dbte_delete_cb(){
  global $wpdb;
  $id = $_REQUEST['dataid'];
  $tbl= $_REQUEST['table'];
  $cur = dbte_current($tbl);
  if(!$cur) return;
  if($cur->noedit || ($cur->editcap && !current_user_can($cur->editcap))) return;
  $wpdb->delete($cur->table, array('id'=>$id));
  header('Content-type: application/json');
  echo "{\"deleted\":$id}";
  die();
}
add_action( 'wp_ajax_dbte_delete', 'dbte_delete_cb' );

/*
 * Written as an ajax handler because that was easy, but we usually just link to here
 * will export filtered results using any filter-{columnname} request parameters provided
 */
function dbte_export_csv(){
  global $wpdb;
  $tbl= $_REQUEST['table'];
  $cur = dbte_current($tbl);
  if(!$cur) return;
  if($cur->editcap && !current_user_can($cur->editcap)) return;

  $wheres = array();
  $filtered=false;
  foreach($_REQUEST as $k=>$v){
    if(strpos($k, "filter-")===0){
      $k = str_replace('filter-','', $k);
      $wheres[] = $wpdb->prepare("$k LIKE %s", '%'.$v.'%');
      $filtered = true;
    }
  }
  $title = $cur->title;
  if($filtered) $title .= '-filtered';
  header('Content-Type: application/excel');
  header('Content-Disposition: attachment; filename="'.$title.'.csv"');
  $data = $cur->getData(array("where"=>$wheres));
  $columns = $data->columnNames;
  $rows = $data->rows;
  $fp = fopen('php://output', 'w');
  fputcsv($fp, $columns, ',', '"');
  foreach ( $rows as $row ){
    fputcsv($fp, $row, ',', '"');
  }
  fclose($fp);
  die();
}
add_action( 'wp_ajax_dbte_export_csv', 'dbte_export_csv' );