<?php

# This implements the WP REST API for Toolset Types custom post types and custom fields for the HTTP methods OPTIONS and GET 
# I.e., read only the HTTP methods POST and PUT not currently supported
#
# curl examples
#
# Schema
#
#      curl -X OPTIONS http://me.local.com/wp-json/mcst/v1/car
#
# Request by ID
#
#      curl http://me.local.com/wp-json/mcst/v1/car/78
#
# Request by taxonomy
#
#      curl http://me.local.com/wp-json/mcst/v1/car?body-type=3
#
# Request by Types custom field
#
#      curl http://me.local.com/wp-json/mcst/v1/car?brand=Plymouth
#
#      curl -g "http://me.local.com/wp-json/mcst/v1/car?brand[]=Plymouth&brand[]=Dodge"
#
# Custom Post Types
#
#      curl http://me.local.com/wp-json/mcst/v1/types

if ( !class_exists( 'WP_REST_Posts_Controller' ) ) {
    return;
}

class MCST_WP_REST_Posts_Controller extends WP_REST_Posts_Controller {

    const REST_NAME_SPACE = 'mcst/v1';

    public static $post_types = [ ];

    public function __construct( $post_type ) {
        self::$post_types[ ] = $post_type;
        $this->post_type = $post_type;
        $this->namespace = self::REST_NAME_SPACE;
        $obj = get_post_type_object( $post_type );
        $this->rest_base = ! empty( $obj->rest_base ) ? $obj->rest_base : $obj->name;
    }

    public function register_routes() {
        # Only WP_REST_Server::READABLE methods are supported
        register_rest_route( $this->namespace, '/' . $this->rest_base, [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_items' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
                'args'                => $this->get_collection_params(),
            ],
            'schema' => [ $this, 'get_public_item_schema' ]
        ] );
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_item' ],
                'permission_callback' => [ $this, 'get_item_permissions_check' ],
                'args'                => [ 'context' => $this->get_context_param( [ 'default' => 'view' ] ) ]
            ],
            'schema' => [ $this, 'get_public_item_schema' ]
        ] );
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
          if ( array_key_exists( $field, $params ) ) {
              # don't override parent's params - TODO: override may be better?
              continue;
          }
          $params[ $field ] = array(
            'description'       => sprintf( __( 'Limit result set to all items that have the specified value assigned in the %s Types custom field.' ), $field ),
            'type'              => 'array',
            'sanitize_callback' => [ $this, '_sanitize_field' ],
            'default'           => array(),
          );
        }
        return $params;
    }
    
    protected function add_additional_fields_to_object( $object, $request ) {

      error_log( 'MCST_WP_REST_Posts_Controller::add_additional_fields_to_object():$object=' . print_r( $object, true ) );

      $additional_fields = $this->get_additional_fields();

      foreach ( $additional_fields as $field_name => $field_options ) {

        if ( array_key_exists( $field_name, $object ) ) {
          # don't override parent's fields TODO: override may be better, e.g. my taxonomy values may be better
          continue;
        }

        if ( ! $field_options['get_callback'] ) {
          continue;
        }

        $object[ $field_name ] = call_user_func( $field_options['get_callback'], $object, $field_name, $request, $this->get_object_type() );
      }
      error_log( 'MCST_WP_REST_Posts_Controller::add_additional_fields_to_object():$object=' . print_r( $object, true ) );
      return $object;
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
    
    public function _sanitize_field( $value, $request, $name ) {
        error_log( 'MCST_WP_REST_Posts_Controller::_sanitize_field():$name=' . $name );
        error_log( 'MCST_WP_REST_Posts_Controller::_sanitize_field():$value=' . print_r( $value, true ) );
        error_log( 'MCST_WP_REST_Posts_Controller::_sanitize_field():$request=' . print_r( $request, true ) );
        # N.B. $value may be an array
        return $value;
    }
}

class MCST_WP_REST_Post_Types_Controller extends WP_REST_Post_Types_Controller {

    const REST_NAME_SPACE = 'mcst/v1';
    protected static $post_types = [ ];

    public function __construct() {
        $this->namespace = self::REST_NAME_SPACE;
        $this->rest_base = 'types';
    }

    public function get_items( $request ) {
        global $wp_post_types;
        $data = [];
        foreach ( self::$post_types as $post_type ) {
            $obj = $wp_post_types[ $post_type ];
            if ( empty( $obj->show_in_rest ) || ( 'edit' === $request['context'] && ! current_user_can( $obj->cap->edit_posts ) ) ) {
                continue;
            }
            $post_type = $this->prepare_item_for_response( $obj, $request );
            $data[ $obj->name ] = $this->prepare_response_for_collection( $post_type );
        }
        return rest_ensure_response( $data );
    }
    
    public static function add_post_type( $post_type ) {
        self::$post_types[ ] = $post_type;
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
        if ( $widget_fields = Search_Types_Custom_Fields_Widget::get_fields( $custom_type ) ) {        
            error_log( 'ACTION:rest_api_init():$widget_fields=' . print_r( $widget_fields, true ) );
            $widget_fields = array_map( function( $field ) {
                return substr( $field, 5 );
            }, array_filter( $widget_fields, function( $field ) {
                # for now only do Types custom fields which have prefix "wpcf-"
                return substr_compare( $field, 'wpcf-', 0, 5 ) === 0;
            } ) );
            error_log( 'ACTION:rest_api_init():$widget_fields=' . print_r( $widget_fields, true ) );
            error_log( 'ACTION:rest_api_init():$fields=' . print_r( $fields, true ) );
            $controller->fields = [ ];
            $fields = array_intersect( $fields, $widget_fields );
            # TODO: do intersection for now but more is possible
            error_log( 'ACTION:rest_api_init():$fields=' . print_r( $fields, true ) );
            foreach ( $fields as $field ) {
                $wpcf_field = $wpcf_fields[ $field ];
                error_log( 'ACTION:rest_api_init()' . "\t" . '$field=' . $wpcf_field[ 'name' ] . '(' . $wpcf_field[ 'type' ] . ')' );
                register_rest_field( $custom_type, $field, [
                    'get_callback' => [ $controller, '_get_types_field' ],
                    'update_callback' => null,
                    'schema' => [
                        # TODO:
                        'description' => $wpcf_field[ 'description' ],
                        'type'        => 'string',
                        'context'     => [ 'view' ],
                        'arg_options' => [
                            'sanitize_callback' => null,
                        ]
                    ]
                ] );
                $controller->fields[ ] = $field;
            }
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
        MCST_WP_REST_Post_Types_Controller::add_post_type( $custom_type );
    }
    $controller = new MCST_WP_REST_Post_Types_Controller( );
    $controller->register_routes( );
} );

add_filter( 'rest_prepare_post_type', function( $response, $post_type, $request ) {
    error_log( 'FILTER:rest_prepare_post_type():$response=' . print_r( $response, true ) );
    error_log( 'FILTER:rest_prepare_post_type():$post_type=' . print_r ( $post_type, true ) );
    error_log( 'FILTER:rest_prepare_post_type():$response=' . print_r( $request, true ) );
    if ( in_array( $post_type->name, MCST_WP_REST_Posts_Controller::$post_types ) ) {
        # fix namespace on links to Types custom post types
        $response->remove_link( 'https://api.w.org/items' );
        $response->add_links( [	'https://api.w.org/items' => [ 'href' => rest_url( MCST_WP_REST_Posts_Controller::REST_NAME_SPACE . '/' . $post_type->name ) ] ] );
    }
    error_log( 'FILTER:rest_prepare_post_type():$response=' . print_r( $request, true ) );
    return $response;
}, 10, 3 );

?>
