<?php
/*
Plugin Name: DB-table-editor
Plugin URI: http://github.com/AcceleratioNet/wp-db-table-editor
Description: A plugin that adds "tools" pages to edit database tables
Version: 1.5.3
Author: Russ Tyndall @ Acceleration.net
Author URI: http://www.acceleration.net
Text Domain: wp-db-table-editor
Domain Path: /languages

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

include('SplClassLoader.php');
$loader = new SplClassLoader('PHPSQL', 'php-sql-parser/src/');
$loader->register();
include('DBTableEditor.class.php');


add_action('init','db_table_editor_init');
function db_table_editor_init(){
    /* TODO: could be used to pull current config from the db if needed */
    // this is mostly not needed, but allows a somewhat clearer
    // place to hook this from a plugin
    do_action('db_table_editor_init');
}

function wp_db_load_plugin_textdomain() {
    load_plugin_textdomain( 'wp-db-table-editor', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'wp_db_load_plugin_textdomain' );

/*
 * Gets the DBTE_DataTable of the current DBTE instance
 */
function dbte_get_data_table(){
  global $wpdb;
  $cur = dbte_current();
  if(!$cur){ return "null"; }
  $data = $cur->getData(array('type'=>ARRAY_N));
  if(!is_a($data, "DBTE_DataTable")) echo __('DB-Table-Editor Cannot READ DATA SOURCE', 'wp-db-table-editor');
  $rows = $data->rows;
  $columns = $data->columns;
  return Array('columns'=>$columns, 'rows'=>$rows);
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
  $tbl = preg_replace('/^.*_page_dbte_/', '', $hook);
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
                    array('slick-core-js', 'jquery-ui-datepicker'));

  wp_enqueue_style('dbte-jquery-ui-css',
    '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.21/themes/smoothness/jquery-ui.css');

  wp_enqueue_script('slick-dataview', 
    $base.'/assets/SlickGrid/slick.dataview.js',
    array('slick-core-js'));

  wp_enqueue_script('slick-grid-js', 
    $base.'/assets/SlickGrid/slick.grid.js',
    array('slick-core-js', 'slick-grid-cellrangedecorator',
          'slick-grid-cellrangeselector', 'slick-grid-cellselectionmodel',
          'slick-grid-formatters', 'slick-grid-editors', 'slick-dataview'));

  wp_enqueue_script('moment-js', 
    $base.'/assets/moment.js', 
                    array());
  wp_enqueue_script('dbte-date-editor-js', 
    $base.'/assets/dbte-date-editor.js', 
                    array('slick-grid-js', 'moment-js', 'jquery', 'jquery-ui-datepicker'));
  
  wp_enqueue_script('sprintf-js', $base.'/assets/sprintf.js');
  
  wp_register_script('db-table-editor-js', 
    $base.'/assets/db-table-editor.js', 
                    array('slick-grid-js', 'jquery', 'json2', 'moment-js', 'dbte-date-editor-js', 'sprintf-js'));
  $translation_array = array(
    'row_count' => __( 'Showing %d of %d rows - items with unsaved changes are not filtered', 'wp-db-table-editor' ),
    'confirm_delete_row' => __( 'Are you sure you wish to remove this row', 'wp-db-table-editor' ),
    'delete_button' => __( 'Delete this Row', 'wp-db-table-editor' )
  );
  wp_localize_script( 'db-table-editor-js', 'translations', $translation_array );
  wp_enqueue_script('db-table-editor-js');

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
  if($cur && ($cur->id == $tbl || !$tbl)) return $cur;
  if($tbl){
    foreach($DBTE_INSTANCES as $o){
      if($tbl == $o->id) $cur = $DBTE_CURRENT = $o;
    }
    if(!$cur && function_exists('dbte_create_'.$tbl)){
      $cur = $DBTE_CURRENT = call_user_func('dbte_create_'.$tbl);
    }
  }
  return $cur;
}

function dbte_shortcode($args){
  $id = @$args["id"];
  $o="";
  if(!is_admin()){
    $url = admin_url('admin-ajax.php');
    $o.='<script type="text/javascript">var ajaxurl = "'.$url.'";</script>';
  }
  if($id){
    dbte_scripts($id);
    $o .= dbte_render($id);
  }
  return $o;
}
add_shortcode('dbte','dbte_shortcode');



