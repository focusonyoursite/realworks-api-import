<?php

    namespace BvdB\Realworks;

    
    class Nieuwbouw implements ImportInterface
    {   

        public $endpoint = '/nieuwbouw/v1/objecten';

        protected $tokens = array(
            '11631' => 'a8412021-00ed-4625-9b85-4c9156fbbd8c', // Rotterdam
            '936576' => '169a2413-bb41-46b6-b9d7-1477f39c2eaf' // Oostvoorne
        );

        public function getFeed() {
            return '';
        }

        public function import( string $json_file ) {

        }

    }