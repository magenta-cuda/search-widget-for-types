<?php

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
        error_log( 'MCST_WP_REST_Posts_Controller::get_items():$request=' . print_r( $request, true ) );
        $fields = [ ];
        $params = $request->get_params( );
        error_log( 'get_items():$params=' . print_r( $params, true ) );
        foreach( $params as $field => $value ) {
            if ( $value && in_array( $field, $this->fields ) ) {
                $fields[ $field ] = $value;
            }
        }
        if ( $fields ) {
            $post_type = $this->post_type;
            add_filter( 'posts_clauses_request', function( $clauses, $query ) use ( $fields, $post_type ) {
                # TODO: Is the following specific enough to intercept only the main query of parent::get_items( )?
                if ( empty( $query->query_vars[ 'post_type' ] ) || $query->query_vars[ 'post_type' ] !== $post_type ) {
                    return $clauses;
                }
                error_log( 'FILTER:posts_clauses_request():$_REQUEST=' . print_r( $_REQUEST, true ) );
                error_log( 'FILTER:posts_clauses_request():$clauses=' . print_r( $clauses, true ) );
                $orig_request = $_REQUEST;
                $_REQUEST = [ ];
                $_REQUEST[ 'post_type' ] = $post_type;
                $_REQUEST[ 'search_types_custom_fields_and_or' ] = 'and';
                foreach ( $fields as $field => $value ) {
                    $_REQUEST[ "wpcf-$field" ] = $value;
                }
                $query = new WP_Query( [ 's' => 'XQ9Z5', 'fields' => 'ids', 'mcst' => true ] );
                # TODO: add clauses for Types custom fields here
                if ( $query->posts ) {
                    error_log( 'FILTER:posts_clauses_request():$query->posts=' . print_r( $query->posts, true ) );
                    $clauses[ 'where' ] .= ' AND ( wp_posts.ID IN ( ' . implode( ', ', $query->posts ) . ' ) ) ';
                } else {
                }
                $_REQUEST = $orig_request;
                error_log( 'FILTER:posts_clauses_request():$_REQUEST=' . print_r( $_REQUEST, true ) );
                error_log( 'FILTER:posts_clauses_request():$clauses=' . print_r( $clauses, true ) );
                return $clauses;
            }, 10, 2 );
        }
        $response = parent::get_items( $request );
        return $response;
    }

    public function get_collection_params( ) {
        $params = parent::get_collection_params( );
        foreach ( $this->fields as $field ) {
          $params[ $field ] = array(
            'description'       => sprintf( __( 'Limit result set to all items that have the specified value assigned in the %s Types custom field.' ), $field ),
            'type'              => 'array',
            'sanitize_callback' => null,   # 'sanitize_text_field',   # TODO: will this work for everything?
            'default'           => array(),
          );
        }
        return $params;
    }
    
    protected function _get_types_field( $object, $field_name, $request, $object_type ) {
        global $post;
        static $models = [ ];
        error_log( '_get_types_field():$post=' . print_r( $post, true ) );
        error_log( '_get_types_field():$object=' . print_r( $object, true ) );
        error_log( '_get_types_field():$field_name=' . $field_name );
        error_log( '_get_types_field():$object_type=' . $object_type );
        if ( !array_key_exists( $post->ID, $models ) ) {
            $models[ $post->ID ] = Search_Types_Custom_Fields_Widget::get_items_for_post( $post, $this->post_type );
        }
        $model = $models[ $post->ID ];
        if ( isset( $model[ $field_name ] ) ) {
            return $model[ $field_name ];
        } else {
            return '';   # TODO: what should this be?
        }
    }
}