function echo_dbte_render(){
  echo dbte_render();
}
/*
 * Renders the DBTE page for the current instance
 */
function dbte_render($id=null){
  $cur = dbte_current($id);
  if(!$cur){
    return __('No Database Table Configured to Edit', 'wp-db-table-editor');
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
    $pendingSaveHeader = '<div class="pending-save-header">'.sprintf(__('There are %s unsaved changes', 'wp-db-table-editor'), $pendingSaveCnt).'</div>';
    $saveButtonLabel = sprintf(__('Save %s Changes', 'wp-db-table-editor'), $pendingSaveCnt);
    $newButtonLabel = __('New', 'wp-db-table-editor');
    $undoButtonLabel = __('Undo', 'wp-db-table-editor');
    $buttons = <<<EOT
    <button class="save" onclick="DBTableEditor.save();"><img src="$base/assets/images/accept.png" align="absmiddle">$saveButtonLabel</button>
    <button onclick="DBTableEditor.gotoNewRow();"><img src="$base/assets/images/add.png" align="absmiddle">$newButtonLabel</button>
    <button onclick="DBTableEditor.undo();"><img src="$base/assets/images/arrow_undo.png" align="absmiddle">$undoButtonLabel</button>
EOT;
  }
  $dataUrl = null;
  if($cur->async_data){
      $dataUrl = admin_url( 'admin-ajax.php')."?".$_SERVER["QUERY_STRING"]
               .'&action=dbte_data&table='.$cur->id;
  }
           
  $args = Array(
    "baseUrl"=>$base,
    "data" => $cur->async_data ? null : dbte_get_data_table(),
    "dataUrl" => $dataUrl
  );
  // copy all DBTE slots to the json array
  foreach($cur as $k => $v) { $args[$k] = $v; }
  unset($args['sql']);
  $json = json_encode($args);
  $loadingLabel = __('Loading data...', 'wp-db-table-editor');
  $exportButtonLabel = __('Export to CSV', 'wp-db-table-editor');
  $clearFiltersButtonLabel = __('Clear Filters', 'wp-db-table-editor');
  $rowCountLabel = __('Showing 0 of 0 rows', 'wp-db-table-editor');
  $confirmationMessage = __('You have unsaved data, are you sure you want to quit', 'wp-db-table-editor');
  $o = <<<EOT
  <div class="dbte-page">
    <h1>$cur->title</h1>
    $pendingSaveHeader
    <span class="status">
      <span class="loading" style="display:none;">
        <img src="$base/assets/images/loading.gif" />
        $loadingLabel
      </span>
      <span class="text"></span></span>
    <div class="db-table-editor-buttons">
    <button class="export" onclick="DBTableEditor.exportCSV();"><img src="$base/assets/images/download.png" align="absmiddle">$exportButtonLabel</button>
    <button class="clear" onclick="DBTableEditor.clearFilters();">
      <img src="$base/assets/images/clear.png" align="absmiddle">
      $clearFiltersButtonLabel</button>
    $buttons
    </div>
    <div class="db-table-editor-row-count" >$rowCountLabel</div>
    <div class="db-table-editor"></div>
    <script type="text/javascript">
jQuery(function(){
    DBTableEditor.onload($json);
});

if(window.addEventListener)
window.addEventListener("beforeunload", function (e) {
  if(DBTableEditor.modifiedRows.length == 0) return; 
 var confirmationMessage = "$confirmationMessage";
  (e || window.event).returnValue = confirmationMessage;     //Gecko + IE
  return confirmationMessage;                                //Webkit, Safari, Chrome etc.
});   
       </script>
  </div>
EOT;
  return $o;
}

/*
 * Creates all the menu items based on each of the $DBTE_INSTANCES
 * puts them in a DB Table Editor menu on admin
 */
