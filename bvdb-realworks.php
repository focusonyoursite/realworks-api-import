<?php 

/*
Plugin Name: Realworks API Import
Plugin URI: https://www.burovoordeboeg.nl
Description: Importeer objecten vanuit Realworks met de Buro voor de Boeg Realworks import
Version: 0.0.1
Author: Buro voor de Boeg
Author URI: https://www.burovoordeboeg.nl
License: none
Text Domain: bvdb-realworks
Domain Path: /languages
*/

error_reporting(E_ALL);
ini_set('display_errors', 'on');


// try
// {
//     $import = new \BvdB\Realworks\Import;

//     echo "<pre>";
//     print_r( $import->start() );
//     echo "</pre>";

// }
// catch( \Exception $e )
// {
//     echo "<pre>";
//     print_r($e);
//     echo "</pre>";
// }

// exit();

/**
 * Register WP-CLI commando
 */ 
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    add_action( 'cli_init', function() {
        include_once 'vendor/autoload.php';

        \WP_CLI::add_command( 'bvdb-import', '\BvdB\Realworks\Import' );

    } );
}
