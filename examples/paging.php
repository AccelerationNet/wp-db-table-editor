<?php

/*

   This is an example of including a custom paging function for
   when your data sets are too large.  I suggest paging based on actual
   parameters to the query such as year / date ranges, but you can also add
   the standard page by 5000 results kind of stuff.

 */

if(function_exists('add_db_table_editor')){

  add_action( 'admin_init', 'my_dbte_init' );
  function my_dbte_init(){
    $yin = @intval($_REQUEST['year']);
    $year = date("Y");
    if($yin > 1900 && $yin  < 3000) $year = $yin;
    add_db_table_editor(array(
      'id' => 'payments',
      'title'=>'Payments for '.$Year,
      'table'=>'payments',
      // !!! DONT SQL INJECT !!! This is guaranteed to be a valid integer
      // use wpdb->prepare if you are not sure !!!
      'sql'=>'SELECT * FROM payments'.
            ' WHERE YEAR(date_entered)='.$year.
            ' ORDER BY ID date_entered DESC',
    ));

    add_action('admin_enqueue_scripts','xxx_register_scripts_admin');
    function xxx_register_scripts_admin (){
      if(@$_REQUEST['page'] == 'dbte_payments'){ // matches 'id' or 'table' above
        $base_url = get_stylesheet_directory_uri();
        wp_enqueue_script(
          'payment-paging.js', $base_url . '/payment-paging.js', Array('jquery'));
      }
    }
  }
}
