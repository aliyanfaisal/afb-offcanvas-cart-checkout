<?php
 
function create_store_user_role() {
    add_role(
        'store',
        __('Store Manager'),
        array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
        )
    );
}
add_action('after_switch_theme', 'create_store_user_role');

 

/**
 * AJAX Handler to get all users with 'store' role
 */
function get_store_users_ajax_handler() {
    // Security check
    if (!wp_verify_nonce($_POST['nonce'], 'afb_nonce')) {
        wp_die('Security check failed');
    }
    
    // Get all users with 'store' role
    $store_users = get_users(array(
        'role' => 'store',
        'orderby' => 'display_name',
        'order' => 'ASC'
    ));
    
    $stores = array();
    
    foreach ($store_users as $user) {
        $stores[] = array(
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'store_address' => get_user_meta($user->ID, 'store_address', true),
            'store_city' => get_user_meta($user->ID, 'store_city', true)
        );
    }
    
    wp_send_json_success($stores);
}

// Hook for logged-in users
add_action('wp_ajax_get_store_users', 'get_store_users_ajax_handler');
// Hook for non-logged-in users (if needed for checkout)
add_action('wp_ajax_nopriv_get_store_users', 'get_store_users_ajax_handler');

/**
 * Add custom fields to user profile for store users
 */
function add_store_user_fields($user) {
    if (in_array('store', $user->roles)) {
        ?>
        <h3><?php _e('Store Information'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="store_address"><?php _e('Store Address'); ?></label></th>
                <td>
                    <input type="text" name="store_address" id="store_address" 
                           value="<?php echo esc_attr(get_user_meta($user->ID, 'store_address', true)); ?>" 
                           class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="store_city"><?php _e('Store City'); ?></label></th>
                <td>
                    <input type="text" name="store_city" id="store_city" 
                           value="<?php echo esc_attr(get_user_meta($user->ID, 'store_city', true)); ?>" 
                           class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="store_phone"><?php _e('Store Phone'); ?></label></th>
                <td>
                    <input type="text" name="store_phone" id="store_phone" 
                           value="<?php echo esc_attr(get_user_meta($user->ID, 'store_phone', true)); ?>" 
                           class="regular-text" />
                </td>
            </tr>
        </table>
        <?php
    }
}
add_action('show_user_profile', 'add_store_user_fields');
add_action('edit_user_profile', 'add_store_user_fields');



/**
 * Save store user custom fields
 */
function save_store_user_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    
    $user = get_user_by('id', $user_id);
    if (in_array('store', $user->roles)) {
        update_user_meta($user_id, 'store_address', sanitize_text_field($_POST['store_address']));
        update_user_meta($user_id, 'store_city', sanitize_text_field($_POST['store_city']));
        update_user_meta($user_id, 'store_phone', sanitize_text_field($_POST['store_phone']));
    }
}
add_action('personal_options_update', 'save_store_user_fields');
add_action('edit_user_profile_update', 'save_store_user_fields');






/**
 * Add store role to new user registration dropdown (admin)
 */
function add_store_role_to_dropdown($roles) {
    $roles['store'] = 'Store Manager';
    return $roles;
}
// add_filter('editable_roles', 'add_store_role_to_dropdown');
