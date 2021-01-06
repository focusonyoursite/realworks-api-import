<?php

    namespace BvdB\Realworks;

    
    class Requests
    {   

        public $import_save_location = __DIR__ . '/../../json/';

        // Setup the available endpoints
        private $endpoints = array(
            'wonen' => '/wonen/v1/objecten',
            'bog' => '/bog/v1/objecten',
            'nieuwbouw' => '/nieuwbouw/v1/projecten',
        );

        protected $api_tokens = array(
            'wonen' => array(
                '11631' => '7590910d-071f-45d6-9474-179301608e7e', // Rotterdam
                '936576' => '1ad82de8-9c24-409d-8582-8a66b2fb227e' // Oostvoorne
            ),
            'bog' => array(
                '11631' => '2f9703df-9a27-4111-890d-7c6751cfe427', // Rotterdam
                '936576' => '7c57408f-1123-49a0-ba67-01757e7fa2b9' // Oostvoorne
            ),
            'nieuwbouw' => array(
                '11631' => 'a8412021-00ed-4625-9b85-4c9156fbbd8c', // Rotterdam
                '936576' => '169a2413-bb41-46b6-b9d7-1477f39c2eaf' // Oostvoorne
            )
        );

        private $request_endpoint = null;
        private $request_tokens = array();


        /**
         * Constructor which starts the import process
         */
        public function __construct( string $time = null ) 
        {

        }



        /**
         * Set the endpoint for the current request
         *
         * @param string $api
         * @return void
         */
        private function set_request_endpoint( string $api ) 
        {
            if( isset( $this->endpoints[ $api ] ) ) 
            {
                $this->request_endpoint = $this->endpoints[ $api ];
            }
            else 
            {
                throw new \Exception( 'Endpoint is not supported or does not exist' );
            }
        }

        /**
         * Set the tokens for the request authorization header
         *
         * @param string $api
         * @return void
         */
        private function set_request_tokens( string $api ) 
        {
            if( isset( $this->api_tokens[ $api ] ) ) 
            {
                $this->request_tokens = $this->api_tokens[ $api ];
            }
            else 
            {
                throw new \Exception( 'Tokens are not available for this API' );
            }
        }

        /**
         * Create a new import document
         *
         * @param string $time
         * @return void
         */
        private function create_json_import_file( string $time = null ) 
        {
            $filename = date('Ymd_Hi', $time) . '.json';

            if( !file_exists( $this->import_save_location . $filename ) ) 
            {
                file_put_contents( $this->import_save_location . $filename, '' );
            } 

            // File exists, so throw exception
            else 
            {
                throw new \Exception( "Import file already exists, with filename: $filename" );
            }
        } 

       


    }


?>




/**
         * Request the Realworks API
         *
         * @param string $api to call
         * @param array $request to perform
         * @return array with results
         */
        // private function get_json_import_contents( string $api = null, array $request = null )
        // {
        //     // Setup empty response
        //     $result = array();
        //     $query = '';

        //     // Verify the request
        //     $this->set_request_endpoint( $api );
        //     $this->set_request_tokens( $api );

        //     // Setup query
        //     if( !empty($request) ) 
        //     {
        //         $query = '?';

        //         foreach( $request as $key => $value ) {
        //             if( $key != 'status' ) 
        //             {
        //                 $query .= $key . '=' . urlencode($value) . '&';
        //             } 
        //             else
        //             {
        //                 foreach( $value as $status )
        //                 {
        //                     $query .= $key . '=' . $status . '&';
        //                 }
        //             }
        //         }

        //         $query = rtrim($query, '&');
        //     }

        //     // Setup URL (base + endpoint + query)
        //     // Ex. https://api.realworks.nl/wonen/v1/objects/?aantal=10
        //     $endpoint = $this->api_base . $this->request_endpoint . $query;

        //     // Make sure we have tokens to perform the request
        //     if( !empty( $this->request_tokens ) )
        //     {
        //         // Perform the request for each token
        //         foreach( $this->request_tokens as $id => $token ) 
        //         {   
        //             $result = array_merge( $result, $this->get_results( $endpoint, $token ) );
        //         }
        //     }

        //     echo "<pre>";
        //     print_r($result);
        //     echo "</pre>";
        //     exit();

        //     // return $result;
        // }

        // /**
        //  * Get the result
        //  *
        //  * @param string $endpoint
        //  * @param string $token
        //  * @param array $memory
        //  * @return array with results from API Call
        //  */
        // private function get_results( string $endpoint, string $token ) {

        //     $result = array();

        //     // Start cURL request
        //     $ch = curl_init();
                    
        //     // Set the url, number of POST vars, POST data
        //     curl_setopt( $ch, CURLOPT_URL, $endpoint );

        //     // Build HTTP Header
        //     curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
        //         'Authorization: rwauth ' . $token
        //     ));

        //     // Set curl options
        //     curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
        //     curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);
        //     curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

        //     // Get the API response
        //     $response = curl_exec( $ch );
        //     $statuscode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

        //     curl_close($ch);

        //     // Statuscode needs to be 200
        //     if( $statuscode == 200 ) 
        //     {
        //         // Decode JSON object
        //         $response_body = json_decode( $response, true );

        //         // Add result to the result array
        //         $result = array_merge($result, $response_body['resultaten']);

        //         if( isset( $response_body['paginering']['volgende'] ) ) 
        //         {
        //             $result = array_merge($result, ( $this->get_results( $response_body['paginering']['volgende'], $token ) ) );
        //         }
        //     }
        //     else 
        //     {
        //         // throw new \Exception( "Realworks API returned statuscode: $statuscode on API Request to URL: $endpoint" );
        //     }

        //     return $result;
        // }