function dbte_menu(){
  global $DBTE_INSTANCES;
  $ico = plugins_url('wp-db-table-editor/assets/images/database_edit.png');
  add_menu_page(__('DB Table Editor', 'wp-db-table-editor'),
                __('DB Table Editor', 'wp-db-table-editor'),
                'read', 'wp-db-table-editor',
                'dbte_main_page', $ico, 50);

  $displayed = 0;
  foreach($DBTE_INSTANCES as $o){
    $cap = $o->cap;
    // shouldnt be null, but lets be defensive
    if(!$cap) $cap = 'edit_others_posts';
    if(current_user_can($cap)) $displayed++;
    add_submenu_page('wp-db-table-editor', $o->title, $o->title, $cap, 'dbte_'.$o->id, 'echo_dbte_render' );
  }
  if(!$displayed){
    remove_menu_page('wp-db-table-editor');
  }

}
add_action('admin_menu', 'dbte_menu');

/*
 * A page for the main menu. Currently just has links to each interface
 * and a bit of explanitory text
 */
function dbte_main_page(){
  global $DBTE_INSTANCES;
  $pluginDescription = sprintf(__('This plugin allows viewing, editing, and exporting of database tables in your wordpress database through the admin interface.  See the %sREADME.md%s for information on configuring this plugin.', 'wp-db-table-editor'),
    '<a href="https://github.com/AccelerationNet/wp-db-table-editor/blob/master/README.md">',
    '</a>');
  $configuredDBTablesTitle = __('Configured Database Tables', 'wp-db-table-editor');
  echo <<<EOT
<h2>DB Table Editor</h2>
<a href="https://github.com/AccelerationNet/wp-db-table-editor/"><h4>
   Github - DB Table Editor</h4></a>
<p style="max-width:600px;">
  $pluginDescription
</p>
<h3> $configuredDBTablesTitle </h3>
<ul>

EOT;
  foreach($DBTE_INSTANCES as $o){
    $cap = $o->cap;
    // shouldnt be null, but lets be defensive
    if(!$cap) $cap = 'edit_others_posts';
    if(current_user_can($cap))
      echo "<li><a href=\"admin.php?page=dbte_$o->id\">$o->title</a></li>";
  }
  echo "</ul>";
}

