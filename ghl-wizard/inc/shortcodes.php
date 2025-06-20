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

    // Shortcode attributes with defaults
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
        ), 
        $atts, 
        'lcw_post_grid'
    );

    global $wpdb, $current_user;
    wp_get_current_user();

    // Get restricted post IDs from wp_prefix_lcw_contacts table
    $restricted_posts = lcw_get_has_not_access_ids();

    // WP_Query Arguments
    $args = array(
        'post_type'      => explode(',', $atts['post_type']),
        'posts_per_page' => intval($atts['posts_per_page']),
        'post__not_in'   => $restricted_posts, // Exclude restricted posts
    );

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
    $url = esc_url($atts['url']);

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