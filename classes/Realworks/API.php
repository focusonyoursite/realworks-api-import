<?php

    namespace BvdB\Realworks;

    
    class API
    {   
        
        // Set defaults
        public $api_base = 'https://api.realworks.nl';

        /**
         * Request function to talk to the Realworks API
         *
         * @param string $endpoint
         * @param string $token
         * @return array $response of the request
         */
        public function request ( string $url, string $token ) {

            // Setup cURL request
            $ch = curl_init();
                    
            // Set the url, number of POST vars, POST data
            curl_setopt( $ch, CURLOPT_URL, $url );

            // Build HTTP Header with token, needed to communicate
            // with the Realworks API server
            curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
                'Authorization: rwauth ' . $token
            ));

            // Set curl options
            curl_setopt( $ch, CURLOPT_TIMEOUT, 60 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true ); // Otherwise request is not parseable

            // Get the API response
            $response = curl_exec( $ch );
            $statuscode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
            curl_close($ch);

            // Statuscode needs to be 200
            if( $statuscode == 200 ) 
            {
                // Decode JSON object
                $response_body = json_decode( $response, true );

                // Check if valid JSON
                if( json_last_error() === JSON_ERROR_NONE )
                {
                    return $response_body;
                }

                else {
                    throw new \Exception( "Realworks API returned false JSON response on API Request to URL: $endpoint" );
                }
            }

            else 
            {
                throw new \Exception( "Realworks API returned statuscode: $statuscode on API Request to URL: $endpoint" );
            }

            // Return the result
            return $result;
        }


        /**
         * Build the request URI for the endpoint
         *
         * @param array $query
         * @return string $request_uri to use in API request
         */
        public function build_request_uri( array $query = null ) 
        {   
            // Setup blank request URI
            $request_uri = '';

            // Build request URI when query is set
            if( $query != null ) 
            {
                $request_uri = '?';

                // Loop query array
                foreach( $query as $key => $value ) {

                    // Parse key/value rows
                    if( $key != 'status' ) 
                    {
                        $request_uri .= $key . '=' . urlencode($value) . '&';
                    } 

                    // When the key is status, parse the status parameters as 
                    // a querystring with separate params for each status.
                    // This is required by Realworks API URL structure.
                    else
                    {
                        // Loop every status and insert in request_uri
                        foreach( $value as $status )
                        {
                            $request_uri .= $key . '=' . $status . '&';
                        }
                    }
                }

                // Remove last ampersand from URL
                $request_uri = rtrim($request_uri, '&');
            }

            return $request_uri;
        }


        /**
         * Get all the data for key in request body 
         *
         * @param string $key
         * @param string $url
         * @param string $token
         * @return array $result
         */
        public function parse_request_data( string $key, string $url, string $token ) 
        {

            // Store results
            $result = array();

            // Make the request
            $request = $this->request( $url, $token );

            // Add the request results to result array
            $result = array_merge( $result, $request[$key] );

            // Check if there is pagination
            if( isset( $request['paginering']['volgende'] ) ) 
            {
                // Recursively call the result function
                $result = array_merge( $result, ( $this->parse_request_data( $key, $request['paginering']['volgende'], $token ) ) );
            }

            return $result;
        }


    }