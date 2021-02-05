<?php

    namespace BvdB\Realworks;

    
    class Vimeo
    {  

        // Setup class variables
        private $vimeo;
        protected static $vimeo_client_id = '5c40d0996d502a323bc2f82a088c29e84bf1a079';
        protected static $vimeo_client_secret = 'UeW0wKXclrhofmCIuRneOMgOXDvtpA9Q5by1S1U5N++DGC2HwER42YdAw4SY6R1HqKBue2TihUUVmyP7h0VNnWAWWimft1LZsnbeOAdW9z8z/NzVZf4dbXQsVv7voLev';
        protected static $vimeo_access_token = '7df0dc5ecf39d61237a67bd155bfb3c0';
        protected static $vimeo_folder_id = '3594618';

        /**
         * Constructor
         */
        public function __construct()
        {
            $this->vimeo = new \Vimeo\Vimeo( static::$vimeo_client_id, static::$vimeo_client_secret );
            $this->vimeo->setToken( static::$vimeo_access_token );
        }

        /**
         * Main function to return the video URL
         *
         * @param integer $post_id
         * @param array $media_object
         * @return void
         */
        public function get_video_url ( int $post_id, array $media_object )
        {
            // URL to return
            $video_url = '';

            // Search if video already exists
            $search_result = $this->search( $media_object );

            // Does not exist, so upload
            if( $search_result === null )
            {
                $upload_result = $this->upload( $post_id, $media_object );

                if( $upload_result !== null ) 
                {
                    $this->put_in_folder( $upload_result );
                    return $upload_result;
                }

            }

            // Does exist, return video-uri
            else 
            {
                $video_url = $search_result;
            }

            // Return video URL
            return $video_url;
        }

        /**
         * Search Vimeo for Media object
         *
         * @param array $media_object
         * @return array|null result of search
         */
        private function search( array $media_object )
        {
            // Check if the video exists on Vimeo
            $request = $this->vimeo->request(
                '/me/videos',
                array(
                    'query' => '[' . $media_object['filename']. ']',
                ),
                'GET'
            );

            // Check if there are videos with post ID in description
            if( $request['body']['total'] > 0 ) 
            {
                $search_results = $request['body']['data'];
                foreach( $search_results as $search_result )
                {
                    if( $search_result['description'] === 'Media file: [' . $media_object['filename'] . ']' )
                    {
                        return $search_result['uri'];
                        break;
                    }
                }
            }
            
            return null;
        }

        /**
         * Upload video to vimeo
         *
         * @param int $post_id
         * @param array $media_object
         * @return string video uri
         */
        private function upload( int $post_id, array $media_object ) 
        {
            $request = $this->vimeo->request(
                '/me/videos', 
                array(
                    'upload' => array(
                        'approach' => 'pull',
                        'link' => $media_object['url']
                    ),
                    'name' => get_the_title( $post_id ),
                    'description' => 'Media file: [' . $media_object['filename'] . ']',
                    'privacy' => array(
                        'download' => false,
                        'embed' => 'public',
                        'view' => 'unlisted'
                    )
                ),
                'POST'
            );

            if( $request['status'] == 201  )
            {
                return $request['body']['uri'];
            }

            return null;
        }

        /**
         * Put video in correct folder
         *
         * @param string $vimeo_uri
         * @return void
         */
        private function put_in_folder ( string $vimeo_uri )
        {
            // Add to Kolpa vd Hoek folder in account
            $request = $this->vimeo->request(
                '/me/projects/' . static::$vimeo_folder_id . $vimeo_uri,
                array(),
                'PUT'
            );

            return $request['body'];
        }

    }

?>