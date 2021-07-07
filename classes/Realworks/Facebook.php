<?php

    namespace BvdB\Realworks;

    class Facebook extends Commands
    {   

        // Setup Facebook API object
        private $facebook_api = null;

        // Facebook access token by page_id
        private $facebook_access_tokens = array(); 

        // Facebook access token by page_id
        private $publish_time = null; 

        // Setup log location
        private $logs_dir = null;

        // Set uploads URI
        private $temp_storage_uri = null;
        private $temp_storage_dir = null;

        // Helpers class
        private $helpers = null;

        /**
         * Class constructor
         */
        public function __construct()
        {
            // Helpers class
            $this->helpers = Helpers::getInstance();

            // Set uploads dir
            $this->temp_storage_uri = wp_upload_dir()['baseurl'] . '/realworks/tmp/';
            $this->temp_storage_dir = wp_upload_dir()['basedir'] . '/realworks/tmp/';
            
            // When the storage dir does not exist, generate
            $this->createTemporaryStorage( $this->temp_storage_dir );

            // This needs to be loaded through the settings page
            $facebook_app_id = get_field('facebook_app_id', 'realworks');
            $facebook_app_secret = get_field('facebook_app_secret', 'realworks');
            $facebook_settings = get_field('facebook_settings', 'realworks');
            $facebook_access_tokens = array();

            // Setup Facebook API object
            $this->facebook_api = new \Facebook\Facebook([
                'app_id' => $facebook_app_id,
                'app_secret' => $facebook_app_secret,
                'default_graph_version' => 'v2.10'
            ]);

            // Format the access tokens
            if( !empty($facebook_settings) )
            {
                foreach( $facebook_settings as $setting )
                {
                    $facebook_access_tokens[ $setting['id'] ] = array(
                        'access_token' => $setting['access_token'],
                        'page_id' => $setting['page_id']
                    );
                }
            }

            // Get access token by establishment_id
            $this->facebook_access_tokens = $facebook_access_tokens;

            // Setup publish time for posts
            $this->publish_time = time() + ( 168 * 3600 );

            // Set logs location
            $this->logs_dir = __DIR__ . '/../../logs/';

        }

        /**
         * WP CLI Command to start the publishing
         *
         * @return void
         */
        public function start()
        {
            // Start publish to Facebook
            \WP_CLI::line('Start publishing updates to Facebook');
            
            // Call the 
            $this->publish();

            // End publish to Facebook
            \WP_CLI::line('Done publishing updates to Facebook');

            // Cleanup old logs
            $this->cleanLogs();
        }

        /**
         * Main action which gathers the posts to publish
         *
         * @return void
         */
        public function publish()
        {
            // Get objects which have a update status of true
            $args = array(
                'post_type' => 'object',
                'showposts' => -1,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'facebook_update_status',
                        'value' => true,
                    )
                )
            );

            $query = new \WP_Query($args);
            if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post();

                // Get the status details
                $status = get_post_meta( get_the_id(), 'facebook_update_status_details', true );

                // Get media object
                $media = get_post_meta( get_the_id(), 'media', true);

                // Check if there is a video related to the post
                if( isset($media['videos']) && !empty($media['videos']) )
                {
                    // Has a video, now check if the video is available on Vimeo
                    $vimeo = new Vimeo();
                    $video_url = $vimeo->getVideoUrlById( $media['videos'][0] );

                    // When video is on Vimeo, post it
                    if( $video_url !== null )
                    {
                        // $this->postVideo( get_the_id(), $video_url, $status );
                    }

                }

                // If there is no video, then process as image
                elseif( isset($media['images']) && !empty($media['images']) )
                {
                    $this->postImage( get_the_id(), $media, $status );
                    break;
                }

            endwhile; endif;

        }

        /**
         * Post Video to Facebook
         * @see: https://developers.facebook.com/docs/graph-api/reference/page/videos#creating
         *
         * @param integer $post_id
         * @param array $status
         * @return int $facebook_id
         */
        private function postVideo( int $post_id, string $video_url, array $status )
        {    
            $filename = $post_id . '.mp4';
            $filepath = $this->temp_storage_dir . $filename;

            // Download the video from Vimeo
            if( !file_exists($filepath) )
            {
                // Download file
                $this->helpers->downloadFile( $video_url, $this->temp_storage_dir, $filename );

                // Check if it exists
                if( file_exists($filepath) )
                {
                    $video = $this->temp_storage_uri . $filename;
                }
            }
            // Already downloaded, so use direct list
            else 
            {
                $video = $this->temp_storage_uri . $filename;
            }

            // Get the formatted message
            $message = $this->formatUpdateMessage($post_id, $status) . ', bekijk op onze website: ' . get_the_permalink($post_id);
            
            // Setup Data array
            $data = array(
                'title' => get_the_title($post_id),
                'description' => $message,
                'file_url' => $video,
                'published' => false,
                'scheduled_publish_time' => $this->publish_time
            );

            // Perform post action to videos endpoint
            $this->post( $post_id, 'videos', $data, 'video' );
        }

        /**
         * Post image to Facebook
         * @see: https://developers.facebook.com/docs/graph-api/reference/page/feed/#publish
         *
         * @param integer $post_id
         * @param array $status
         * @return int $facebook_id
         */
        private function postImage( int $post_id, array $media, array $status )
        {
            // Get the formatted message
            $message = $this->formatUpdateMessage($post_id, $status);
            
            // Setup Data array
            $data = array(
                'title' => get_the_title($post_id),
                'message' => $message,
                'link' => get_the_permalink($post_id),
                'published' => false,
                'scheduled_publish_time' => $this->publish_time
            );

            // Perform post action to videos endpoint
            $this->post( $post_id, 'feed', $data, 'image' );
        }

        /**
         * Perform the actual post-action
         *
         * @param integer $post_id
         * @param string $endpoint
         * @param array $data
         * @param string $type
         * @return void
         */
        private function post( int $post_id, string $endpoint, array $data, string $type )
        {
            // Posting
            \WP_CLI::line('Posting ' . $type . ' for ID: ' . $post_id);

            // Get the establishment id of the post
            $establishment_id = get_post_meta( $post_id, 'vestiging', true );
            
            // Get correct Page ID and Access Token
            $facebook_page_id = $this->facebook_access_tokens[$establishment_id]['page_id'];
            $facebook_access_token = $this->facebook_access_tokens[$establishment_id]['access_token'];

            try 
            {
                // Perform the request
                $response = $this->facebook_api->post( $facebook_page_id . '/' . $endpoint, $data, $facebook_access_token );

                // Success!
                \WP_CLI::success('Successfully posted ' . $type . ' for ID: ' . $post_id);

                // Add the post meta for this update
                // add_post_meta( $post_id, 'facebook_update_id_' . time(), 'Succesfully published ' . $type );

                // Update post meta to set the update status to false
                // update_post_meta( $post_id, 'facebook_update_status', false );
            }

            // When Graph returns an error
            catch(\Facebook\Exceptions\FacebookAuthenticationException $e) 
            {
                \WP_CLI::warning('Authentication Exception: Error posting ' . $type . ' for ID: ' . $post_id . ' - ' . $e->getMessage());
            } 

            // When Graph returns an error
            catch(\Facebook\Exceptions\FacebookResponseException $e) 
            {
                \WP_CLI::warning('Response Exception: Error posting ' . $type . ' for ID: ' . $post_id . ' - ' . $e->getMessage());
            } 

            // When validation fails or other local issues
            catch(\Facebook\Exceptions\FacebookSDKException $e) {
                \WP_CLI::warning('SDK Exception: Error posting ' . $type . ' for ID: ' . $post_id . ' - ' . $e->getMessage());
            }

            return null;
        }

        /**
         * Format the message accompanied by media item
         *
         * @param integer $post_id
         * @param array $status
         * @return string $message
         */
        private function formatUpdateMessage( int $post_id, array $status )
        {
            // Message store
            $message = '';

            // Format message according to new status
            switch( $status['new_status'] )
            {
                case 'BESCHIKBAAR':
                    $message = 'Nieuw in verkoop';
                    break;

                case 'ONDER_BOD':
                    $message = 'Onder bod';
                    break;

                case 'VERKOCHT_ONDER_VOORBEHOUD':
                    $message = 'Verkocht onder voorbehoud';
                    break;

                case 'VERHUURD_ONDER_VOORBEHOUD':
                    $message = 'Verhuurd onder voorbehoud';
                    break;

                case 'VERKOCHT':
                    $message = 'Verkocht';
                    break;

                case 'VERHUURD':
                    $message = 'Verhuurd';
                    break;
                
                default:
                    $message = '';
                    break;
            }

            // Get the title
            $title = get_the_title( $post_id );

            // Return the update message
            return $message . ': ' . $title;

        }
        

        /**
         * Remove old logs
         *
         * @return void
         */
        public function cleanLogs()
        {
            // Check if logs dir exists
            if( file_exists( $this->logs_dir ) )
            {
                // Get the files
                $loglist = array_diff( scandir( $this->logs_dir ), array( '..', '.' ) );

                // Set max date
                $max_datetime = date('Y-m-d H:i:s', strtotime('-7 days'));
                
                // Loop list when not empty
                if( !empty($loglist) )
                {
                    foreach( $loglist as $log ) 
                    {
                        // Get datetime string from filename, output: Y-m-d H:i:s
                        // Ex. 2021-04-21 14:15:00
                        $datetime = str_replace('_', ' ', rtrim(ltrim($log, 'facebook-'), '.log'));

                        if( strtotime($datetime) < strtotime($max_datetime) )
                        {
                            // Unlink
                            unlink( $this->logs_dir . '/' . $log );
                        }    
                    }
                }

                // Removed logs before max date
                \WP_CLI::line("Removed Facebook logs before $max_datetime");
            }
        }

        /**
         * Create temp storage when it doesn't exist
         *
         * @param string $dir
         * @return void
         */
        private function createTemporaryStorage( string $dir ) 
        {
            if( !file_exists($dir) )
            {
                \WP_CLI::line('Create tmp storage folder');
                mkdir( $dir, 0755 );
            }
        }




    }

?>