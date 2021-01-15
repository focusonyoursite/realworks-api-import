<?php

    namespace BvdB\Realworks;

    interface ImportInterface
    {
        public function getFeed();
        public function import( string $json_file );
    }

?>