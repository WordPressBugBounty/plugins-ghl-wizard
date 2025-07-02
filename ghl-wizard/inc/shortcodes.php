<?php

/**********************************************
    Shortcodes to display Custom values
    @ updated in v: 1.1
**********************************************/

function lcw_display_custom_value( $atts ) {

	// Attributes
	$atts = shortcode_atts(
		array(
			'key' => ''
		),
		$atts,
		'lcw_custom_value'
	);

	$key = $atts['key'];

	if ( !empty( $key ) ) {

		$custom_values = hlwpw_get_location_custom_values();

		if ( isset( $custom_values[$key] ) ) {

			return $custom_values[$key];

		}else{

			return "<p class='hlwpw-warning'>Check the 'key' - ({$key}) is correct or refresh data on option tab.</p>";

		}

	}else{

		return "<p class='hlwpw-warning'>Custom value 'key' shouldn't be empty.</p>";

	}

}
add_shortcode( 'lcw_custom_value', 'lcw_display_custom_value' );



/**********************************************
    Force to sync contact
    @ v: 1.1
**********************************************/
function lcw_force_to_sync_contact(){

	if ( is_user_logged_in() ) {
		$user_id = get_current_user_id();
		lcw_turn_on_contact_sync($user_id);
	}
	
	return null;

}
add_shortcode( 'lcw_contact_sync', 'lcw_force_to_sync_contact' );



/**********************************************
    This is depricated
    will delete in next version
    @ depricated from v: 1.1
**********************************************/
// Shortcodes to display Custom values
function hlwpw_display_custom_value( $atts ) {

	// Attributes
	$atts = shortcode_atts(
		array(
			'key' => ''
		),
		$atts,
		'gw_custom_value'
	);

	$key = $atts['key'];

	if ( !empty( $key ) ) {

		$custom_values = hlwpw_get_location_custom_values();

		if ( isset( $custom_values[$key] ) ) {

			return $custom_values[$key];

		}else{

			return "<p class='hlwpw-warning'>Check the 'key' - ({$key}) is correct or refresh data on option tab.</p>";

		}

	}else{

		return "<p class='hlwpw-warning'>Custom value 'key' shouldn't be empty.</p>";

	}

}
add_shortcode( 'gw_custom_value', 'hlwpw_display_custom_value' );

/**********************************************
    Restricted Post Grid
    @ v: 1.2.x
**********************************************/
function lcw_post_grid_shortcode( $atts ) {
    ob_start();

    // Shortcode attributes with defaults, add post__in and post__not_in
    $atts = shortcode_atts(
        array(
            'post_type'      => 'post',   // Default post type
            'columns'        => 3,        // Default column count
            'posts_per_page' => 6,        // Default number of posts per page
            'taxonomy'       => '',       // Custom taxonomy (e.g., category, custom_taxonomy)
            'terms'          => '',       // Comma-separated term slugs/IDs
            'read_more_text' => 'Read More', // Customizable "Read More" text
            'orderby'        => '',        // Default orderby
            'order'          => '',        // Default order direction
            'post__in'       => '',        // New: Comma-separated post IDs to include
            'post__not_in'   => '',        // New: Comma-separated post IDs to exclude
        ), 
        $atts, 
        'lcw_post_grid'
    );

    global $wpdb, $current_user;
    wp_get_current_user();

    // Get restricted post IDs from wp_prefix_lcw_contacts table
    $restricted_posts = lcw_get_has_not_access_ids();

    // Handle post__in and post__not_in
    $post__in = array();
    if ( !empty($atts['post__in']) ) {
        $post__in = array_filter(array_map('intval', explode(',', $atts['post__in'])));
    }

    $extra_post__not_in = array();
    if ( !empty($atts['post__not_in']) ) {
        $extra_post__not_in = array_filter(array_map('intval', explode(',', $atts['post__not_in'])));
    }

    // Combine restricted_posts and extra_post__not_in
    $all_post__not_in = array_unique(array_merge((array)$restricted_posts, $extra_post__not_in));

    // WP_Query Arguments
    $args = array(
        'post_type'      => explode(',', $atts['post_type']),
        'posts_per_page' => intval($atts['posts_per_page']),
        'post__not_in'   => $all_post__not_in, // Exclude restricted posts and extra post__not_in
    );

    // If post__in is not empty, add it to the query
    if ( !empty($post__in) ) {
        $args['post__in'] = $post__in;
    }

    // Apply taxonomy filter if specified
    if (!empty($atts['taxonomy']) && !empty($atts['terms'])) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => sanitize_text_field($atts['taxonomy']),
                'field'    => 'id', // Change to 'slug' if using term slugs
                'terms'    => explode(',', $atts['terms']),
            ),
        );
    }
    // ORDER BY
    if (!empty($atts['orderby'])) {
        $args['orderby'] = sanitize_text_field($atts['orderby']);
    }
    
    // Optional: Add order direction (ASC/DESC)
    if (!empty($atts['order'])) {
        $args['order'] = strtoupper(sanitize_text_field($atts['order']));
    }

    $query = new WP_Query($args);

    if ($query->have_posts()) :
        echo '<div class="lcw-posts-grid columns-' . esc_attr($atts['columns']) . '">';
        while ($query->have_posts()) : $query->the_post(); ?>
            <div class="lcw-post-item">
                <?php if (has_post_thumbnail()) : ?>
                    <a href="<?php the_permalink(); ?>" class="lcw-post-thumb">
                        <?php the_post_thumbnail('medium'); ?>
                    </a>
                <?php endif; ?>
                <h3 class="lcw-post-title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h3>
                <a href="<?php the_permalink(); ?>" class="lcw-readmore-btn">
                    <?php echo esc_html($atts['read_more_text']); ?>
                </a>
            </div>
        <?php endwhile;
        echo '</div>';
        wp_reset_postdata();
    else :
        echo '<p>No posts found.</p>';
    endif;

    return ob_get_clean();
}
add_shortcode('lcw_post_grid', 'lcw_post_grid_shortcode');


