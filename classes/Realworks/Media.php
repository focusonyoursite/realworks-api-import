<?php

    namespace BvdB\Realworks;

    
    class Media
    {   

        public static $realworks_dir = 'realworks';
        public static $media_needs_resize = array(
            'images',
            'panorama',
            'floorplan'
        );

        private $vimeo;
        protected static $vimeo_client_id = '5c40d0996d502a323bc2f82a088c29e84bf1a079';
        protected static $vimeo_client_secret = 'UeW0wKXclrhofmCIuRneOMgOXDvtpA9Q5by1S1U5N++DGC2HwER42YdAw4SY6R1HqKBue2TihUUVmyP7h0VNnWAWWimft1LZsnbeOAdW9z8z/NzVZf4dbXQsVv7voLev';
        protected static $vimeo_access_token = '0a08f0d11169d0a0a392cd762336bb58';

        public $upload_path;
        public $upload_uri;
        public $upload_dir;

        public function __construct()
        {
            $this->upload_path = wp_upload_dir()['basedir'] . '/';
            $this->upload_uri = wp_upload_dir()['baseurl'] . '/' . static::$realworks_dir . '/';
            $this->upload_dir = $this->createFolder( static::$realworks_dir , true );

            $this->vimeo = new \Vimeo\Vimeo( static::$vimeo_client_id, static::$vimeo_client_secret );
            $this->vimeo->setToken( static::$vimeo_access_token );
        }

        /**
         * Creates a folder at set location
         *
         * @param string $dirname
         * @param boolean $root from upload folder root
         * @return void
         */
        public function createFolder( string $dirname, bool $root = false )
        {
            $location = (( $root ) ? $this->upload_path : $this->upload_dir ) . $dirname . '/';

            // Create folder if not exists
            if( !file_exists( $location ) )
            {
                mkdir( $location, 0755 );
            }

            return $location;
        }

        /**
         * Returns the realworks dir
         *
         * @return void
         */
        public function getUploadsFolder() 
        {
            return $this->upload_dir;
        }

        /**
         * Parse the path to a valid URL
         *
         * @param string $location path
         * @return string $location URL
         */
        private function parseFolderUrl( string $location )
        {
            return str_replace( $this->upload_dir, $this->upload_uri, $location );
        }

        /**
         * Check if the dir exists within realworks folder
         *
         * @param string $dirname
         * @return boolean
         */
        public function folderExists( string $dirname )
        {
            return file_exists( $this->upload_dir . '/' . $dirname );
        }

        /**
         * Check if the file exists within realworks folder
         *
         * @param string $dirname
         * @return boolean
         */
        public function fileExists( string $location, string $filename )
        {
            return file_exists( $location . $filename );
        }

        /**
         * Get the correct filename from URL given
         * by realworks (as it contains parameters)
         *
         * @param string $url
         * @return string clean filename
         */
        public function getFilename( string $url )
        {
            $filename = explode('?', $url);
            if( isset($filename[0]) ) {
                return basename($filename[0]);
            }
            
            return basename($url);
        }

        /**
         * Format the media items list for further processing
         * and sort it by external order input
         *
         * @param array $media_list
         * @return array $media_objects
         */
        public function formatMediaList( array $media_list )
        {
            // Setup return array to append data to
            $media = array(
                'images' => array(),
                'panorama' => array(),
                'videos' => array(),
                'floorplan' => array(),
                'docs' => array(),
                'misc' => array()
            );

            // Loop through media list to categorise media items
            foreach( $media_list as $item )
            {
                // Get type
                if( !isset($item['vrijgave']) || $item['vrijgave'] == '1' ) {
                    $media[ $this->getMediaType($item['soort']) ][] = array(
                        'order' => $item['volgnummer'],
                        'filename' => $this->getFilename( $item['link'] ),
                        'url' => $item['link'],
                        'mime' => $item['mimetype'],
                        'featured' => ( $item['soort'] == 'HOOFDFOTO' )
                    );
                }
            }
            
            // Sort by order
            foreach( $media as $media_type => $media_object )
            {
                usort( $media[$media_type], function($a, $b) {
                    return $a['order'] <=> $b['order'];
                });
            }

            return $media;

        }

        /**
         * Get the media type by input
         *
         * @param string $type
         * @return string media key
         */
        private function getMediaType ( string $type )
        {
            // Get selector
            switch($type)
            {
                case 'FOTO':
                    $selector = 'images';
                    break;

                case 'HOOFDFOTO':
                    $selector = 'images';
                    break;
                    
                case 'VIDEO':
                    $selector = 'videos';
                    break;

                case 'CONNECTED_PARTNER':
                    $selector = 'panorama';
                    break;

                case 'PLATTEGROND':
                    $selector = 'floorplan';
                    break;

                case 'DOCUMENT':
                    $selector = 'docs';
                    break;

                default:
                    $selector = 'misc';
                    break;
            }

            return $selector;
        }

        /**
         * Import the media objects
         *
         * @param string $location
         * @param string $media_type
         * @param array $media_objects
         * @return string success or failure
         */
        public function importMediaObjects( int $post_id, string $media_type, array $media_objects )
        {   
            // Storage for imported items
            $imported_items = array();

            if( $media_type !== 'videos' )
            {
                // Create the file-folder (files go in separate folders by media type)
                $location = $this->createFolder( $post_id . '/' . $media_type );

                // Loop media objects
                foreach( $media_objects as $media_object )
                {
                    // Make sure file does not already exists
                    if( !$this->fileExists( $location, $media_object['filename'] ) )
                    {
                        // Setup URL to download
                        $download_url = $media_object['url'] . (( in_array( $media_type, static::$media_needs_resize ) ) ? '&resize=4' : '' );
                        $this->downloadFile( $download_url, $location, $media_object['filename'] );
                    }

                    // Add to imported items
                    $imported_items[] = $this->parseFolderUrl( $location ) . $media_object['filename'];

                    // Check if item is featured, set featured image as meta
                    if( $media_object['featured'] && $media_type == 'images' ) 
                    {
                        $featured_image = $this->parseFolderUrl( $location ) . $media_object['filename'];
                        update_post_meta( $post_id, 'featured_image_url', $featured_image );
                    }
                }
            }

            if( $media_type === 'videos' )
            {   
                foreach( $media_objects as $media_object )
                {
                    // echo 'Start upload of ' . $media_object['url'] . PHP_EOL;
                    // $video_response = $this->vimeo->request(
                    //     '/me/videos',
                    //     [
                    //         'upload' => [
                    //             'approach' => 'pull',
                    //             'link' => $media_object['url']
                    //         ],
                    //     ],
                    //     'POST'
                    // );

                    // FOLLOWS WHEN VIMEO ADDS UPLOAD ACCESS
                }
            }

            return $imported_items;
        }

        /**
         * Method for downloading the file over cURL to prevent timeout errors
         *
         * @param string $url
         * @param string $location
         * @param string $filename
         * @return void
         */
        private function downloadFile( string $url, string $location, string $filename )
        {
            // unlimited max execution time
            set_time_limit(0);

            // Setup file handler (where the file ends up after download)
            $file = fopen( $location . $filename , 'w');

            // cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url );

            // set cURL options
            curl_setopt($ch, CURLOPT_FAILONERROR, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // set file handler option
            curl_setopt($ch, CURLOPT_FILE, $file);

            // execute cURL
            curl_exec($ch);

            // close cURL
            curl_close($ch);

            // close file
            fclose($file);
        }


    }

?>