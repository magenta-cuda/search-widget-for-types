<?php
/*
    Copyright 2016 Magenta Cuda

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

# This implements the WP REST API for Toolset Types custom post types and custom fields for the HTTP methods OPTIONS and GET 
# I.e., read only the HTTP methods POST and PUT not currently supported
#
# curl examples
#
# Schema
#
#      curl -X OPTIONS http://me.local.com/wp-json/mcst/v1/cars
#
# Request by ID
#
#      curl http://me.local.com/wp-json/mcst/v1/cars/78
#
# Request by taxonomy
#
#      curl http://me.local.com/wp-json/mcst/v1/cars?body-type=3
#
# Request by Types custom field
#
#      curl http://me.local.com/wp-json/mcst/v1/cars?brand=Plymouth
#
#      curl -g "http://me.local.com/wp-json/mcst/v1/cars?brand[]=Plymouth&brand[]=Dodge"
#
# Custom Post Types
#
#      curl http://me.local.com/wp-json/mcst/v1/types
#
#
#
#      http://me.local.com/wp-content/plugins/search-types-custom-fields-widget/tutorials/backbone-client.html
#

if ( !class_exists( 'WP_REST_Posts_Controller' ) ) {
    return;
}

class MCST_WP_REST_Posts_Controller extends WP_REST_Posts_Controller {

    const REST_NAME_SPACE = 'mcst/v1';

    public static $post_types     = [ ];
    public static $mapping_models = [ ];
    protected static $wpcf_fields;

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
          if ( preg_match( '/mcst-parentof-(\w+)/', $field ) || preg_match( '/mcst-childof-(\w+)/', $field ) ) {
              # skip child and parent fields
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
            error_log( '_get_types_field():$models[ $post->ID ]=' . print_r( $models[ $post->ID ], true ) );
        }
        $model = $models[ $post->ID ];
        if ( isset( $model[ $field_name ] ) ) {
            return $model[ $field_name ];
        } else {
            # handle psuedo fields here
            # map the user friendly name to the real model psuedo field name
            if ( preg_match( '/mcst-parentof-(\w+)/', $field_name, $matches ) ) {
                $mcst_field_name = "$matches[1]_id_for";
            } else if ( preg_match( '/mcst-childof-(\w+)/', $field_name, $matches ) ) {
                $mcst_field_name = "$matches[1]_id_of";
            } else {
                # TODO: Handle name mismatch
                return '';   # TODO: what should this be?
            }
            if ( isset( $model[ $mcst_field_name ] ) ) {
                return $model[ $mcst_field_name ];
            } else {
                # TODO: Handle name mismatch
                return '';   # TODO: what should this be?
            }
        }
    }
    
    public function _sanitize_field( $values, $request, $name ) {
        error_log( 'MCST_WP_REST_Posts_Controller::_sanitize_field():$name=' . $name );
        error_log( 'MCST_WP_REST_Posts_Controller::_sanitize_field():$values=' . print_r( $values, true ) );
        error_log( 'MCST_WP_REST_Posts_Controller::_sanitize_field():$request=' . print_r( $request, true ) );
        # N.B. $value may be an array
        if ( !is_array( $values ) ) {
            $values = [ $values ];
        }
        $type = self::$wpcf_fields[ $name ][ 'type' ];
        foreach ( $values as &$value ) {
            switch ( $type ) {
            case 'numeric':
                if ( !is_numeric( $value ) ) {
                    $value = '0';
                }
                break;
            default:
                $value = sanitize_text_field( $value );
                break;
            }
        }
        unset( $value );
        error_log( 'MCST_WP_REST_Posts_Controller::_sanitize_field():$values=' . print_r( $values, true ) );
        return $values;
    }

    public static function add_mapping_model( $collection, $model ) {
        self::$mapping_models[ $collection ] = $model;
    }

    public static function set_wpcf_fields( $wpcf_fields ) {
        self::$wpcf_fields = $wpcf_fields;
    }

    public static function get_settings( ) {
        if ( !self::$mapping_models ) {
            $wpcf_custom_types = get_option( 'wpcf-custom-types', [ ] );
            foreach ( get_option( 'wpcf-custom-types', [ ] ) as $custom_type ) {
                $rest_base = strtolower( $custom_type[ 'labels' ][ 'name' ] );
                $singular_name = $custom_type[ 'labels' ][ 'singular_name' ];
                self::add_mapping_model( strtoupper( substr( $rest_base, 0, 1 ) ) . substr( $rest_base, 1 ),
                                         strtoupper( substr( $singular_name, 0, 1 ) ) . substr( $singular_name, 1 ) );
            }
        }
        self::add_mapping_model( 'Types', 'Type' );
        $settings = [
            'root'          => esc_url_raw( get_rest_url() ),
            'nonce'         => wp_create_nonce( 'wp_rest' ),
            'versionString' => self::REST_NAME_SPACE . '/',
            'mapping'       => [
                                   'models'      => self::$mapping_models,
                                   'collections' => [ ]
                               ]
        ];
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            wp_send_json_success( $settings );
        }
        return $settings;
    }
}

class MCST_WP_REST_Post_Types_Controller extends WP_REST_Post_Types_Controller {

    const REST_NAME_SPACE = 'mcst/v1';

    public function __construct() {
        $this->namespace = self::REST_NAME_SPACE;
        $this->rest_base = 'types';
    }

    public function get_items( $request ) {
        global $wp_post_types;
        $data = [];
        foreach ( MCST_WP_REST_Posts_Controller::$post_types as $post_type ) {
            $obj = $wp_post_types[ $post_type ];
            if ( empty( $obj->show_in_rest ) || ( 'edit' === $request['context'] && ! current_user_can( $obj->cap->edit_posts ) ) ) {
                continue;
            }
            error_log( 'MCST_WP_REST_Post_Types_Controller::get_items():$obj=' . print_r( $obj, true ) );
            $post_type = $this->prepare_item_for_response( $obj, $request );
            $data[ $obj->name ] = $this->prepare_response_for_collection( $post_type );
        }
        return rest_ensure_response( $data );
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
    MCST_WP_REST_Posts_Controller::set_wpcf_fields( $wpcf_fields );
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
            $wp_post_types[ $custom_type ]->show_in_rest           = TRUE;
            #$wp_post_types[ $custom_type ]->rest_base             = $custom_type;
            $rest_base = $wp_post_types[ $custom_type ]->rest_base = strtolower( $wpcf_custom_types[ $custom_type ][ 'labels' ][ 'name' ] );
            $wp_post_types[ $custom_type ]->rest_controller_class  = 'MCST_WP_REST_Posts_Controller';
            $singular_name                                         = $wpcf_custom_types[ $custom_type ][ 'labels' ][ 'singular_name' ];
            MCST_WP_REST_Posts_Controller::add_mapping_model( strtoupper( substr( $rest_base, 0, 1 ) ) . substr( $rest_base, 1 ),
                                                              strtoupper( substr( $singular_name, 0, 1 ) ) . substr( $singular_name, 1 ) );
        }
        # create a REST controller for this Types custom post type
        $controller = new MCST_WP_REST_Posts_Controller( $custom_type );
        # add the Types custom fields, custom taxonomies, child of and parent of fields
        if ( $widget_fields = Search_Types_Custom_Fields_Widget::get_fields( $custom_type ) ) {
            error_log( 'ACTION:rest_api_init():$widget_fields=' . print_r( $widget_fields, true ) );
            $widget_fields = array_filter( array_map( function( $field ) {
                # for now only do Types custom fields which have prefix "wpcf-"
                if ( substr_compare( $field, 'wpcf-', 0, 5 ) === 0 ) {
                    # Toolset Types custom field
                    return substr( $field, 5 );
                } else if ( substr_compare( $field, 'tax-tag-', 0, 8 ) === 0 ) {
                    # Toolset Types custom taxonomy
                    error_log( 'ACTION:rest_api_init():tax-tag-:field=' . substr( $field, 8 ) );
                    return FALSE;
                } else if ( substr_compare( $field, '_wpcf_belongs_', 0, 14 ) === 0 && substr_compare( $field, '_id', -3, 3 ) === 0 ) {
                    # child of psuedo field
                    error_log( 'ACTION:rest_api_init():_wpcf_belongs_:field=' . substr( $field, 14, -3 ) );
                    # replace the ugly psuedo field name with a more user friendly name
                    return 'mcst-childof-' . substr( $field, 14, -3 );
                } else if ( preg_match( '/^inverse_(\w+)__wpcf_belongs_(\w+)_id$/', $field, $matches ) === 1 ) {
                    # parent of psuedo field
                    error_log( 'ACTION:rest_api_init():$matches=' . print_r( $matches, true ) );
                    # replace the ugly psuedo field name with a more user friendly name
                    return "mcst-parentof-$matches[1]";
                } else {
                    return FALSE;
                }
            }, $widget_fields ) );
            error_log( 'ACTION:rest_api_init():$widget_fields=' . print_r( $widget_fields, true ) );
            $controller->fields = [ ];
            #$fields = array_intersect( $fields, $widget_fields );
            foreach ( $widget_fields as $field ) {
                if ( preg_match( '/mcst-parentof-(\w+)/', $field ) || preg_match( '/mcst-childof-(\w+)/', $field ) ) {
                    $description = 'TODO: child/parent description';
                } else {
                    $wpcf_field = $wpcf_fields[ $field ];
                    error_log( 'ACTION:rest_api_init()' . "\t" . '$field=' . $wpcf_field[ 'name' ] . '(' . $wpcf_field[ 'type' ] . ')' );
                    $description = $wpcf_field[ 'description' ];
                }
                register_rest_field( $custom_type, $field, [
                    'get_callback' => [ $controller, '_get_types_field' ],
                    'update_callback' => null,
                    'schema' => [
                        'description' => $description,
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
    }
    $controller = new MCST_WP_REST_Post_Types_Controller( );
    $controller->register_routes( );
} );

add_filter( 'rest_prepare_post_type', function( $response, $post_type, $request ) {
    if ( in_array( $post_type->name, MCST_WP_REST_Posts_Controller::$post_types ) ) {
        error_log( 'FILTER:rest_prepare_post_type():$request=' . print_r( $request, true ) );
        error_log( 'FILTER:rest_prepare_post_type():$post_type=' . print_r ( $post_type, true ) );
        error_log( 'FILTER:rest_prepare_post_type():$response=' . print_r( $response, true ) );
        # fix namespace on links to Types custom post types
        $response->remove_link( 'https://api.w.org/items' );
        $response->add_links( [	'https://api.w.org/items' => [ 'href' => rest_url( MCST_WP_REST_Posts_Controller::REST_NAME_SPACE . '/'
            . ( !empty( $post_type->rest_base ) ? $post_type->rest_base : $post_type->name ) ) ] ] );
        error_log( 'FILTER:rest_prepare_post_type():$response=' . print_r( $response, true ) );
    }
    return $response;
}, 10, 3 );

add_action( 'wp_enqueue_scripts', function( ) {
		wp_enqueue_script( 'mcst-api', plugins_url( 'mcst-api.js', __FILE__ ), [ 'jquery', 'backbone', 'underscore' ], FALSE, TRUE );
		wp_localize_script( 'mcst-api', 'mcstApiSettings', MCST_WP_REST_Posts_Controller::get_settings( ) );
    
}, -100 );

add_action( 'wp_ajax_mcst_get_mcst_settings', [ 'MCST_WP_REST_Posts_Controller', 'get_settings' ] );
add_action( 'wp_ajax_nopriv_mcst_get_mcst_settings', [ 'MCST_WP_REST_Posts_Controller', 'get_settings' ] );

add_action( 'wp_ajax_mcst_get_wp_settings', function( ) {
    wp_send_json_success( [
        'root'          => esc_url_raw( get_rest_url() ),
        'nonce'         => wp_create_nonce( 'wp_rest' ),
        'versionString' => 'wp/v2/',
		] );
} );

add_action( 'wp_ajax_nopriv_mcst_get_wp_settings', function( ) {
    do_action( 'wp_ajax_mcst_get_wp_settings' );
} );

?>