add_action( 'wp_ajax_dbte_data', 'dbte_get_data' );
add_action( 'wp_ajax_no_priv_dbte_data', 'dbte_get_data' );
function dbte_get_data(){
  $tbl= $_REQUEST['table'];
  $cur = dbte_current($tbl);
  if(!$cur) return;
  $cap = $cur->cap;
  // shouldnt be null, but lets be defensive
  if(!$cap) $cap = 'edit_others_posts';
  if(!current_user_can($cap)){
      header('HTTP/1.0 403 Forbidden');
      echo 'You are forbidden!';
      die();
  }
  $data = dbte_get_data_table();
  header('Content-type: application/json');
  echo json_encode($data);
  die(); 
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
  $cols = @$d["columns"];
  $rows = @$d["rows"];
  $idxs = @$d["modifiedIdxs"];
  $len = count($cols);
  $id_col = $cur->id_column;
  $no_edit_cols = $cur->noedit_columns;
  if(is_string($no_edit_cols)) $no_edit_cols=explode(',',$no_edit_cols);

  $idIdx = 0; 
  $i=0;
  foreach($cols as $c){
    if($c==$id_col) $idIdx = $i;
    $i++;
  }

  $i=0;
  $ridx = 0;
  $new_ids = Array();
  foreach($rows as $r){
    $id=@$r[$idIdx];
    $up = array();
    for($i=0 ; $i < $len ; $i++) {
      if($i != $idIdx && array_key_exists($i, $r)){
        $v = $r[$i];
        $up[$cols[$i]] = $v;
      }
    }
    if($no_edit_cols) foreach($no_edit_cols as $noedit){
      unset($up[$noedit]);
    }


    if($cur->save_cb){
        $isinsert = $id===null;
        $data = Array('table'=>$cur,
                      'update'=>$up,
                      'columns'=>$cols,
                      'indexes'=>$idxs[$ridx],
                      'id'=>$id,
                      'isinsert'=>$isinsert);
        call_user_func_array($cur->save_cb, array(&$data));
        if($isinsert && $data['id']){
            $ids= Array('rowId'=>@$r["id"], 'dbid'=>$wpdb->insert_id);
            if(!@$ids['rowId']) $ids['rowId'] = @$r["rowId"];
            $new_ids[] = $ids;
        }
        do_action('dbte_row_saved', array(&$data));
    }
    else if($id != null){
      if($cur->update_cb){
        call_user_func($cur->update_cb,$cur, $up, $cols, $idxs[$ridx], $id);
      }
      else{
        $where = array($id_col=>$id);
        $wpdb->update($cur->table, $up , $where);
      }
      do_action('dbte_row_updated', $cur, $up, $cols, $idxs, $id);
    }
    else{
      if($cur->insert_cb){
        call_user_func($cur->insert_cb,$cur, $up, $cols, $idxs[$ridx]);
      }
      else{
        $wpdb->insert($cur->table, $up);
        $ids= Array('rowId'=>@$r["id"], 'dbid'=>$wpdb->insert_id);
        if(!@$ids['rowId']) $ids['rowId'] = @$r["rowId"];
        $new_ids[] = $ids;
      }
      do_action('dbte_row_inserted', $cur, $up, $cols, $idxs);
    }
    $ridx++;
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
  $id_col = $cur->id_column;
  if($cur->delete_cb){
    call_user_func($cur->delete_cb,$cur,$id);
  }
  else{
    $wpdb->delete($cur->table, array($id_col=>$id));
  }
  do_action('dbte_row_deleted', $cur, $id);
  header('Content-type: application/json');
  echo json_encode(Array('deleted',$id));
  die();
}
add_action( 'wp_ajax_dbte_delete', 'dbte_delete_cb' );


function dbte_is_date($ds){
  $d = date_parse($ds);
  if($d && !@$d['errors']) return $d;
  return null;
}

// Here is an adaption of the above code that adds support for double
// quotes inside a field. (One double quote is replaced with a pair of
// double quotes per the CSV format). - http://php.net/manual/en/function.fputcsv.php
function x8_fputcsv($filePointer,$dataArray,$delimiter,$enclosure){
  // Write a line to a file
  // $filePointer = the file resource to write to
  // $dataArray = the data to write out
  // $delimeter = the field separator
  
  // Build the string
  $string = "";
  
  // No leading delimiter
  $writeDelimiter = FALSE;
  foreach($dataArray as $dataElement) {
    // Replaces a double quote with two double quotes
    $dataElement=str_replace($enclosure, $enclosure.$enclosure , $dataElement);
    if($writeDelimiter) $string .= $delimiter;
    $string .= $enclosure . $dataElement . $enclosure;
    $writeDelimiter = TRUE;
  }
  
  $string .= "\r\n";
  // Write the string to the file
  fwrite($filePointer,$string);
}

/*
 * Written as an ajax handler because that was easy, but we usually just link to here
 * will export filtered results using any filter-{columnname} request parameters provided
 */
function dbte_export_csv(){
  global $wpdb;
  $cur = dbte_current(@$_REQUEST['table']);
  if(!$cur) return;
  if($cur->editcap && !current_user_can($cur->editcap)) return;
  $tbl = $cur->table;

  $wheres = array();
  $filtered=false;
  foreach($_REQUEST as $k=>$v){
    if(strpos($k, "filter-")===0){
      $k = str_replace('filter-','', $k);
      if($cur->auto_date && dbte_is_date($v)) $wheres[] = $wpdb->prepare("$k = %s", $v);
      else $wheres[] = $wpdb->prepare("$tbl.$k LIKE %s", '%'.$v.'%');
      $filtered = true;
    }
  }

  $title = $cur->title;
  if($filtered) $title .= '-filtered';
  $data = $cur->getData(array("where"=>$wheres));
  $columns = $data->columnNames;
  $rows = $data->rows;
  header('Content-Type: application/excel');
  header('Content-Disposition: attachment; filename="'.$title.'.csv"');
  $fp = fopen('php://output', 'w');
  x8_fputcsv($fp, $columns, ',', '"');
  foreach ( $rows as $row ){
    x8_fputcsv($fp, $row, ',', '"');
  }
  fclose($fp);
  die();
}
add_action( 'wp_ajax_dbte_export_csv', 'dbte_export_csv' );
