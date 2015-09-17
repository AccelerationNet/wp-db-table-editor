<?php 
/* This is not a fully runnable example, but should give good examples of
    * working cf7dbsubmit plugin
    * Custom save delete hooks
    * custom complex sql building with this plugin
    * sending notifications on edit of specific fields

   cf7dbsubmit stores its data in a "hashtable" format of:
     form, submit_time, field_name, field_value
 
   but we want to present this in a more excel fasion of each field
   being a column of our spreadsheet and each row being a different 
   submission
 */

// A function to turn the wp_cf7dbplugin_submits hash table style db table
// into a grid of data for the editor plugin
function xxx_contacts_sql($formname, $wheres=null, $limit=null){
  global $wpdb;

  // Find all the fields that appear on the form
  $sql=<<<EOT
    SELECT 
      DISTINCT field_name
    FROM wp_cf7dbplugin_submits 
    WHERE form_name ='$formname' 
    ORDER BY field_order ASC
EOT;
  $fields = $wpdb->get_col($sql);
  $selects = ARRAY();
  $joins = ARRAY();

  // Do many self joins to the same table to pull up each field as a result column
  foreach($fields as $f){
    $selects[] = " `tbl_$f`.field_value `$f` ";
    $joins[] = <<<EOT
   LEFT JOIN (
     SELECT submit_time, GROUP_CONCAT(DISTINCT field_value SEPARATOR ', ') as field_value
      FROM wp_cf7dbplugin_submits
     WHERE form_name='$formname' AND field_name='$f'
     GROUP BY submit_time, form_name, field_name
   ) as `tbl_$f` ON `tbl_$f`.submit_time = submits.submit_time
EOT;

  }

  // Build the final SQL that joins to our table for each field on the 
  // contact form
  $selects = implode($selects, ", ");
  if($selects) $selects .= ", ";
  $joins = implode($joins, "\n");
  $sql = <<<EOT
    SELECT  FROM_UNIXTIME(submits.submit_time) `Submit Time`, $selects rc as id, submits.submit_time
    FROM (
      SELECT  null as submit_time, (SELECT @ROW:=0) as rc
      UNION
      SELECT submit_time, (@ROW :=@ROW + 1)  AS row
        FROM (
          SELECT DISTINCT submit_time
          FROM wp_cf7dbplugin_submits
          WHERE form_name ='$formname'
        ) as tbl
    ) as submits
    $joins
    WHERE submits.submit_time IS NOT NULL
    $wheres
    ORDER BY submits.submit_time DESC
    $limit

EOT;
  //    echo $sql; die();
  return $sql;
}


if(function_exists('add_db_table_editor')){
  $base = Array(
    'table'=>'wp_cf7dbplugin_submits',
    'save_cb'=>'xxx_contacts_save',
    'delete_cb'=>'xxx_contacts_delete',
    'hide_columns'=>"id",
    'cap'=>"edit_others_posts",
    'noedit_columns'=>'Submitted Login,Submitted From,Submit Time');

  // Configure the db-table-editor plugin for displaying the results of a single 
  // contact form
  add_db_table_editor(array_merge(Array(
      'id'=>'MoreInfoRequests',
      'title'=>'More Info Requsts',
      'sql' => xxx_contacts_sql('MoreInfoRequests')),
    $base));
}

// When inserting a new row, we need to convert it from a row
// into a more "hashtable" style database schema (that is, one 
// row in the db for each "column" in our incoming dataset
function xxx_contacts_save($args){
  global $wpdb;
  $dbte = $args['table'];
  $columns = $args['columns'];
  $columns = $args['columns'];
  $id = $dbte->id;

  $cs = implode($columns, ', ');
  $is = implode($idxs, ', ');
  $isinsert = $args['id'] === null;
  $subtime = @$vals["submit_time"];
  unset($vals["submit_time"]);
  unset($vals["Submit Time"]);
  if($isinsert) $subtime = function_exists('microtime') ? microtime(true) : time();

  foreach($vals as $k => $v){
    // our column was not edited continue
    if(!$isinsert && !in_array(array_search($k, $columns),$idxs)) continue;
    $rc = $wpdb->update('wp_cf7dbplugin_submits',
            array('field_value'=>$v),
            array('form_name'=>$id, 'submit_time'=>$subtime,
                  'field_name'=>$k));
  }
}

// Delete all the database rows for this submission
function xxx_contacts_delete($dbte, $id){
  global $wpdb;
  $id = $dbte->id;
  $subtime = @$_REQUEST["submit_time"];
  $wpdb->delete('wp_cf7dbplugin_submits', array('form_name'=>$id, 'submit_time'=>$subtime));
}
