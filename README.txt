=== WP-DB-Table-Editor ===

Contributers: bobbysmith007
Donate link: https://www.acceleration.net/programming/donate-to-acceleration-net/
Tags: admin screens, database, editor
Requires at least: 3.0.0
Tested Up To: 4.1
Stable tag: trunk
License: BSD
URL: https://github.com/AccelerationNet/wp-db-table-editor/

== Description ==

This is a Wordpress plugin that allows direct excel-like editing of
tables in your Wordpress database.  It's goals are to provide useful,
simple, flexible database table admin screens.

It supports:

 * one table per admin screen, as many admin screens as desired
  * These are organized under a new "DB Table Editor" menu item
 * excel spreadsheet like interface using SlickGrid
 * Filter and Sort results
 * Add, Edit & Delete records
 * Custom buttons extensibility
 * Custom permissions per interface for viewing and editing 
   (defaults to: edit_others_posts)
  * editing defaults to the same permission as viewing if not specified
 * CSV exports of filtered grid
 * Custom primary key names (but must be a single value / column)

= Reasons and Expectations =

Previously my company had been using DB-toolkit to provide minimal
database interfaces for custom tables through the Wordpress admin.
While the configuration was cumbersome for what we were doing, it did
work and was easier than writing anything.  However, when DB-Toolkit
stopped being maintained and I couldn't find a simple, but suitable
replacement, I decided to tackle my goals more head on

Use of this plugin requires a basic knowledge of PHP, and SQL.  It was
written by a programmer to help accomplish his work and does not
currently provide admin configuration screens (instead simple function
calls in your theme's functions file are used to configure the
screens).  This was preferable to me, because my configuration is
safely in source control (a problem I had when DB-toolkit would
upgrade and lose all configuration).


== Installation ==

This is installed the same way all wordpress plugins:

 * Drop the unzipped plugin directory into your wordpress install at
   `wp-content/plugins/wp-db-table-editor`

 * Activate the plugin via the Wordpress Admin > "Plugins" menu



= Adding an interface =

DB-Table Editor Interfaces are added by calling the
add_db_table_editor function in your theme's `functions.php` file.
This supports `wp_parse_args` style arguments. 

 * `title`: what shows up in the H1 on the screen and in menues
 * `table`: the table we wish to display / edit
 * `id`: the admin interface id (defaults to table)
 * `id_column`: the column in each row that names the id for the row
 * `dataFn`: a function that returns the data to be displayed /
   edited, defaults to `select * from {table}`. This should return ARRAY_N
   through wpdb->get_results. Alternatively it may return a DBTE_DataTable;
 * `jsFile`: the name of a registered script that will be enqueued for
   this interface
 * `cap`: the capability a user needs to view/edit this interface,
    defaults to edit_others_posts
 * `editcap`: the capability required to edit the grid, if not set
    all viewers are assumed to be editors
 * `noedit`: turns off the editing abilities (same as editcap=nosuchcapability)
 * `columnFilters`: Default column filters, this is an array of column->val
   to be applied as default column fitlers when the page is loaded
 * `columnNameMap`: A map of actual column names to displayed label
 * `noedit_columns`, `hide_columns`: You may wish to hide some columns
   or prevent edit.  You may do so by setting these fields to the name
   of columns you wish hidden or uneditable (eg: the id)
 * `insert_cb`,`update_cb`, `delete_cb`: function names to be called with
   the dbte, update array, column array and modified indexes array
   `call_user_func($cur->insert_cb,$cur, $up, $cols, $idxs);`
 * `auto_date`: should columns that appear to be datetimes, be treated as such
   This is based on the columns data type
 * `autoHeight`: passes the autoHeight option to slickgrid (makes
   there not be a vertical scrollbar on the grid and instead in the
   window)

Example:

```
if(function_exists('add_db_table_editor')){
  add_db_table_editor('title=Employees&table=employees');

  add_db_table_editor(array(
    'title'=>'Event Registrations',
    'table'=>'event_registrations',
    'sql'=>'SELECT * FROM event_registrations ORDER BY date_entered DESC'
  ));

}
```

== Adding an Interface on the fly ==

If we go to look up a database table editor and we dont find it, but
there is a function named dbte_create_$tbl that matches, we will call
that function expecting it to return a dbte instance. This is useful
in situations where we may not have the data for a table editor in all
circumstances (EG: not every page has a member id, so only do it on
that particular page).

== Adding an Interface from a plugin ==

If you need to add an interface from a plugin, you should use the
`admin_menu` action with a lower than default priority.

eg: `add_action( 'admin_menu', 'my_load_tables', -10 );`

Inside of the `my_load_tables` function you would include all the
calls to add_db_table_editor


== Custom Buttons ==

Buttons can be created by pushing functions into
`DBTableEditor.extraButtons`.  Each of these is a slick grid
rowButtonFormatter and should return a string of html.

eg:
   out += fn(row, cell, value, columnDef, dataContext);



= Hooks / Actions =

 * `db-table-editor_enqueue_scripts` is an action that will be called
   after enqueueing all plugin scripts and before enqueueing `jsFile`
   (if it exists)

```
function dbTableEditorScripts(){
  wp_register_script('employee-table-extensions-js',
    get_stylesheet_directory_uri().'/employee-table.js',
    array('db-table-editor-js'));
}
  add_action('db-table-editor_enqueue_scripts', 'dbTableEditorScripts');
```

= Shortcodes =

You can use a shortcode to include a dbte interface on a wordpress
page.  Please use with care.

[dbte id=table-editor-id] - (id defaults to table)


== Caveats ==

 * Dont put an editable table editor on your public facing screens using the shortcode!

== Troubleshooting ==

 * My delete button is missing / I Can't Edit
  * You either dont have `editcap` or `id_column` is misconfigured
  * https://github.com/AccelerationNet/wp-db-table-editor/issues/5

