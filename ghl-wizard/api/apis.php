<?php
add_action('init', function() {
    require_once( __DIR__ . '/get-token.php');
    require_once( __DIR__ . '/get-tags.php');
    require_once( __DIR__ . '/get-campaigns.php');
    require_once( __DIR__ . '/get-workflows.php');
    require_once( __DIR__ . '/contacts.php');
    require_once( __DIR__ . '/get-custom-values.php');
    require_once( __DIR__ . '/get-custom-fields.php');
});