add_action( 'rest_api_init', function( ) {
    error_log( 'ACTION:rest_api_init():backtrace=' . print_r( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), true ) );
    global $wp_post_types, $wp_taxonomies, $wpdb;
    # get Types custom fields and Types custom post types from the database
    error_log( 'ACTION:rest_api_init():$wp_post_types=' . print_r( $wp_post_types, true ) );
    $wpcf_custom_types = get_option( 'wpcf-custom-types', [ ] );
    error_log( 'ACTION:rest_api_init():$wpcf_custom_types=' . print_r( $wpcf_custom_types, true ) );
    $wpcf_fields = get_option( 'wpcf-fields', [ ] );
    error_log( 'ACTION:rest_api_init():$wpcf_fields=' . print_r( $wpcf_fields, true) );
    $results=$wpdb->get_results( <<<EOD
SELECT g.meta_value custom_types, f.meta_value fields FROM wp_postmeta f, wp_postmeta g
    WHERE f.post_id = g.post_id AND f.meta_key = '_wp_types_group_fields' AND g.meta_key = '_wp_types_group_post_types'
EOD
    );
    error_log( 'ACTION:rest_api_init():$results=' . print_r( $results, true ) );
    # collect the Types custom fields for each Types custom post type
    $fields_of = [ ];
    foreach ( $results as $result ) {
        foreach ( array_filter( explode( ',', $result->custom_types ) ) as $custom_type ) {
            if ( !array_key_exists( $custom_type, $fields_of ) ) {
                $fields_of[ $custom_type ] = [ ];
            }
            $fields_of[ $custom_type ] = array_merge( $fields_of[ $custom_type ], array_filter( explode( ',', $result->fields ) ) );
        }
    }
    error_log( 'ACTION:rest_api_init():$fields_of=' . print_r( $fields_of, true ) );
    foreach ( $fields_of as $custom_type => $fields ) {
        error_log( 'ACTION:rest_api_init():$custom_type=' . $custom_type );
        # add REST attributes to the global $wp_post_types
        if ( isset( $wp_post_types[ $custom_type ] ) ) {
            $wp_post_types[ $custom_type ]->show_in_rest          = true;
            $wp_post_types[ $custom_type ]->rest_base             = $custom_type;
            $wp_post_types[ $custom_type ]->rest_controller_class = 'MCST_WP_REST_Posts_Controller';
        }
        # create a REST controller for this Types custom post type
        $controller = new MCST_WP_REST_Posts_Controller( $custom_type );
        # add the Types custom fields 
        $controller->fields = [ ];
        foreach ( $fields as $field ) {
            $wpcf_field = $wpcf_fields[ $field ];
            error_log( 'ACTION:rest_api_init()' . "\t" . '$field=' . $wpcf_field[ 'name' ] . '(' . $wpcf_field[ 'type' ] . ')' );
            register_rest_field( $custom_type, $field, [
                'get_callback' => [ $controller, '_get_types_field' ],
                'update_callback' => null,
                'schema' => [
                    # TODO:
                    'description' => __( 'The description for the resource.' ),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'arg_options' => [
                        'sanitize_callback' => null,
                    ]
                ]
            ] );
            $controller->fields[ ] = $field;
        }
        error_log( 'ACTION:rest_api_init():$controller=' . print_r( $controller, true ) );
        $controller->register_routes( );
        # add REST attributes to the global $wp_taxonomies
        error_log( 'ACTION:rest_api_init():$wp_taxonomies=' . print_r( $wp_taxonomies, true ) );
        $wpcf_custom_taxonomies = get_option( 'wpcf-custom-taxonomies', [ ] );
        error_log( 'ACTION:rest_api_init():$wpcf_custom_taxonomies=' . print_r( $wpcf_custom_taxonomies, true ) );
        foreach ( $wpcf_custom_taxonomies as $tax_slug => $taxonomy ) {
            if ( empty( $taxonomy[ '_builtin' ] ) ) {
                $wp_taxonomies[ $tax_slug ]->show_in_rest          = true;
                $wp_taxonomies[ $tax_slug ]->rest_base             = $tax_slug;
                $wp_taxonomies[ $tax_slug ]->rest_controller_class = 'WP_REST_Terms_Controller';
            }
        }
        error_log( 'ACTION:rest_api_init():$wp_taxonomies=' . print_r( $wp_taxonomies, true ) );
        error_log( 'ACTION:rest_api_init():$controller->get_collection_params()=' . print_r( $controller->get_collection_params(), true ) );
        error_log( 'ACTION:rest_api_init():$controller->get_item_schema()=' . print_r( $controller->get_item_schema(), true ) );
    }
} );

?>
