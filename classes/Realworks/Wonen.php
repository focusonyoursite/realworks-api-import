<?php

    namespace BvdB\Realworks;

    
    class Wonen implements ImportInterface
    {   

        /**
         * Generate the JSON feed 
         *
         * @return string the URL for the wonen-import JSON-file
         */
        public function getFeed() 
        {   
            // Setup endpoint for wonen
            $endpoint = '/wonen/v1/objecten';

            // Setup tokens to use for API connection
            $tokens = array(
                '11631' => '7590910d-071f-45d6-9474-179301608e7e', // Rotterdam
                // '936576' => '1ad82de8-9c24-409d-8582-8a66b2fb227e' // Oostvoorne
            );
            
            // Set the query parameters
            $query = array(
                'actief' => 'true',
                'aantal' => '100',
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

            // Get the JSON data from API
            try 
            {
                $feed = new Feed();
                return $feed->getFeedLocation( 'wonen', $endpoint, $query, $tokens );
            }
            catch( \Exception $e ) 
            {
                return $e;
            }

        }

        public function import( string $json_file ) {

        }

    }