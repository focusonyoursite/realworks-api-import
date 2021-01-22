<?php

    namespace BvdB\Realworks;

    
    class ImportHelper
    {   

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
            if( $type === 'wonen' ) 
            {   
                // Get correct array from data
                $address = $data['adres'];

                // Key of address array and inserting space after key (true/false)
                $format = array(
                    'straat' => true,
                    'straat2' => true,
                    'huisnummer' => false,
                    'huisnummertoevoeging' => false,
                    'plaats' => false
                );

                foreach( $format as $key => $space )
                {
                    $title .= (( $key == 'plaats' ) ? ', ' : '' );
                    $title .= $address[ $key ];
                    $title .= (( $space ) ? ' ' : '');
                }
            }

            // Return the title
            return $title;
        }

        public function formatObjectContent( string $type, array $data )
        {
            // Holder for content
            $content = '';

            if( $type === 'wonen' )
            {
                // Get correct string from data
                $input = $data['teksten']['aanbiedingstekst'];
                $content = htmlentities( html_entity_decode( $input ) );
            }

            // Return the content
            return $content;
        }

        public function formatDate( string $type, array $data, string $return, string $format )
        {
            $date = time();

            if( $type === 'wonen' ) 
            {
                // Set path where the value is located
                switch ($return)
                {
                    case 'insert':
                        $value = $data['diversen']['diversen']['invoerdatum'];
                        break;

                    case 'modified':
                        $value = $data['diversen']['diversen']['wijzigingsdatum'];
                        break;
                }

                $date = date( $format, strtotime($value) );
            }

            return $date;

        }

    }

?>