<?php

    namespace BvdB\Realworks;

    
    class Helpers
    {   

        /**
         * Check if the post needs a status update
         *
         * @param string $type
         * @param integer $post_id
         * @param array $data
         * @return boolean 
         */
        public function checkStatusUpdate ( $post_id, string $type, array $data )
        {   
            $status_update = null;
            $update = false;

            if( $type === 'wonen' || $type === 'business' )
            {
                // Get current status
                $current_status = wp_get_post_terms( $post_id, 'object_status' );
                $input_status = $this->formatTermData( $type, $data, 'status' );

                // Check if post needs update
                if ( $input_status !== 'INGETROKKEN' &&
                    $input_status !== 'GEANNULEERD' &&
                    $input_status !== 'PROSPECT' &&
                    $input_status !== 'IN_AANMELDING' &&
                    $input_status !== 'VERKOCHT_BIJ_INSCHRIJVING' &&
                    ( !isset($current_status[0]) || $current_status[0]->name !== $input_status ) )
                {
                    $update = true;
                }
                
                $status_update = array(
                    'old_status' => (( !isset( $current_status[0] ) ) ? null : $current_status[0]->name ),
                    'new_status' => $input_status
                );
            }

            // Update the post meta
            update_post_meta( $post_id, 'facebook_update_status', $update );
            update_post_meta( $post_id, 'facebook_update_status_details', $status_update );
        }


        /**
         * Get the post ID by reference (Realworks ID)
         *
         * @param string $type 
         * @param array $data
         * @return int post_id
         */
        public function findPostByReference ( string $type, array $data )
        {
            // Get reference by type
            $reference = $this->extractRealworksId( $type, $data );

            $query = new \WP_Query(
                array(
                    'no_found_rows' => true, // speeds up query
                    'update_post_meta_cache' => false, // speeds up query
                    'update_post_term_cache' => false, // speeds up query
                    'fields' => 'ids', // speeds up query
                    'post_type' => 'object',
                    'post_status' => 'any',
                    'meta_key'    => 'realworks_id',
                    'meta_value'  => $reference,
                )
            );
            
            if ( empty( $query->posts ) ) {
                return null;
            }
    
            // Return first item if it is already present in the WordPress database
            $post_id = reset( $query->posts ); // Because we are only retreiving the id fields ;)
            return $post_id; 
        }

        /**
         * Get the location of the realworks ID
         *
         * @param string $type
         * @param array $data
         * @return integer $id
         */
        function extractRealworksId( string $type, array $data )
        {
            if( $type === 'nieuwbouw' )
            {
                return $data['project']['id'];
            }
            else {
                return $data['id'];
            }
        }

        /**
         * Format the object title
         *
         * @param string $type
         * @param array $address
         * @return string title
         */
        public function formatObjectTitle ( string $type, array $data ) 
        {
            // Holder for title
            $title = '';

            // Format the title
            if( $type === 'wonen' || $type === 'business' ) 
            {   
                // Setup address var
                if( $type === 'wonen' ) 
                {
                    $address = $data['adres'];
                } 
                else 
                {
                    $address = $data;
                }

                // Format the title
                $title =  $address['straat'] . ' ';
                $title .= $address['huisnummer'] . (( !empty($address['huisnummertoevoeging']) ) ? $address['huisnummertoevoeging'] : '') . ', ';
                $title .= $address['plaats'];
                
            }

            if( $type === 'nieuwbouw' )
            {
                $title = $data['project']['algemeen']['omschrijving'];
            }

            // Return the title
            return $title;
        }

        /**
         * Format the object content
         *
         * @param string $type
         * @param array $data
         * @return string content
         */
        public function formatObjectContent( string $type, array $data )
        {
            // Holder for content
            $content = $data['teksten']['aanbiedingstekst'];

            // Return the content
            return htmlentities( html_entity_decode( $content ) );
        }

        /**
         * Format the fate based on the format
         *
         * @param string $type
         * @param array $data
         * @param string $return
         * @param string $format
         * @return string date
         */
        public function formatDate( string $type, array $data, string $return, string $format )
        {
            $date = time();

            if( $type === 'wonen' || $type === 'business' ) 
            {
                // Set path where the value is located
                switch ($return)
                {
                    case 'insert':
                        $value = $data['diversen']['diversen']['invoerdatum'];
                        break;

                    case 'modified':
                        $value = $data['tijdstipLaatsteWijziging'];
                        break;
                }
            }

            if( $type === 'nieuwbouw' ) 
            {
                // Set path where the value is located
                switch ($return)
                {
                    case 'insert':
                        $value = $data['project']['diversen']['diversen']['invoerdatum'];
                        break;

                    case 'modified':
                        $value = $data['tijdstipLaatsteWijziging'];
                        break;
                }
            }

            return date( $format, strtotime($value) );

        }

        /**
         * Formats the term as specified
         *
         * @param string $type
         * @param array $data
         * @param string $taxonomy
         * @return string formatted term name
         */
        public function formatTermData( string $type, array $data, string $taxonomy )
        {
            $term = '';

            /**
             * Wonen taxonomies
             */
            if( $type === 'wonen' ) 
            {
                // Check if the object is for sale or for rent
                // Can be both, so base is an array. 
                if( $taxonomy === 'koophuur' ) 
                {
                    $term = array();

                    if( !empty( $data['financieel']['overdracht']['koopprijs'] ) ) 
                    {
                        $term[] = 'KOOP';
                    }
                    
                    if( !empty( $data['financieel']['overdracht']['huurprijs'] ) )
                    {
                        $term[] = 'HUUR';
                    }
                }
                
                // Get the main object type and a subtype if available.
                if( $taxonomy === 'type' )
                {
                    $term = array();

                    $term[0]['parent'] = $data['object']['type']['objecttype'];

                    if( $term[0]['parent'] === 'APPARTEMENT' ) 
                    {
                        if( !empty($data['algemeen']['appartementsoort']) ) 
                        {
                            $term[0]['children'][] = $data['algemeen']['appartementsoort'];
                        }
                    }

                    // Get correct parent term
                    if ( $term[0]['parent'] === 'WOONHUIS' )
                    {
                        if( !empty($data['algemeen']['woonhuissoort']) ) 
                        {
                            $term[0]['children'][] = $data['algemeen']['woonhuissoort'];
                        }

                        if( !empty($data['algemeen']['woonhuistype']) ) 
                        {
                            $term[0]['children'][] = $data['algemeen']['woonhuistype'];
                        }
                    }
                }
                
                if( $taxonomy === 'plaats' )
                {
                    $term = $data['adres']['plaats'];
                }
                
                if( $taxonomy === 'status' )
                {
                    $term = $data['financieel']['overdracht']['status'];
                }
            }

            /**
             * Business taxonomies
             */
            if( $type === 'business' )
            {
                // Check if the object is for sale or for rent
                // Can be both, so base is an array. 
                if( $taxonomy === 'koophuur' ) 
                {
                    $term = array();
                    $koophuur = $data['financieel']['overdracht']['koopEnOfHuur'];

                    if( $koophuur['huurprijs'] != 0 ) 
                    {   
                        $term[] = 'HUUR';
                    }   

                    if( $koophuur['koopprijs'] != 0 ) 
                    {   
                        $term[] = 'KOOP';
                    } 
                }

                // Get the main object type and a subtype if available.
                if( $taxonomy === 'type' )
                {
                    $term = array();
                    $term['parent'] = $data['kenmerken']['hoofdfunctie'];
                }
                
                if( $taxonomy === 'plaats' )
                {
                    $term = $data['plaats'];
                }
                
                if( $taxonomy === 'status' )
                {
                    $term = $data['status'];
                }
            }

            /**
             * Nieuwbouw taxonomies
             */
            if( $type === 'nieuwbouw' ) 
            {
                // Check if the object is for sale or for rent
                // Can be both, so base is an array. 
                if( $taxonomy === 'koophuur' ) 
                {
                    $term = array();
                    $koophuur = $data['project']['algemeen']['koopOfHuur'];

                    if( $koophuur === 'KOOP_EN_HUUR' )
                    {
                        $term[] = 'KOOP';
                        $term[] = 'HUUR';
                    }
                    else
                    {
                        if( $koophuur === 'KOOP' )
                        {
                            $term[] = 'KOOP';
                        }

                        if( $koophuur === 'HUUR' )
                        {
                            $term[] = 'HUUR';
                        }
                    }
                }
                
                // Get the main object type and a subtype if available.
                if( $taxonomy === 'type' )
                {
                    $term = array();

                    if( !empty( $data['bouwtypen'] ) )
                    {
                        // Setup empty term
                        $term = array();

                        foreach( $data['bouwtypen'] as $key => $bouwtype )
                        {
                            $term[$key]['parent'] = $bouwtype['algemeen']['objecttype'];

                            if( $bouwtype['algemeen']['objecttype'] === 'APPARTEMENT' ) 
                            {
                                if( !empty($bouwtype['algemeen']['appartementsoort']) ) 
                                {
                                    $term[$key]['children'][] = $bouwtype['algemeen']['appartementsoort'];
                                }
                            }

                            // Get correct parent term
                            if ( $bouwtype['algemeen']['objecttype'] === 'WOONHUIS' )
                            {
                                if( !empty($bouwtype['algemeen']['woonhuissoort']) ) 
                                {
                                    $term[$key]['children'][] = $bouwtype['algemeen']['woonhuissoort'];
                                }

                                if( !empty($bouwtype['algemeen']['woonhuistype']) ) 
                                {
                                    $term[$key]['children'][] = $bouwtype['algemeen']['woonhuistype'];
                                }
                            }
                        }
                    }
                }
                
                if( $taxonomy === 'plaats' )
                {
                    $term = strtoupper($data['project']['algemeen']['plaats']);
                }
                
                if( $taxonomy === 'status' )
                {
                    $term = $data['project']['algemeen']['status'];
                }
            }

            return $term;
        }

        /**
         * Format the object terms and create new terms when not available and multiple
         *
         * @param string $taxonomy
         * @param array|string $value
         * @return array|string|void $terms
         */
        public function formatObjectTerm ( string $taxonomy, $value ) 
        {
    
            // Is an array, so we have to loop in order to maintain parent > child hierarchy
            if( $taxonomy == 'object_type' && !empty( $value ) )
            {
                // Storage variable for terms to set
                $object_terms = array();
                
                foreach( $value as $value_term )
                {
                    // Check if the parent term exists
                    $parent_term_id = term_exists( $value_term['parent'], $taxonomy );
        
                    // If parent term does not exists, insert into taxonomy
                    if( $parent_term_id === null )
                    {
                        $parent_term_id = wp_insert_term( $value_term['parent'], $taxonomy );
                    }
                    // Add parent term to storage
                    $object_terms[] = (int) $parent_term_id['term_id'];
        
                    // Now loop through any children. 
                    if( !empty( $value_term['children'] ) )
                    {
                        foreach ( $value_term['children'] as &$child_term ) 
                        {
                            $child_term_id = term_exists( $child_term, $taxonomy, $parent_term_id );
        
                            // If not exists, create the term and use ID to link to post. 
                            if( $child_term_id === null )
                            {
                                $child_term_id = wp_insert_term( $child_term, $taxonomy, array(
                                    'parent' => $parent_term_id['term_id']
                                ) );
                            }
        
                            $object_terms[] = (int) $child_term_id['term_id'];
                        }
                    }
                }
    
                // Return the array
                return $object_terms;
            }
    
            // Setup koophuur taxonomy
            elseif ( $taxonomy == 'object_koophuur' && !empty( $value )  )
            {
                $object_terms = array();
    
                foreach( $value as &$term )
                {
                    $term_id = term_exists( $term, $taxonomy );
                    
                    if( $term_id === null )
                    {
                        $term_id = wp_insert_term( $term, $taxonomy );
                    }
    
                    $object_terms[] = (int) $term_id['term_id'];
                }
    
                return $object_terms;
            }
    
            // Not an array so return as is
            else 
            {
                return $value;
            }
    
        }
        

    } // End class


?>