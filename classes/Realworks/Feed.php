<?php

    namespace BvdB\Realworks;

    
    class Feed extends API
    {   

        // Setup the general save locaion
        public $import_save_location = __DIR__ . '/../../json/';
        public $import_filename;
        public $settings;

        public function __construct() {
            $this->import_filename = date( 'Ymd_Hi', time() ) . '.json';
            $this->settings = new Settings();
        }

        /**
         * Setup feed for input type
         * 
         * @param string $type the type of feed to get
         * @param string $import_type latest or initial
         * @param string $latest_update latest update
         * @return void
         */
        public function getFeed( string $type, string $import_type, string $latest_update ) 
        {

            // Get the endpoint
            $endpoint = $this->getEndpoint( $type );
            
            // Get the query parameters
            $query = $this->getQueryParams( $type, $import_type, $latest_update );

            // Get the API Keys as tokens
            $tokens = $this->settings->getAPIKeys( $type );
            
            // Perform query
            try 
            {
                return $this->getFeedLocation( $type, $endpoint, $query, $tokens );
            }
            catch( \Exception $e ) 
            {
                return $e;
            }

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
            if( $query !== null ) 
            {
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

        /**
         * Get API Endpoint
         *
         * @param string $type
         * @return string $endpoint
         */
        private function getEndpoint( string $type )
        {
            switch( $type )
            {
                case 'wonen':
                    return '/wonen/v1/objecten';

                case 'business':
                    return '/bog/v1/objecten';
                
                case 'nieuwbouw':
                    return '/nieuwbouw/v1/projecten';
            }
        }

        /**
         * Function to return the parameters for the API request
         *
         * @param string $type
         * @param string $import_type
         * @param string $latest_update
         * @return array with arguments
         */
        private function getQueryParams( string $type, string $import_type = null, string $latest_update = null )
        {
            // Setup return value
            $query = array();

            // Wonen objects OR
            // Business/BOG objects
            if( $type === 'wonen' || $type === 'business' ) 
            {
                $query = array(
                    'actief' => 'true',
                    'aantal' => '100',
                    'status' => array(
                        'BESCHIKBAAR',
                        'ONDER_BOD',
                        'ONDER_OPTIE',
                        'VERKOCHT_ONDER_VOORBEHOUD',
                        'VERHUURD_ONDER_VOORBEHOUD',
                        'VERHUURD'
                    )
                );

                // When import type is latest, we also get other item statusses
                if( $import_type === 'latest' ) 
                {
                    $query['status'] = array_merge($query['status'], array(
                        'GEVEILD',
                        'INGETROKKEN',
                        'GEANNULEERD'
                    ));
                }
            }

            // Nieuwbouw objects
            if( $type === 'nieuwbouw' )
            {
                $query = array(
                    'actief' => 'true',
                    'aantal' => '10'
                );

                // When import type is latest, we need to get all active 
                // statusses (to remove unactive projects automatically)
                if( $import_type === 'latest' ) 
                {
                    $query['actief'] = 'all';
                }
            }

            // When import type is latest, include the latest update
            if( $import_type === 'latest' && !empty($latest_update) )
            {
                $latest_update_datetime = strftime("%Y-%m-%dT%H:%M:%S", $latest_update);
                $query['gewijzigdNa'] = $latest_update_datetime;
            }

            // Return the query
            return $query;
        }
        
    }


?>