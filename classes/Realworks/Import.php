<?php

    namespace BvdB\Realworks;

    // This class is used for generating the JSON files and importing them afterwards
    class Import extends Commands
    {   

        protected $data = array();
    
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
            \WP_CLI::line('Starting the import');
            
            // Make sure data is available
            // $this->getData();

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
    
            $data = $this->getData();
            
            // WP CLI function
            $this->startBulkOperation();
    
            // Set start in log
            \WP_CLI::line('Importing all objects' );
    
            // Array with WordPress post_ids as value
            $imported_posts = [];
    
            // Do the import
    
            
            // End import operation and reset all the hooks etc
            $this->endBulkOperation();

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