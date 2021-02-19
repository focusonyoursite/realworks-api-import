<?php

    namespace BvdB\Realworks;

    // This class is used for generating the JSON files and importing them afterwards
    class Import extends Commands
    {   

        protected $data = array();

        // Class wrappers
        private $feed;
        private $helpers;
        private $media;
        private $meta;
        private $settings;

        public function __construct()
        {
            $this->feed = new Feed();
            $this->helpers = new ImportHelper();
            $this->media = new Media();
            $this->meta = new Meta();
            $this->settings = new Settings();
        }

        /**
         * @synopsis [<external_event_id>] [--use-cached-endpoint]
         * 
         * @param array $args 
         * @param array $assoc_args Associative CLI args.
         * 
         */
        public function start( $args, $assoc_args )
        {
            // Set notice for starting the import
            \WP_CLI::line('Starting import at: ' . date('d-m-Y H:i:s') );
            
            // Make sure data is available
            $this->getData();

            // Start list
            $this->import( $this->data );

            // Set notice for completing the import
            \WP_CLI::success('Import complete at: ' . date('d-m-Y H:i:s') );
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
                        $file = $this->feed->getFeed( $type );

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

                    foreach( $data as $object ) {
                        
                        $imported_posts[] = $this->importObject( $feed, $object );

                    }

                    \WP_CLI::line('Ending import of feed ' . $feed );

                }
            }

            // Start Import of media
            $this->importMedia( $imported_posts );

            // Setup Facebook post
            
            
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
            
            if( $post_id == null ) {
                \WP_CLI::warning( 'Failed importing object with ID ' . $realworks_id );
            } else {
                // Output success message
                $this->importTerms( $post_id, $type, $data );
                $this->helpers->checkStatusUpdate( $post_id, $type, $data );

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
                    wp_set_object_terms( $post_id, $terms, $taxonomy );
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
                
                // Loop posts media import
                foreach( $post_ids as $post_id )
                {
                    // Start logging output
                    \WP_CLI::line('Start importing media items from post: ' . $post_id);

                    // Storage for post meta: media
                    $post_media = array();

                    // Setup the target folder, first check if folder exists, if not. Create it. 
                    $media_folder = $this->media->createFolder( $post_id );

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
                                $post_media[$media_type] = $this->media->importMediaObjects( $post_id, $media_type, $media_objects );
                            }
                        }
                    }

                    update_post_meta($post_id, 'media', $post_media);
                }

            }
            else
            {
                \WP_CLI::warning( 'No media to import' );
            }
        }


        
        // private function finalize_import() {
            
        // 	// We always need to run these commands afterwards in one bulk action because we are disabling counting of terms etc in the "end_bulk_operation" method.
    
        // 	foreach( $this->get_taxonomies() as $taxonomy ) {
        // 		\WP_CLI::runcommand( "term recount " . $taxonomy);
        // 	}
        // }

    }