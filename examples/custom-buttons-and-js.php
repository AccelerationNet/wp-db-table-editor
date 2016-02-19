<?php /*
* An example showing how to create a grid with custom javascript and buttons
*
*
       */

if(function_exists('add_db_table_editor')){
  add_db_table_editor(array(
    'title'=>'Reports',
    'table'=>'reports',
    'no_edit'=>true,
    'auto_date'=>false,
  ));
  add_db_table_editor(array(
    'title'=>'Members',
    'table'=>'memberdata',
    'sql'=>'SELECT * FROM memberdata ORDER BY ID desc',
    'auto_date'=>true,
  ));
  function xxx_register_scripts_admin (){
    $base_url = get_stylesheet_directory_uri();
    wp_enqueue_script(
      'custom-buttons.js', $base_url . '/custom-buttons.js', Array('jquery'));
  }

  add_action('admin_enqueue_scripts','xxx_register_scripts_admin');
}
