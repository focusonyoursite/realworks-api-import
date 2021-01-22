<?php

    namespace BvdB\Realworks;

    
    class Feed extends API
    {   

        // Setup the general save locaion
        public $import_save_location = __DIR__ . '/../../json/';
        public $import_filename;

        public function __construct() {
            $this->import_filename = date( 'Ymd', time() ) . '.json';
        }

        /**
         * Gets the feed data and saving it to a JSON file
         *
         * @param string $name
         * @param string $endpoint
         * @param array $query
         * @param array $tokens
         * @return string file-location
         */
        public function getFeedLocation( string $name, string $endpoint, array $query = null, array $tokens ) 
        {   
            // Check if file already exists
            $file = $this->import_save_location . $name . '/' . $this->import_filename;
            if( file_exists( $file ) )
                return $file;

            // Does not exist, so continue with getting latest data
            try
            {
                $feed = '';
                $data = $this->getFeedData( $endpoint, $query, $tokens );

                if( !empty($data) ) 
                { 
                    $feed = $this->saveToJsonFile( $name, $data );
                }
                
                return $feed;
            }
            catch ( \Exception $e ) 
            {
                return $e;
            }
        }

        /**
         * Gets the data from the endpoint
         *
         * @param string $endpoint
         * @param array $query
         * @param array $tokens
         * @return void
         */
        public function getFeedData ( string $endpoint, array $query, array $tokens ) 
        {
            
            // Storage for feed data
            $feed_data = array();

            // Setup the request url
            $request_uri = '';
            if( $query !== null ) {
                $request_uri = $this->buildRequestUri( $query );
            }

            // Loop the tokens
            foreach( $tokens as $office => $token ) 
            {
                // Get the data from the endpoints and save all 
                // data to filecontent variable
                $feed_data = array_merge($feed_data, ( $this->parseRequestData( 'resultaten', $this->api_base . $endpoint . $request_uri, $token ) ) );
            }

            // Return the feed data
            return $feed_data;
        }

        /**
         * Create a new import document
         *
         * @param string $time
         * @return void
         */
        public function saveToJsonFile( string $type, array $data ) 
        {
            $contents = json_encode( $data );
            $file = $this->import_save_location . $type . '/' . $this->import_filename;

            if( !file_exists( $file ) ) 
            {
                $filesave = file_put_contents( $file, $contents );

                if( $filesave ) 
                {
                    return $file;
                }
                else 
                {
                    throw new \Exception( "Import file could not be saved due to error, with filename: $this->import_filename" );
                }
            } 

            // File exists, return current file
            else 
            {
                throw new \Exception( "File already exists with filename: $this->import_filename" );
            }
        } 
        
    }


?>