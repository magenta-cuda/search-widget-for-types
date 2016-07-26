<?php

add_action( 'plugins_loaded', function( ) {
    global $wp_post_types,$wpdb;
    # get Types custom fields for Types custom post types
    error_log( '$wp_post_types=' . print_r( $wp_post_types, true ) );
    $wpcf_custom_types = get_option( 'wpcf-custom-types', [ ] );
    error_log( '$wpcf_custom_types=' . print_r( $wpcf_custom_types, true ) );
    $wpcf_fields = get_option( 'wpcf-fields', [ ] );
    error_log( '$wpcf_fields=' . print_r( $wpcf_fields, true) );
    $results=$wpdb->get_results( <<<EOD
SELECT g.meta_value custom_types,f.meta_value fields FROM wp_postmeta f,wp_postmeta g
    WHERE f.meta_key='_wp_types_group_fields' AND g.meta_key='_wp_types_group_post_types' AND f.post_id=g.post_id
EOD
    );
    error_log( '$results=' . print_r( $results, true ) );
    $fields_of = [ ];
    foreach ( $results as $result ) {
        foreach ( explode( ',', $result->custom_types ) as $custom_type ) {
            if ( !$custom_type ) {
                continue;
            }
            if ( !isset( $fields_of[ $custom_type ] ) ) {
                $fields_of[ $custom_type ] = [ ];
            }
            $fields_of[ $custom_type ] = array_merge( $fields_of[ $custom_type ], array_filter( explode( ',', $result->fields ) ) );
        }
    }
    error_log( '$fields_of=' . print_r( $fields_of, true ) );
    foreach ( $fields_of as $custom_type => $fields ) {
        error_log( '$custom_type=' . $custom_type );
        foreach ( $fields as $field ) {
            $wpcf_field = $wpcf_fields[ $field ];
            error_log( "\t" . '$field=' . $wpcf_field[ 'name' ] . '(' . $wpcf_field[ 'type' ] . ')' );
        }
    }
} );

?>