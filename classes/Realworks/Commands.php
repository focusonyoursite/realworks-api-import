<?php

namespace BvdB\Realworks;

/**
 * Base Class for Importer Commands
 *
 */
class Commands extends \WP_CLI_Command {

    public function __construct() {

        // Ensure only the minimum of extra actions are fired.
        if ( ! defined( 'WP_IMPORTING' ) ) {
			define( 'WP_IMPORTING', true );
        }

        $this->disableHooks();
    }

    /**
     * Disable hooks that you don't want to run while running inserts of updates.
     * Run these hooks from their own individuel commands.
     */
    protected function disableHooks(): void {

        // SearchWP: Stop the SearchWP indexer process
        add_filter( 'searchwp\index\process\enabled', '__return_false' );
        
        // FacetWP Indexer: Don't index on save of post
        if ( function_exists( 'FWP' ) ) {
			remove_action( 'save_post', array( FWP()->indexer, 'save_post' ) );
        }
        
        // Empty image_sizes array so the won't be generated. Run "wp media regenerate --all" afterwards
        add_filter( 'intermediate_image_sizes_advanced', function(){
            return [];
        });
    }

    /**
	 * Disable Term and Comment counting so that they are not all recounted after every term or post operation
	 */
	protected function startBulkOperation(): void {
		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );
	}

	/**
	 * Re-enable Term and Comment counting and trigger a term counting operation to update all term counts
	 */
	protected function endBulkOperation(): void {
        wp_defer_term_counting( false );
        wp_defer_comment_counting( false );
        wp_cache_flush();
	}

    /**
	 *  Clear all of the caches for memory management
	 */
	protected function clearCaches(): void {
        $this->resetQueryLog();
        $this->resetActionsLog();
        $this->resetLocalObjectCache();
    }
    
    /**
     * Reset the WordPress DB query log
     */
    protected function resetQueryLog(): void {
        global $wpdb;

        $wpdb->queries = array();
    }

    /**
     * Reset the WordPress Actions log
     */
    protected function resetActionsLog() {
        global $wp_actions;

        $wp_actions = array(); //phpcs:ignore
    }

    /**
     * Reset the local WordPress object cache
     *
     * This only cleans the local cache in WP_Object_Cache, without
     * affecting memcache
     */
    protected function resetLocalObjectCache() {
        global $wp_object_cache;

        if ( ! is_object( $wp_object_cache ) ) {
            return;
        }

        $wp_object_cache->group_ops = array();
        $wp_object_cache->memcache_debug = array();
        $wp_object_cache->cache = array();
        $wp_object_cache->stats = array();

        if ( is_callable( $wp_object_cache, '__remoteset' ) ) {
            $wp_object_cache->__remoteset(); // important
        }
    }
}