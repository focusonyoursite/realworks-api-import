<?php

    namespace BvdB\Realworks;

    
    class Wonen extends API
    {   

        // Setup endpoint for wonen
        protected $endpoint = '/wonen/v1/objecten';

        // Setup tokens to use for API connection
        protected $tokens = array(
            '11631' => '7590910d-071f-45d6-9474-179301608e7e', // Rotterdam
            // '936576' => '1ad82de8-9c24-409d-8582-8a66b2fb227e' // Oostvoorne
        );


        /**
         * Generate the JSON feed 
         *
         * @return void
         */
        public function get_json_feed_data() 
        {   

            // For calling the API class
            $api = new API();

            
            // Setup variable to store feed results
            $feed_data = array();
            
            // Set the query parameters
            $query = array(
                'actief' => 'true',
                'aantal' => '50',
                'status' => array(
                    'BESCHIKBAAR',
                    'ONDER_BOD',
                    'ONDER_OPTIE',
                    'VERKOCHT_ONDER_VOORBEHOUD',
                    'VERHUURD_ONDER_VOORBEHOUD',
                    'VERHUURD',
                    'GEVEILD',
                    'INGETROKKEN',
                    'GEANNULEERD'
                )
            );

            // Setup the endpoint request uri
            $request_uri = $this->endpoint . $this->build_request_uri( $query );

            // Get the JSON data from API
            try 
            {
                // Loop the tokens
                foreach( $this->tokens as $office => $token ) 
                {
                    // Get the data from the endpoints and save all 
                    // data to filecontent variable
                    $feed_data = array_merge($feed_data, ( $this->parse_request_data( 'resultaten', $this->api_base . $request_uri, $token ) ) );

                }
            }
            catch( \Exception $e ) 
            {
                return $e;
            }

        }


        /**
         * Start the import for Wonen-objects
         *
         * @return void
         */
        public function import( string $json_file ) {
            
            // Get the JSON file to import
            

            // Parse JSON file to individual records

            // 

            
        }






        // Processing below

    }