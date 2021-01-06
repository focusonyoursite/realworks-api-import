<?php

    namespace BvdB\Realworks;

    
    class Business extends API
    {   

        public $endpoint = '/bog/v1/objecten';

        protected $tokens = array(
            '11631' => '2f9703df-9a27-4111-890d-7c6751cfe427', // Rotterdam
            '936576' => '7c57408f-1123-49a0-ba67-01757e7fa2b9' // Oostvoorne
        );

        /**
         * Start the import for BOG-objects
         *
         * @param string $from timestamp for the API request
         * @return void
         */
        public function import( string $from ) {

        }

        // Processing below

    }