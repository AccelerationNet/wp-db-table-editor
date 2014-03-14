# WP-DB-Table-Editor

This is a wordpress plugin that allows direct excel-like editing of
tables in your wordpress database.  

It supports:
 * one table per admin screen, as many admin screens as desired
 * excel spreadsheet like interface using SlickGrid
 * Add / Edit / Delete
 * Custom buttons extensibility
 * Custom permissions per interface (defaults to: edit_others_posts)

## Adding an interface

DB-Table Editor Interfaces are added by calling the
add_db_table_editor function in your theme's `functions.php` file.
This supports `wp_parse_args` style arguments. 

 * `title`: what shows up in the H1 on the screen and in menues
 * `table`: the table we wish to display / edit
 * `id`: the admin interface id (defaults to table)
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

Example:
```
if(function_exists('add_db_table_editor')){
  add_db_table_editor('title=Employees&table=employees&'.
    'dataFn=get_employees&jsFile=employees-table-extensions-js');
}
```

## Hooks / Actions

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

## Caveats

 * Database tables are expected to have a column names `id` that is
   the primary key
 * There is only one permission per interface, anyone who sees it can edit
