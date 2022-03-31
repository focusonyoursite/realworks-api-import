<?php

    namespace BvdB\Realworks;

    
    class Vimeo
    {  

        // Setup class variables
        private $vimeo;
        private $vimeo_client_id = '';
        private $vimeo_client_secret = '';
        private $vimeo_access_token = '';
        private $vimeo_folder_id = '';

        /**
         * Constructor
         */
        public function __construct ()
        {
            // Gather Vimeo access data
            $this->vimeo_client_id = get_field('vimeo_client_id', 'realworks');
            $this->vimeo_client_secret = get_field('vimeo_client_secret', 'realworks');
            $this->vimeo_access_token = get_field('vimeo_access_token', 'realworks');
            $this->vimeo_folder_id = get_field('vimeo_folder_id', 'realworks');

            // Create Vimeo Instance
            $this->vimeo = new \Vimeo\Vimeo( $this->vimeo_client_id, $this->vimeo_client_secret );
            $this->vimeo->setToken( $this->vimeo_access_token );
        }

        /**
         * Main function to return the video URL
         *
         * @param integer $post_id
         * @param array $media_object
         * @return void
         */
        public function getVideoUrl ( int $post_id, array $media_object )
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
                    $this->putInFolder( $upload_result );
                    return $this->getVimeoId($upload_result);
                }

            }

            // Does exist, return video-id
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
        private function search ( array $media_object )
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
                        return $this->getVimeoId($search_result['uri']);
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
        private function upload ( int $post_id, array $media_object ) 
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
                        'view' => 'disable'
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
         * @return array $request
         */
        private function putInFolder ( string $vimeo_uri )
        {
            // Add to Kolpa vd Hoek folder in account
            $request = $this->vimeo->request(
                '/me/projects/' . $this->vimeo_folder_id . $vimeo_uri,
                array(),
                'PUT'
            );

            return $request['body'];
        }

        /**
         * Only return Vimeo ID for further processing
         *
         * @param string $uri
         * @return string video ID
         */
        private function getVimeoId( string $uri )
        {
            return str_replace('/videos/', '', $uri);
        }

        /**
         * Get the video url by post-id
         *
         * @param string $video_id
         * @return string video-url
         */
        public function getVideoUrlById( string $video_id )
        {
            // Get the video by ID
            $request = $this->vimeo->request(
                '/videos/' . $video_id,
                array(),
                'GET'
            );

            // Check if status is 200 (OK)
            if( $request['status'] == 200  )
            {
                if( 
                    isset( $request['body']['status'] ) && 
                    $request['body']['status'] === 'available' && 
                    isset( $request['body']['files'] ) && 
                    !empty( $request['body']['files'] ) )
                {
                    // Get files
                    $files = $request['body']['files'];

                    // Order array by 
                    usort($files, function($a, $b) {
                        return $a['width'] <=> $b['width'];
                    });

                    // Last item in array is largest file (hd mostly)
                    $video_file = end($files);

                    if( isset($video_file['link']) )
                    {
                        // Return the file link
                        return $video_file['link'];
                    }
                }
            }
            
            // Always return a string
            return '';
        }

    }

?>
