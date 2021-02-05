<?php

    namespace BvdB\Realworks;

    
    class Nieuwbouw implements ImportInterface
    {   

        public function getFeed() {
            // Setup endpoint for nieuwbouw
            $endpoint = '/nieuwbouw/v1/projecten';

            // Setup tokens to use for API connection
            $tokens = array(
                // '11631' => 'a8412021-00ed-4625-9b85-4c9156fbbd8c', // Rotterdam
                // '936576' => '169a2413-bb41-46b6-b9d7-1477f39c2eaf' // Oostvoorne
                '11631' => 'DEV_eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJSZWFsV29ya3MiLCJzdWIiOiI1YTQ4MWI1MC04Mjc0LTQwMmItOWI4My1lZmYxODg5YjQ1YzciLCJpYXQiOjE2MTI1MzM5MTd9.i4SHball2RrFgFksWLO-Be15w7yQ71AdCMYW6cHRjLY' // DEV Token
            );
            
            // Set the query parameters
            $query = array(
                'actief' => 'true',
                'aantal' => '10'
            );

            // Get the JSON data from API
            try 
            {
                $feed = new Feed();
                return $feed->getFeedLocation( 'nieuwbouw', $endpoint, $query, $tokens );
            }
            catch( \Exception $e ) 
            {
                return $e;
            }
        }

    }