// Simple Redirect Shortcode
function lcw_redirect_shortcode($atts, $content = null) {
    // Parse attributes with defaults
    $atts = shortcode_atts(array(
        'url' => '',
        'delay' => 0,
        'target' => '_self'
    ), $atts, 'lcw_redirect');

    // Process any shortcodes in the attributes first
    foreach ($atts as $key => $value) {
        $atts[$key] = do_shortcode($value);
    }

    // Allow shortcodes in content
    $content = do_shortcode($content);
    
    // Sanitize inputs
    $delay = absint($atts['delay']); // Ensure positive integer
    $target = esc_attr($atts['target']);
    $url = esc_url($atts['url'] ? home_url($atts['url']) : '');

    if (empty($url)) {
        return '<p class="hlwpw-warning">URL is required for redirect shortcode</p>';
    }

    // Generate unique ID for this instance
    $redirect_id = 'lcw_redirect_' . uniqid();

    // Build redirect script
    $output = '<div id="' . $redirect_id . '">';
    $output .= $content;
    $output .= '</div>';
    $output .= '<script>
        setTimeout(function() {
            window.open("' . $url . '", "' . $target . '");
        }, ' . ($delay * 1000) . ');
    </script>';

    return $output;
}
add_shortcode('lcw_redirect', 'lcw_redirect_shortcode');

// Password Reset Form
function lcw_reset_password_shortcode($atts) {
    $atts = shortcode_atts(array(
        'button_text' => __('Update Password', 'ghl-wizard'),
        'success_message' => __('Password updated successfully!', 'ghl-wizard'),
        'redirect_to' => '',
        'set_tags' => '',
        'remove_tags' => ''
    ), $atts, 'lcw_reset_password');

    if (!is_user_logged_in()) {
        return '<p class="hlwpw-warning">' . __('You must be logged in to reset your password.', 'ghl-wizard') . '</p>';
    }

    ob_start();
    ?>
    <form id="lcw-reset-password-form" method="post">
        <div id="lcw-reset-password-message"></div>
        <table class="form-table">
            <tr>
                <td><label for="password"><?php _e('New Password', 'ghl-wizard'); ?></label></td>
                <td>
                    <div class="password-field-wrapper">
                        <input type="password" name="password" id="password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <i class="dashicons dashicons-visibility"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <tr>
                <td><label for="confirm_password"><?php _e('Confirm Password', 'ghl-wizard'); ?></label></td>
                <td>
                    <div class="password-field-wrapper">
                        <input type="password" name="confirm_password" id="confirm_password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="dashicons dashicons-visibility"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <input type="hidden" id="action" value="lcw_reset_password_ajax">
                    <input type="hidden" id="preset_nonce" value="<?php echo wp_create_nonce('lcw_reset_password_nonce'); ?>">
                    <input type="hidden" id="set_tags" value="<?php echo esc_attr($atts['set_tags']); ?>">
                    <input type="hidden" id="remove_tags" value="<?php echo esc_attr($atts['remove_tags']); ?>">
                    <input type="hidden" id="success_message" value="<?php echo esc_attr($atts['success_message']); ?>">
                    <input type="hidden" id="redirect_to" value="<?php echo esc_url($atts['redirect_to']); ?>">
                    <button type="submit" id="lcw_reset_password_submit"><?php echo esc_html($atts['button_text']); ?></button>
                </td>
            </tr>
        </table>
    </form>

    <script>
    function togglePassword(fieldId) {
        var field = document.getElementById(fieldId);
        var icon = field.nextElementSibling.querySelector("i");
        if (field.type === "password") {
            field.type = "text";
            icon.classList.remove("dashicons-visibility");
            icon.classList.add("dashicons-hidden");
        } else {
            field.type = "password";
            icon.classList.remove("dashicons-hidden");
            icon.classList.add("dashicons-visibility");
        }
    }
</script>

    <?php
    return ob_get_clean();
}
add_shortcode('lcw_reset_password', 'lcw_reset_password_shortcode');