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

    include_once 'vendor/autoload.php';

    // Register the custom post
    new \BvdB\Realworks\Activation();
    new \BvdB\Realworks\CustomPosts();
    new \BvdB\Realworks\Settings();

    /**
     * Register WP-CLI commando
     */ 
    if ( defined( 'WP_CLI' ) && WP_CLI ) {
        add_action( 'cli_init', function() {

            \WP_CLI::add_command( 'bvdb-import', '\BvdB\Realworks\Import' );
            \WP_CLI::add_command( 'bvdb-facebook-update', '\BvdB\Realworks\Facebook' );

        } );
    }
    