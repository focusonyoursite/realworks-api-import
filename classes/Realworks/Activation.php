<?php

    namespace BvdB\Realworks;


    class Activation
    {   

        function __construct()
        {
            register_activation_hook( 'bvdb-realworks/bvdb-realworks.php', array($this, 'activate') );
            add_action( 'init', array($this, 'flushRewriteRules'), 20 );
        }

        /**
         * Flush rewriterules
         *
         * @return void
         */
        public function activate()
        {
            if ( ! get_option( 'bvdb_realworks_flush_rewrite_rules_flag' ) ) {
                \add_option( 'bvdb_realworks_flush_rewrite_rules_flag', true );
            }
        }

        /**
         * Flush rewrite rules when option is available
         *
         * @return void
         */
        public function flushRewriteRules()
        {
            if ( get_option( 'bvdb_realworks_flush_rewrite_rules_flag' ) ) {
                \flush_rewrite_rules();
                \delete_option( 'bvdb_realworks_flush_rewrite_rules_flag' );
            }
        }

    }