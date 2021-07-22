<?php

    namespace BvdB\Realworks;

    // This class is used for generating the JSON files and importing them afterwards
    class Import extends Commands
    {   
        // Data store
        protected $data = array();

        // Import type
        private $import_type = null;
        private $latest_update = null;

        // Class wrappers
        private $feed;
        private $helpers;
        private $media;
        private $meta;
        private $logs;
        private $settings;

        public function __construct()
        {
            $this->feed = new Feed();
            $this->helpers = new Helpers();
            $this->media = new Media();
            $this->meta = new Meta();
            $this->settings = new Settings();

            // Get latest update
            $this->import_type = 'latest';
            $this->latest_update = get_option('realworks_latest_update');

            // Set logs location
            $this->logs_dir = __DIR__ . '/../../logs/';
        }

        /**
         * Starting point for the import
         * 
         * @param array $args 
         * @param array $assoc_args Associative CLI args.
         * 
         */
        public function start( $args, $assoc_args )
        {
            // Set the import type to make sure options are set
            if( isset($assoc_args['import-type']) )
            {
                $this->import_type = $assoc_args['import-type'];
            }
            
            // Log import type
            \WP_CLI::line('Import type: ' . (( isset($assoc_args['import-type']) ) ? $assoc_args['import-type'] : 'Latest' ) );

            // Set the import type to make sure options are set
            if( !empty($this->latest_update) )
            {
                \WP_CLI::line('Latest import was at: ' . date('d-m-Y H:i', $this->latest_update) );
            }

            // Set notice for starting the import
            \WP_CLI::line('Starting import at: ' . date('d-m-Y H:i:s') );
            
            // Make sure data is available
            $this->getData();

            // Start list
            $this->import( $this->data );

            // Set notice for completing the import
            \WP_CLI::success('Import complete at: ' . date('d-m-Y H:i:s') );

            // Cleanup old logs
            $this->cleanLogs();

            // Finalize the import
            $this->finalizeImport();
        }


        /**
         * Method to download all latest feeds from Realworks
         * API. This is needed to start the import and saving
         * the file locally speeds this up, plus gives us the
         * possibility to do this in our own pace.
         *
         * @return array $feeds contains all feed locations
         */
        private function getData()
        {

            // Log downloading data
            \WP_CLI::line('Starting downloading JSON-feeds at: ' . date('d-m-Y H:i:s') );

            // Get active feeds
            $active_feeds = get_field('active_feeds', 'realworks');
            $feeds = array();

            // Load the feeds and gather data
            if( !empty($active_feeds) )
            {
                foreach( $active_feeds as $active_feed ) {
                    $feeds[ $active_feed ] = '';
                }

                // Check if there are any active feeds
                foreach( $feeds as $type => $feedlocation )
                {   
                    try
                    {
                        // Get the feed data
                        $file = $this->feed->getFeed( $type, $this->import_type, $this->latest_update );

                        // Check if the file exists, and if so, add data to the 
                        // global data attribute.
                        if ( file_exists($file) ) {
                            $this->data[$type] = json_decode(file_get_contents($file), 1);
                        }
                    }
                    catch( \Exception $e ) 
                    {
                        return $e;
                    }

                }

                // Log downloading data
                \WP_CLI::success('Downloading JSON-feeds complete at: ' . date('d-m-Y H:i:s') );
            }

            // No active feeds available
            else
            {
                // Log downloading data
                \WP_CLI::error('No feeds active at: ' . date('d-m-Y H:i:s') );
            }

        }
    
        /**
         * Start import process for data
         * 
         * @param array $data
         *
         * @return void
         */
        private function import( array $data ) 
        {
            // Start processing the data
            \WP_CLI::log('Start processing data at: ' . date('d-m-Y H:i:s') );

            // WP CLI function
            $this->startBulkOperation();
    
            // Array with WordPress post_ids as value
            $imported_posts = [];
    
            // TEMP:
            $i = 0;

            // Do the import
            if( !empty($data) ) 
            {
                // There are three arrays in the data variable, containing the three separate 
                // object-types. The key is the feedname and the value is the data containing the 
                // objects for importing. 
                foreach( $data as $feed => $data )
                {
                    // Log which feed is processing
                    \WP_CLI::line('Starting import of feed: ' . $feed );

                    // Get total of posts
                    $total_posts = count($data);

                    // Add progress bar
                    $progress_bar = \WP_CLI\Utils\make_progress_bar( 'Progress Bar', $total_posts );

                    // Loop objects
                    foreach( $data as $object ) {
                        
                        // Import post
                        $imported_posts[] = $this->importObject( $feed, $object );

                        // Update progress bar
                        $progress_bar->tick();
                    }

                    // Finish progress bar
                    $progress_bar->finish();

                    // End import of current feed process
                    \WP_CLI::line('Ending import of feed ' . $feed );

                }
            }

            // Start Import of media
            $this->importMedia( $imported_posts );

            // Add the latest import to options table
            add_option('realworks_latest_update', time());
            update_option('realworks_latest_update', time());
            
            // End import operation and reset all the hooks etc
            $this->endBulkOperation();

            // Log end of processing
            \WP_CLI::success('Processing succesful at: ' . date('d-m-Y H:i:s') );

        }


        /**
         * Import the object
         * Insert or update the post
         * 
         * @see: https://developer.wordpress.org/reference/functions/wp_insert_post/
         *
         * @param string $type
         * @param array $data
         * @return int $post_id
         */
        private function importObject( string $type, array $data )
        {

            // Check if there is a post with the same Realworks ID already in database
            $post_id = $this->helpers->findPostByReference( $type, $data );
            $realworks_id = $this->helpers->extractRealworksId( $type, $data );
            $establishment_id = $this->helpers->extractEstablishmentId( $type, $data );
            
            \WP_CLI::line( (( $post_id != null ) ? 'Start update of object with Post_ID: ' . $post_id : 'Insert new object for Realworks ID ' . $realworks_id ) );

            // Setup post to insert
            $post = array(
                'ID' => $post_id,
                'post_type' => 'object',
                'post_status' => 'publish',
                
                'post_title' => $this->helpers->formatObjectTitle( $type, $data ),
                'post_content' => $this->helpers->formatObjectContent( $type, $data ),

                'post_date' => $this->helpers->formatDate( $type, $data, 'insert', 'Y-m-d H:i:s' ),
                'post_modified' => $this->helpers->formatDate( $type, $data, 'modified', 'Y-m-d H:i:s' ),
                
                'meta_input' => $this->meta->formatMeta( $type, $data )
            );

            // Create the post
            $post_id = \wp_insert_post ($post);

            // Add Realworks ID
            update_post_meta( $post_id, 'realworks_id', $realworks_id );
            update_post_meta( $post_id, 'vestiging', $establishment_id );

            // Add OpenGraph data
            update_post_meta( $post_id, '_yoast_wpseo_opengraph-title', $post['post_title'] );
            update_post_meta( $post_id, '_yoast_wpseo_twitter-title', $post['post_title'] );
            update_post_meta( $post_id, '_yoast_wpseo_opengraph-description', substr(strip_tags($post['post_content']), 0, 300) . '...' );
            update_post_meta( $post_id, '_yoast_wpseo_twitter-description', substr(strip_tags($post['post_content']), 0, 300) . '...' );
            
            // Check if failed
            if( $post_id == null ) {
                \WP_CLI::warning( 'Failed importing object with ID ' . $realworks_id );
            } else {

                // Check if eligible for Facebook update
                $this->helpers->checkStatusUpdate( $post_id, $type, $data );
                
                // Import the terms
                $this->importTerms( $post_id, $type, $data );
                
                // Output success message
                \WP_CLI::success( 'Succesfully imported object with ID ' . $realworks_id . ' and ' . $post_id );
            }

            return $post_id;
        }

        /**
         * Set the object terms
         * @see: https://developer.wordpress.org/reference/functions/wp_set_post_terms/
         *
         * @param integer $post_id
         * @param string $type
         * @param array $data
         * @return void
         */
        private function importTerms( int $post_id, string $type, array $data )
        {
            // Assign terms to post
            $tax_input = array(
                'object_type' => $this->helpers->formatTermData( $type, $data, 'type' ),
                'object_plaats' => $this->helpers->formatTermData( $type, $data, 'plaats' ),
                'object_koophuur' => $this->helpers->formatTermData( $type, $data, 'koophuur' ),
                'object_status' => $this->helpers->formatTermData( $type, $data, 'status' ),
                'object_soort' => $type,
            );

            foreach( $tax_input as $taxonomy => $value )
            {   
                $terms = $this->helpers->formatObjectTerm ( $taxonomy, $value );
                
                if( !empty( $terms ) )
                {
                    // Set the object terms
                    wp_set_object_terms( $post_id, $terms, $taxonomy );

                    if( $taxonomy == 'object_status' )
                    {
                        // Set latest status
                        update_post_meta( $post_id, 'latest_status', $terms[0] );
                    }
                }
                
            }
        }

        /**
         * Start importing media items to separate folders
         *
         * @param array $post_ids
         * @return void
         */
        public function importMedia( array $post_ids = null )
        {

            if( !empty( $post_ids ) )
            {

                // Get the upload dir folder to search in
                $upload_dir = $this->media->getUploadsFolder();

                // Total amount of posts and counter
                $total_posts = count($post_ids);
                $total_counter = 1;

                // Add progress bar
                $progress_bar = \WP_CLI\Utils\make_progress_bar( 'Progress Bar', $total_posts );
                
                // Loop posts media import
                foreach( $post_ids as $post_id )
                {
                    // Start logging output
                    \WP_CLI::line('Start importing media items from post: ' . $post_id . " [$total_counter/$total_posts]");

                    // Get realworks ID
                    $realworks_id = get_post_meta( $post_id, 'realworks_id', true );

                    // Storage for post meta: media
                    $post_media = array();

                    // Setup the target folder, first check if folder exists, if not. Create it. 
                    $media_folder = $this->media->createFolder( $realworks_id );

                    // Get the media list
                    $media_list = get_post_meta( $post_id, 'media_raw', true );

                    if( !empty($media_list) )
                    {
                        // Setup media in different categories to loop through
                        $media = $this->media->formatMediaList( $media_list );

                        // Start downloading and processing images according to type
                        foreach( $media as $media_type => $media_objects )
                        {
                            // Check if there are media objects
                            if( !empty( $media_objects ) ) 
                            {
                                \WP_CLI::line('Start import ' . $media_type);

                                // Import the media objects
                                $post_media[$media_type] = $this->media->importMediaObjects( $post_id, $realworks_id, $media_type, $media_objects );
                            }
                        }
                    }

                    // Add media to post meta
                    update_post_meta($post_id, 'media', $post_media);

                    // Move progress bar
                    $progress_bar->tick();

                    // Update the count
                    $total_counter++;
                }

                // Finish progress bar
                $progress_bar->finish();

            }
            else
            {
                \WP_CLI::warning( 'No media to import' );
            }
        }

        /**
         * Remove old logs
         *
         * @return void
         */
        public function cleanLogs()
        {
            // Check if logs dir exists
            if( file_exists( $this->logs_dir ) )
            {
                // Get the files
                $loglist = array_diff( scandir( $this->logs_dir ), array( '..', '.' ) );

                // Set max date
                $max_datetime = date('Y-m-d H:i:s', strtotime('-7 days'));
                
                // Loop list when not empty
                if( !empty($loglist) )
                {
                    foreach( $loglist as $log ) 
                    {
                        // Get datetime string from filename, output: Y-m-d H:i:s
                        // Ex. 2021-04-21 14:15:00
                        $datetime = str_replace('_', ' ', rtrim(ltrim($log, 'import-'), '.log'));

                        if( strtotime($datetime) < strtotime($max_datetime) )
                        {
                            // Unlink
                            unlink( $this->logs_dir . '/' . $log );
                        }    
                    }
                }

                // Removed logs before max date
                \WP_CLI::line("Removed logs before $max_datetime");
            }
        }

        /**
         * Get all taxonomies of object type
         *
         * @return array $taxonomies
         */
        private function getTaxonomies()
        {
            $taxonomies = get_object_taxonomies('object');
            return (array) $taxonomies;
        }

        /**
         * Finalize the import and recount the terms
         *
         * @return void
         */
        private function finalizeImport() 
        {
            // Remove all posts currently having status INGETROKKEN as these can't be available on the website
            \WP_CLI::runcommand( "wp post list --post_type=object --object_status=INGETROKKEN --fields=ID  | xargs -n1 -I % wp post delete --force %" );

        	// We always need to run these commands afterwards in one bulk action because 
            // we are disabling counting of terms etc in the "end_bulk_operation" method.
        	foreach( $this->getTaxonomies() as $taxonomy ) {
        		\WP_CLI::runcommand( "term recount " . $taxonomy);
        	}

            // Reindex Facets
            \WP_CLI::runcommand( "facetwp index" );
        }

    }