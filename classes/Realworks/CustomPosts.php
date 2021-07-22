<?php

    namespace BvdB\Realworks;

    class CustomPosts
    {   

        public function __construct()
        {
            add_action( 'init', array( $this, 'register_posttype' ) );
            add_action( 'init', array( $this, 'register_taxonomies' ) );
        }

        /**
         * Register the object posttype
         *
         * @return void
         */
        public function register_posttype ()
        {
            $labels = array (
                'name'                => _x( 'Aanbod', 'Post Type General Name', 'burovoordeboeg' ),
                'singular_name'       => _x( 'Aanbod', 'Post Type Singular Name', 'burovoordeboeg' ),
                'menu_name'           => __( 'Aanbod', 'burovoordeboeg' ),
                'parent_item_colon'   => __( 'Hoofd aanbod:', 'burovoordeboeg' ),
                'all_items'           => __( 'Alle aanbod', 'burovoordeboeg' ),
                'view_item'           => __( 'Toon aanbod', 'burovoordeboeg' ),
                'add_new_item'        => __( 'Voeg nieuw aanbod toe', 'burovoordeboeg' ),
                'add_new'             => __( 'Voeg nieuwe toe', 'burovoordeboeg' ),
                'edit_item'           => __( 'Bewerk aanbod', 'burovoordeboeg' ),
                'update_item'         => __( 'Update aanbod', 'burovoordeboeg' ),
                'search_items'        => __( 'Zoek aanbod', 'burovoordeboeg' ),
                'not_found'           => __( 'Niet gevonden', 'burovoordeboeg' ),
                'not_found_in_trash'  => __( 'Niet gevonden in prullenbak', 'burovoordeboeg' ),
            );

            // Rewrite URL slug
            $rewrite = array(
                'slug'                => _x( 'aanbod', 'URL slug', 'burovoordeboeg' ),
                'with_front'          => true,
                'pages'               => true,
                'feeds'               => true,
            );

            // Setup post type arguments
            $args = array(
                'label'               => __( 'Aanbod', 'burovoordeboeg' ),
                'description'         => __( '', 'burovoordeboeg' ),
                'labels'              => $labels,
                'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes', ),
                'taxonomies'          => array( '' ),
                'hierarchical'        => false,
                'public'              => true,
                'show_ui'             => true,
                'show_in_menu'        => true,
                'show_in_nav_menus'   => true,
                'show_in_admin_bar'   => true,
                'menu_position'       => 26,
                'menu_icon'           => 'dashicons-admin-multisite',
                'can_export'          => true,
                'has_archive'         => 'aanbod',
                'exclude_from_search' => false,
                'publicly_queryable'  => true,
                'rewrite'             => $rewrite,
                'capability_type'     => 'post',
            );
            
            // Register the post type
            register_post_type( 'object', $args );
        }


        /**
         * Registration of all taxonomies
         *
         * @return void
         */
        public function register_taxonomies ()
        {
            $taxonomies = array(

                // Appartement, woonhuis, etc. 
                'object_type' => array(
                    'hierarchical' => true, 
                    'label' => __('Type', 'burovoordeboeg'),
                    'public' => false,
                    'rewrite' => false,
                    'show_ui' => true
                ),

                // Koop of huur
                'object_koophuur' => array(
                    'hierarchical' => true, 
                    'label' => __('Koop of huur', 'burovoordeboeg'),
                    'public' => false,
                    'rewrite' => false,
                    'show_ui' => true,
                    'show_admin_column' => true
                ),

                // Plaats
                'object_plaats' => array(
                    'hierarchical' => true, 
                    'label' => __('Plaats', 'burovoordeboeg'),
                    'public' => false,
                    'rewrite' => false,
                    'show_ui' => true,
                    'show_admin_column' => true,
                ),

                // Wonen, Business of Nieuwbouw
                'object_soort' => array(
                    'hierarchical' => true, 
                    'label' => __('Soort', 'burovoordeboeg'),
                    'public' => false,
                    'rewrite' => false,
                    'show_ui' => true,
                    'show_admin_column' => true,
                ),

                // Status van het object
                'object_status' => array(
                    'hierarchical' => true, 
                    'label' => __('Status', 'burovoordeboeg'),
                    'public' => (( defined( 'WP_CLI' ) && WP_CLI ) ? true : false),
                    'rewrite' => false,
                    'show_ui' => true,
                    'show_admin_column' => true,
                )
            );

            // Register the taxonomies
            foreach( $taxonomies as $taxonomy => $args ) {
                register_taxonomy( $taxonomy, 'object', $args );
            }
        }

    }

?>