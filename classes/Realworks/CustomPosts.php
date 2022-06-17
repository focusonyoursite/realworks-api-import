<?php

    namespace BvdB\Realworks;

    class CustomPosts
    {   

        /**
         * Registred locations
         */
        public $locations = array();

        /**
         * Class construct
         */
        public function __construct()
        {
            add_action( 'init', array( $this, 'register_posttype' ) );
            add_action( 'init', array( $this, 'register_taxonomies' ) );
			
			add_action( 'manage_object_posts_columns', array( $this, 'add_meta_columns' ), 10, 1 );
			add_action( 'manage_object_posts_custom_column', array( $this, 'location_post_meta' ), 10, 2 );
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
                    'public' => true,
                    'rewrite' => array(
                        'slug'         => _x( 'overzicht', 'URL slug', 'burovoordeboeg' ),
                        'with_front'   => true,
                        'pages'        => true,
                        'feeds'        => true,
                    ),
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

        /**
         * Add meta data columns in admin overview
         *
         * @return array
         */
        public function add_meta_columns( $columns )
        {
            // Add new column at the 
            $insert_column = array('location' => 'Vestiging');
            $columns = $this->insert_column_at_position( $columns, $insert_column, 3 );

            return $columns;
        }

        /**
         * Added location post meta
         *
         * @param string $column
         * @param int $post_id
         * @return string
         */
        public function location_post_meta( $column, $post_id )
        {
            if( $column === 'location' )
            {
                // Store locations when empty
                if( empty($this->locations) )
                {
                    $this->locations = $this->get_locations();
                }

                // Set locations
                $locations = $this->locations;

                // Set the correct location value based on post meta
                $location_id = get_post_meta( $post_id, 'vestiging', true );
                
                echo $locations[$location_id] ?: '-';
            }
        }

        /**
         * Get locations from realworks settigns
         *
         * @return array
         */
        private function get_locations():array
        {
            // Get locations
            $locations = get_field('locations', 'realworks') ?: [];

            if( !empty($locations) )
            {
                // Set location store
                $location_list = array();

                // Loop locations
                foreach( $locations as $location )
                {
                    $location_list[$location['id']] = $location['title'];
                }

                // Return location list
                return $location_list;
            }

            // Always return array
            return array();
        }

        /**
         * Insert a new column at specified position
         *
         * @param array $columns
         * @param array $insert_column
         * @param integer $position
         * @return array
         */
        private function insert_column_at_position( array $columns, array $insert_column, int $position ):array 
        {
            $first_part = array_slice($columns, 0, $position, true);
            $last_part = array_slice($columns, $position, count($first_part), true);
            return array_merge( $first_part, $insert_column, $last_part );
        }



    }

?>