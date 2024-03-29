<?php

    namespace BvdB\Realworks;

    class Settings 
    {

        /**
         * Register the Realworks settings page
         */
        public function __construct()
        {
            add_action( 'init', array( $this, 'loadSettings' ) );
        }

        /**
         * Load the settings page
         *
         * @return void
         */
        public function loadSettings() 
        {
            /** 
             * Load settings page as ACF options page
             * @see: https://www.advancedcustomfields.com/resources/acf_add_options_page/
             */
            if( class_exists('ACF') )
            {
                \acf_add_options_page(array(
                    'page_title' => 'Realworks instellingen',
                    'menu_title' => 'Realworks',
                    'position' => 50,
                    'parent_slug' => 'edit.php?post_type=object',
                    'post_id' => 'realworks',
                    'autoload' => true,
                    'update_button' => 'Realworks instellingen opslaan',
                    'updated_message' => 'Realworks instellingen zijn bijgewerkt en opgeslagen. <strong>Let op:</strong> Deze hebben pas effect op de eerstvolgende import.',
                ));

                // Load the field group
                $this->loadLocalFieldGroup();
            }
        }

        /**
         * Get the API keys for feed-type
         *
         * @param string $type
         * @return array $api_keys
         */
        public function getAPIKeys( string $type ) 
        {
            // Setup return array
            $api_keys = array();

            // Get keys from options
            $keys = get_field( 'api_keys', 'realworks' );
            if( !empty($keys) ) 
            {
                foreach( $keys as $key ) 
                {
                    if( $key['type'] === $type ) 
                    {
                        $api_keys[ $key['location_id'] ] = $key['api_key'];
                    }
                }
            }

            return $api_keys;
        }

        
        /**
         * Load the ACF options
         *
         * @return void
         */
        public function loadLocalFieldGroup()
        {
            if( function_exists('acf_add_local_field_group') ) {

                // Realworks settings
                acf_add_local_field_group(array(
                    'key' => 'group_602f8265b6897',
                    'title' => 'Realworks instellingen',
                    'fields' => array(
                        array(
                            'key' => 'field_602f829b01736',
                            'label' => 'Activeer koppelingen',
                            'name' => 'active_feeds',
                            'type' => 'checkbox',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'wpml_cf_preferences' => 1,
                            'choices' => array(
                                'wonen' => 'Wonen',
                                'nieuwbouw' => 'Nieuwbouw',
                                'business' => 'Zakelijk (BOG)',
                            ),
                            'allow_custom' => 0,
                            'default_value' => array(
                            ),
                            'layout' => 'vertical',
                            'toggle' => 0,
                            'return_format' => 'value',
                            'save_custom' => 0,
                        ),
                        array(
                            'key' => 'field_602f82fc01739',
                            'label' => 'Vestigingen',
                            'name' => 'locations',
                            'type' => 'repeater',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'wpml_cf_preferences' => 1,
                            'collapsed' => '',
                            'min' => 0,
                            'max' => 0,
                            'layout' => 'table',
                            'button_label' => '',
                            'sub_fields' => array(
                                array(
                                    'key' => 'field_602f83030173b',
                                    'label' => 'ID',
                                    'name' => 'id',
                                    'type' => 'text',
                                    'instructions' => '',
                                    'required' => 0,
                                    'conditional_logic' => 0,
                                    'wrapper' => array(
                                        'width' => '',
                                        'class' => '',
                                        'id' => '',
                                    ),
                                    'wpml_cf_preferences' => 0,
                                    'default_value' => '',
                                    'placeholder' => '',
                                    'prepend' => '',
                                    'append' => '',
                                    'maxlength' => '',
                                ),
                                array(
                                    'key' => 'field_602f830a0173c',
                                    'label' => 'Titel',
                                    'name' => 'title',
                                    'type' => 'text',
                                    'instructions' => '',
                                    'required' => 0,
                                    'conditional_logic' => 0,
                                    'wrapper' => array(
                                        'width' => '',
                                        'class' => '',
                                        'id' => '',
                                    ),
                                    'wpml_cf_preferences' => 0,
                                    'default_value' => '',
                                    'placeholder' => '',
                                    'prepend' => '',
                                    'append' => '',
                                    'maxlength' => '',
                                ),
                            ),
                        ),
                        array(
                            'key' => 'field_602f82c901737',
                            'label' => 'API-sleutels',
                            'name' => 'api_keys',
                            'type' => 'repeater',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'wpml_cf_preferences' => 1,
                            'collapsed' => '',
                            'min' => 0,
                            'max' => 0,
                            'layout' => 'table',
                            'button_label' => '',
                            'sub_fields' => array(
                                array(
                                    'key' => 'field_602f82ef01738',
                                    'label' => 'Vestiging ID',
                                    'name' => 'location_id',
                                    'type' => 'text',
                                    'instructions' => '',
                                    'required' => 0,
                                    'conditional_logic' => 0,
                                    'wrapper' => array(
                                        'width' => '',
                                        'class' => '',
                                        'id' => '',
                                    ),
                                    'wpml_cf_preferences' => '',
                                    'default_value' => '',
                                    'placeholder' => '',
                                    'prepend' => '',
                                    'append' => '',
                                    'maxlength' => '',
                                ),
                                array(
                                    'key' => 'field_602f83230173d',
                                    'label' => 'Type',
                                    'name' => 'type',
                                    'type' => 'select',
                                    'instructions' => '',
                                    'required' => 0,
                                    'conditional_logic' => 0,
                                    'wrapper' => array(
                                        'width' => '',
                                        'class' => '',
                                        'id' => '',
                                    ),
                                    'choices' => array(
                                        'wonen' => 'Wonen',
                                        'nieuwbouw' => 'Nieuwbouw',
                                        'business' => 'Zakelijk (BOG)',
                                    ),
                                    'default_value' => array(
                                    ),
                                    'allow_null' => 0,
                                    'multiple' => 0,
                                    'ui' => 0,
                                    'return_format' => 'value',
                                    'wpml_cf_preferences' => 0,
                                    'ajax' => 0,
                                    'placeholder' => '',
                                ),
                                array(
                                    'key' => 'field_602f83380173e',
                                    'label' => 'API Sleutel',
                                    'name' => 'api_key',
                                    'type' => 'text',
                                    'instructions' => '',
                                    'required' => 0,
                                    'conditional_logic' => 0,
                                    'wrapper' => array(
                                        'width' => '',
                                        'class' => '',
                                        'id' => '',
                                    ),
                                    'wpml_cf_preferences' => 0,
                                    'default_value' => '',
                                    'placeholder' => '',
                                    'prepend' => '',
                                    'append' => '',
                                    'maxlength' => '',
                                ),
                            ),
                        ),
                    ),
                    'location' => array(
                        array(
                            array(
                                'param' => 'options_page',
                                'operator' => '==',
                                'value' => 'acf-options-realworks',
                            ),
                        ),
                    ),
                    'menu_order' => 10,
                    'position' => 'normal',
                    'style' => 'default',
                    'label_placement' => 'top',
                    'instruction_placement' => 'label',
                    'hide_on_screen' => '',
                    'active' => true,
                    'description' => '',
                ));

                // Facebook settings
                acf_add_local_field_group(array(
                    'key' => 'group_6091497e8c8bd',
                    'title' => 'Facebook instellingen',
                    'fields' => array(
                        array(
                            'key' => 'field_609149889130b',
                            'label' => 'Access tokens',
                            'name' => 'facebook_settings',
                            'type' => 'repeater',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'wpml_cf_preferences' => 1,
                            'collapsed' => '',
                            'min' => 0,
                            'max' => 0,
                            'layout' => 'table',
                            'button_label' => '',
                            'sub_fields' => array(
                                array(
                                    'key' => 'field_609149b29130c',
                                    'label' => 'Vestiging ID',
                                    'name' => 'id',
                                    'type' => 'text',
                                    'instructions' => '',
                                    'required' => 0,
                                    'conditional_logic' => 0,
                                    'wrapper' => array(
                                        'width' => '',
                                        'class' => '',
                                        'id' => '',
                                    ),
                                    'wpml_cf_preferences' => 0,
                                    'default_value' => '',
                                    'placeholder' => '',
                                    'prepend' => '',
                                    'append' => '',
                                    'maxlength' => '',
                                ),
                                array(
                                    'key' => 'field_609149bd9130d',
                                    'label' => 'Facebook Page ID',
                                    'name' => 'page_id',
                                    'type' => 'text',
                                    'instructions' => '',
                                    'required' => 0,
                                    'conditional_logic' => 0,
                                    'wrapper' => array(
                                        'width' => '',
                                        'class' => '',
                                        'id' => '',
                                    ),
                                    'wpml_cf_preferences' => 0,
                                    'default_value' => '',
                                    'placeholder' => '',
                                    'prepend' => '',
                                    'append' => '',
                                    'maxlength' => '',
                                ),
                                array(
                                    'key' => 'field_609149cd9130e',
                                    'label' => 'Access Token',
                                    'name' => 'access_token',
                                    'type' => 'text',
                                    'instructions' => '',
                                    'required' => 0,
                                    'conditional_logic' => 0,
                                    'wrapper' => array(
                                        'width' => '',
                                        'class' => '',
                                        'id' => '',
                                    ),
                                    'wpml_cf_preferences' => 0,
                                    'default_value' => '',
                                    'placeholder' => '',
                                    'prepend' => '',
                                    'append' => '',
                                    'maxlength' => '',
                                ),
                            ),
                        ),
                        array(
                            'key' => 'field_60914a009130f',
                            'label' => 'Facebook App ID',
                            'name' => 'facebook_app_id',
                            'type' => 'text',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'wpml_cf_preferences' => 0,
                            'default_value' => '',
                            'placeholder' => '',
                            'prepend' => '',
                            'append' => '',
                            'maxlength' => '',
                        ),
                        array(
                            'key' => 'field_60914a0891310',
                            'label' => 'Facebook App Secret',
                            'name' => 'facebook_app_secret',
                            'type' => 'text',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'wpml_cf_preferences' => 0,
                            'default_value' => '',
                            'placeholder' => '',
                            'prepend' => '',
                            'append' => '',
                            'maxlength' => '',
                        ),
                    ),
                    'location' => array(
                        array(
                            array(
                                'param' => 'options_page',
                                'operator' => '==',
                                'value' => 'acf-options-realworks',
                            ),
                        ),
                    ),
                    'menu_order' => 20,
                    'position' => 'normal',
                    'style' => 'default',
                    'label_placement' => 'top',
                    'instruction_placement' => 'label',
                    'hide_on_screen' => '',
                    'active' => true,
                    'description' => '',
                ));

                // Vimeo settings
                acf_add_local_field_group(array(
                    'key' => 'group_6149b08d31715',
                    'title' => 'Vimeo instellingen',
                    'fields' => array(
                        array(
                            'key' => 'field_6149b09cea77e',
                            'label' => 'Client ID',
                            'name' => 'vimeo_client_id',
                            'type' => 'text',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'default_value' => '',
                            'placeholder' => '',
                            'prepend' => '',
                            'append' => '',
                            'maxlength' => '',
                        ),
                        array(
                            'key' => 'field_6149b0bbea77f',
                            'label' => 'Client Secret',
                            'name' => 'vimeo_client_secret',
                            'type' => 'text',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'default_value' => '',
                            'placeholder' => '',
                            'prepend' => '',
                            'append' => '',
                            'maxlength' => '',
                        ),
                        array(
                            'key' => 'field_6149b0c3ea780',
                            'label' => 'Access Token',
                            'name' => 'vimeo_access_token',
                            'type' => 'text',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'default_value' => '',
                            'placeholder' => '',
                            'prepend' => '',
                            'append' => '',
                            'maxlength' => '',
                        ),
                        array(
                            'key' => 'field_6149b0d6ea781',
                            'label' => 'Folder ID',
                            'name' => 'vimeo_folder_id',
                            'type' => 'text',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'default_value' => '',
                            'placeholder' => '',
                            'prepend' => '',
                            'append' => '',
                            'maxlength' => '',
                        ),
                    ),
                    'location' => array(
                        array(
                            array(
                                'param' => 'options_page',
                                'operator' => '==',
                                'value' => 'acf-options-realworks',
                            ),
                        ),
                    ),
                    'menu_order' => 30,
                    'position' => 'normal',
                    'style' => 'default',
                    'label_placement' => 'top',
                    'instruction_placement' => 'label',
                    'hide_on_screen' => '',
                    'active' => true,
                    'description' => '',
                ));
                
            }
        }
    }


