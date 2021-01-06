<?php

    namespace BvdB\Realworks;

    
    // This class is used for generating the JSON files and importing them afterwards
    class Import
    {   

        private $feeds = array();



        public function __construct() {

            $wonen = new Wonen();

            // Set the JSON files
            try 
            {
                $feeds[] = $wonen->get_json_feed_data();
                // $feeds[] = new Business::get_json_feed();
                // $feeds[] = new Nieuwbouw::get_json_feed();

                echo "<pre>";
                print_r($feeds);
                echo "</pre>";
            }
            catch( \Exception $e ) 
            {
                echo "<pre>";
                print_r( $e->getMessage() );
                echo "</pre>";    
            }
            
        }

    }