<?php 
add_action('admin_init', function () {
    // Check if WPML is active
    $wpml_active = function_exists('icl_object_id') && function_exists('icl_get_languages');
    
    if ($wpml_active) {
        // Get all active languages from WPML
        $languages = apply_filters('wpml_active_languages', null, array('skip_missing' => 0));
        
        if (!empty($languages)) {
            foreach ($languages as $language) {
                $lang_code = $language['language_code'];
                $option_name = 'delivery_information_' . $lang_code;
                
                // Register setting for each language
                register_setting('general', $option_name, [
                    'type' => 'string',
                    'sanitize_callback' => 'wp_kses_post', // allow safe HTML
                    'default' => '',
                ]);
                
                // Add settings field for each language
                add_settings_field(
                    $option_name,
                    'Delivery Information (' . $language['native_name'] . ')',
                    function () use ($option_name, $language) {
                        $content = get_option($option_name, '');
                        wp_editor(
                            $content,
                            $option_name, // editor ID
                            [
                                'textarea_name' => $option_name,
                                'media_buttons' => false,
                                'textarea_rows' => 6,
                                'teeny' => true,
                                'quicktags' => true,
                            ]
                        );
                        echo '<p class="description">Add delivery info for ' . $language['native_name'] . ' language (supports rich text).</p>';
                    },
                    'general'
                );
            }
        }
    } else {
        // Fallback for when WPML is not active - use the original implementation
        register_setting('general', 'delivery_information', [
            'type' => 'string',
            'sanitize_callback' => 'wp_kses_post', // allow safe HTML
            'default' => '',
        ]);

        add_settings_field(
            'delivery_information',
            'Delivery Information',
            function () {
                $content = get_option('delivery_information', '');
                wp_editor(
                    $content,
                    'delivery_information', // editor ID
                    [
                        'textarea_name' => 'delivery_information',
                        'media_buttons' => false,
                        'textarea_rows' => 6,
                        'teeny' => true,
                        'quicktags' => true,
                    ]
                );
                echo '<p class="description">Add delivery info displayed on the frontend (supports rich text).</p>';
            },
            'general'
        );
    }
});