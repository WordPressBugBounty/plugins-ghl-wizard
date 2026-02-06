<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Show/hide admin bar based on setting.
 * @param bool $show_admin_bar
 */
function lcw_show_admin_bar( $show_admin_bar ) {
    if ( empty( get_option( 'lcw_hide_admin_bar' ) ) ) {
        return $show_admin_bar;
    }

    return ( current_user_can( 'delete_posts' ) || current_user_can( 'edit_posts' ) );
}
add_filter( 'show_admin_bar', 'lcw_show_admin_bar', 999 );

/**
 * Redirect after login.
 * @return void
 */
function lcw_redirect_user_after_login() {
    if ( current_user_can( 'manage_options' ) ) {
        return;
    }
    $is_login_redirect_enabled = get_option( 'lcw_enable_login_redirect' );
    if ( ! empty( $is_login_redirect_enabled ) ) {
        $redirect_url = get_permalink( get_option( 'lcw_login_redirect_page' ) );
        wp_safe_redirect( $redirect_url );
        exit;
    }
}
add_action( 'wp_login', 'lcw_redirect_user_after_login' );

/**
 * Redirect after logout.
 * @return void
 */
function lcw_redirect_user_after_logout() {
    $is_logout_redirect_enabled = get_option( 'lcw_enable_logout_redirect' );
    if ( ! empty( $is_logout_redirect_enabled ) ) {
        $redirect_url = get_permalink( get_option( 'lcw_logout_redirect_page' ) );
        wp_safe_redirect( $redirect_url );
        exit;
    }
}
add_action( 'wp_logout', 'lcw_redirect_user_after_logout' );
