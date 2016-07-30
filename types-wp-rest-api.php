<?php

# This is draft and probably won't even run

if ( !class_exists( 'WP_REST_Posts_Controller' ) ) {
    return;
}

class MCST_WP_REST_Posts_Controller extends WP_REST_Posts_Controller {

    public function __construct( $post_type ) {
        $this->post_type = $post_type;
        $this->namespace = 'mcst/v1';
        $obj = get_post_type_object( $post_type );
        $this->rest_base = ! empty( $obj->rest_base ) ? $obj->rest_base : $obj->name;
    }

    protected function prepare_items_query( $prepared_args = array(), $request = null ) {
        $query_args = parent::prepare_items_query( $prepared_args, $request );
        return $query_args;
    }

    public function get_items( $request ) {
        add_filter( 'posts_clauses_request', function( $clauses, $query ) {
            error_log( 'FILTER:posts_clauses_request():$clauses=' . print_r( $clauses, true ) );
            # TODO: add clauses for Types custom fields here
            return $clauses;
        }, 10, 2 );
        $response = parent::get_items( $request );
        return $response;
    }

    public function get_collection_params( ) {
        $params = parent::get_collection_params( );
        foreach ( $this->fields as $field ) {
          $params[ $field ] = array(
            'description'       => sprintf( __( 'Limit result set to all items that have the specified value assigned in the %s Types custom field.' ), $field ),
            'type'              => 'array',
            'sanitize_callback' => 'wp_parse_id_list',
            'default'           => array(),
          );
        }
        return $params;
    }
    
    protected function _get_types_field( $object, $field_name, $request, $object_type ) {
        global $post;
        error_log( '_get_types_field():$post=' . print_r( $post, true ) );
        error_log( '_get_types_field():$object=' . print_r( $object, true ) );
        error_log( '_get_types_field():$field_name=' . $field_name );
        error_log( '_get_types_field():$object_type=' . $object_type );
        return 'TODO';
    }
}

add_action( 'rest_api_init', function( ) {
    error_log( 'action::rest_api_init():backtrace=' . print_r( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), true ) );
    global $wp_post_types,$wp_taxonomies,$wpdb;
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
        foreach ( array_filter( explode( ',', $result->custom_types ) ) as $custom_type ) {
            if ( !isset( $fields_of[ $custom_type ] ) ) {
                $fields_of[ $custom_type ] = [ ];
            }
            $fields_of[ $custom_type ] = array_merge( $fields_of[ $custom_type ], array_filter( explode( ',', $result->fields ) ) );
        }
    }
    error_log( '$fields_of=' . print_r( $fields_of, true ) );
    foreach ( $fields_of as $custom_type => $fields ) {
        error_log( '$custom_type=' . $custom_type );
        if ( isset( $wp_post_types[ $custom_type ] ) ) {
            $wp_post_types[ $custom_type ]->show_in_rest = true;
            $wp_post_types[ $custom_type ]->rest_base = $custom_type;
            $wp_post_types[ $custom_type ]->rest_controller_class = 'MCST_WP_REST_Posts_Controller';
        }
        $controller = new MCST_WP_REST_Posts_Controller( $custom_type );
        $controller->fields = [ ];
        foreach ( $fields as $field ) {
            $wpcf_field = $wpcf_fields[ $field ];
            error_log( "\t" . '$field=' . $wpcf_field[ 'name' ] . '(' . $wpcf_field[ 'type' ] . ')' );
            register_rest_field( $custom_type, $field, [
                'get_callback' => [ $controller, '_get_types_field' ],
                'update_callback' => null,
                'schema' => [
                    # TODO:
                    'description' => __( 'The description for the resource.' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'arg_options' => [
                        'sanitize_callback' => 'wp_filter_post_kses',
                    ]
                ]
            ] );
            $controller->fields[ ] = $field;
        }
        error_log( 'ACTION:rest_api_init():$controller=' . print_r( $controller, true ) );
        $controller->register_routes( );
        error_log( '$wp_taxonomies=' . print_r( $wp_taxonomies, true ) );
        $wpcf_custom_taxonomies = get_option( 'wpcf-custom-taxonomies', [ ] );
        error_log( '$wpcf_custom_taxonomies=' . print_r( $wpcf_custom_taxonomies, true ) );
        foreach ( $wpcf_custom_taxonomies as $tax_slug => $taxonomy ) {
            if ( !empty( $taxonomy[ '_builtin' ] ) ) {
                continue;
            }
            $wp_taxonomies[ $tax_slug ]->show_in_rest = true;
            $wp_taxonomies[ $tax_slug ]->rest_base = $tax_slug;
            $wp_taxonomies[ $tax_slug ]->rest_controller_class = 'WP_REST_Terms_Controller';
        }
        error_log( '$wp_taxonomies=' . print_r( $wp_taxonomies, true ) );
    }
} );

?>
