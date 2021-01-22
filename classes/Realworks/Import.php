<?php

    namespace BvdB\Realworks;

    // This class is used for generating the JSON files and importing them afterwards
    class Import extends Commands
    {   

        protected $data = array();
        private $helpers;

        public function __construct()
        {
            $this->helpers = new ImportHelper();
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

             // Feeds to loop, storage for feeds
             $feeds = array(
                'wonen' => '',
                // 'business' => '',
                // 'nieuwbouw' => ''
            );

            foreach( $feeds as $feed => $feedlocation )
            {   

                // Get correct classname
                switch( $feed ) 
                {
                    case 'wonen':  
                        $class = new Wonen();
                        break;

                    case 'business':  
                        $class = new Business();
                        break;

                    case 'nieuwbouw':  
                        $class = new Nieuwbouw();
                        break;
                }

                try
                {
                    // Get the feed data
                    $file = $class->getFeed();

                    // Check if the file exists, and if so, add data to the 
                    // global data attribute.
                    if ( file_exists($file) ) {
                        $this->data[$feed] = json_decode(file_get_contents($file), 1);
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

                        $i++;
                        if( $i == 10 ) {
                            break; 
                        }
                    }

                    // echo "<pre>";
                    // print_r($imported_posts);
                    // echo "</pre>";
                    \WP_CLI::line('Ending import of feed ' . $feed );

                }
            }

            
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
            $post_id = $this->findPostByReference( $data['id'] );
            
            \WP_CLI::line( (( $post_id != null ) ? 'Start update of object with Post_ID: ' . $post_id : 'Insert new object for Realworks ID ' . $data['id']) );

            // Setup post to insert
            $post = array(
                'ID' => $post_id,
                'post_type' => 'object',
                'post_status' => 'publish',
                
                'post_title' => $this->helpers->formatObjectTitle( $type, $data ),
                'post_content' => $this->helpers->formatObjectContent( $type, $data ),

                'post_date' => $this->helpers->formatDate( $type, $data, 'insert', 'Y-m-d H:i:s' ),
                'post_modified' => $this->helpers->formatDate( $type, $data, 'modified', 'Y-m-d H:i:s' ),
                
                'meta_input' => array(
                    'realworks_id' => $data['id']

                    // TO DO: Import rest of the data as serialized arrays
                    // TO DO: Add media object as total media object on initial import,
                    //        we will handle media parsing after the initial import is

                ),
                'tax_input' => array(
                    // TO DO: Setup the taxonomies 
                )
            );

            // Create the post
            $return_id = \wp_insert_post ($post);

            if( $return_id == null ) {
                \WP_CLI::warning( 'Failed importing object with ID ' . $data['id'] );
            } else {
                \WP_CLI::success( 'Succesfully imported object with ID ' . $data['id'] . ' and ' . $return_id );
            }

            return $return_id;
        }

        /**
         * Get the post ID by reference (Realworks ID)
         *
         * @param string $reference = object ID
         * @return int post_id
         */
        private function findPostByReference ( string $reference )
        {
            $query = new \WP_Query(
                array(
                    'no_found_rows' => true, // speeds up query
                    'update_post_meta_cache' => false, // speeds up query
                    'update_post_term_cache' => false, // speeds up query
                    'fields' => 'ids', // speeds up query
                    'post_type' => 'object',
                    'post_status' => 'any',
                    'meta_key'    => 'realworks_id',
                    'meta_value'  => $reference,
                )
            );
            
            if ( empty( $query->posts ) ) {
                return null;
            }
    
            // Return first item if it is already present in the WordPress database
            $post_id = reset( $query->posts ); // Because we are only retreiving the id fields ;)
            return $post_id; 
        }



        /**
         * Remove all post and metadata for the imports
         *
         * @return void
         */
        public function nukeImport() 
        {
            
        }
        
        // private function finalize_import() {
            
        // 	// We always need to run these commands afterwards in one bulk action because we are disabling counting of terms etc in the "end_bulk_operation" method.
    
        // 	foreach( $this->get_taxonomies() as $taxonomy ) {
        // 		\WP_CLI::runcommand( "term recount " . $taxonomy);
        // 	}
        // }

    }