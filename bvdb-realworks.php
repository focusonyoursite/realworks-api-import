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

use \BvdB\Realworks as Realworks;

error_reporting(E_ALL);
ini_set('display_errors', 'on');

include_once 'vendor/autoload.php';

try {
    echo "<pre>";
    print_r( new Realworks\Import() );
    echo "</pre>";
}

catch( Exception $e ) {
    echo "<pre>";
    print_r( $e );
    echo "</pre>";
}

exit();