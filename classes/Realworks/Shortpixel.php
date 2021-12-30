<?php

    namespace BvdB\Realworks;

    class Shortpixel extends Commands
    {   
        // Setup Class
        private $shortpixel_api = null;

        // Media directory
        public static $realworks_dir = 'realworks';
        public $upload_dir;

        /**
         * Class constructor
         */
        public function __construct()
        {
            // Setup upload dir
            $this->upload_dir = wp_upload_dir()['basedir'] . '/' . static::$realworks_dir . '/';

            // Setup class
            $this->shortpixel_api = \ShortPixel\setKey('VUnKwZALtJVfjYOHtYdH');

            // Add persist type to allow folder optimization
            \ShortPixel\ShortPixel::setOptions(array("persist_type" => "text"));
        }
        
        /**
         * WP CLI Command to start the publishing
         *
         * @return void
         */
        public function start()
        {
            // Start publish to Facebook
            \WP_CLI::line('Start optimizing images');
            
            // Call the 
            $this->optimize();

            // End publish to Facebook
            \WP_CLI::line('Done optimizing all images');
        }

        /**
         * Optimize all images in folder
         *
         * @return void
         */
        private function optimize()
        {   
            // Get folder list
            $folders = $this->indexImageFiles();

            if( !empty($folders) )
            {
                foreach( $folders as $realworks_id => $files )
                {
                    // Check if folder is already optimized
                    $optimized = strpos($files[0], '.shortpixel');

                    if( !empty($files) && $optimized !== false )
                    {
                        \WP_CLI::line('Already optimized images in folder ' . $realworks_id );
                    }
                    elseif( !empty($files) && $optimized === false )
                    {
                        // Process images
                        $count = 0;
                        if( !empty($files) )
                        {
                            // Loop all files in folder
                            foreach( $files as $file )
                            {
                                // Optimize
                                try{
                                    $result = \ShortPixel\fromFile($file)->toFiles( $this->upload_dir . 'realworks/images' );
                                    $count++;
                                }
                                catch(\Exception $e)
                                {
                                    \WP_CLI::error('Encountered error while processing images: ' . $e->getMessage() );
                                }
                            }
                        }
                        
                        // Add optimization result as verbose output
                        \WP_CLI::success('Optimized ' . $count . ' images for folder ' . $realworks_id );
                    }
                    else 
                    {
                        // Add optimization result as verbose output
                        \WP_CLI::line('Nothing to optimize in folder ' . $realworks_id );
                    }
                }

            }
        }

        /**
         * Index all image subfolders
         *
         * @return array folder dirs
         */
        private function indexImageFiles()
        {
            // Image folder locations
            $image_folders = array();

            // Get main folder
            $main_folder_path = $this->upload_dir;

            // Loop subfolders which contain an 'images' folder
            $dir = opendir($main_folder_path);
            while( $subfolder = readdir($dir) )
            {
                // Set subfolder path
                $subfolder_path = $main_folder_path . $subfolder;

                // Check if the subfolder is a directory
                if(is_dir( $subfolder_path ) && $subfolder != '.DS_Store' && $subfolder != '.' && $subfolder != '..') 
                {
                    // Now get the images folder
                    if( is_dir($subfolder_path . '/images/') )
                    {
                        // Create array to store files
                        $image_folders[$subfolder] = array();

                        // Index all found files
                        $files = array_diff(scandir( $subfolder_path . '/images/' ), array('..', '.'));

                        // Add files to folder (index as realworks_id)
                        if( !empty($files) )
                        {
                            foreach($files as $file) 
                            {
                                $image_folders[$subfolder][] = $subfolder_path . '/images/' . $file;
                            }
                        }
                    }
                }
            }

            // Return array of image folders
            return $image_folders;

        }

    }


?>