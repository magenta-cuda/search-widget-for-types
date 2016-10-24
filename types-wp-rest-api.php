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

# This implements the WP REST API for Toolset Types custom post types and custom fields for the HTTP methods OPTIONS and GET.
# I.e., read only, the HTTP methods POST and PUT not currently supported. The real work is done by the widget's search functionality.
# This code just translates a REST request to a search widget request and the search widget result to a REST result. This code
# uses the WP_REST_Posts_Controller class and has the WP REST API plugin as a prerequisite.
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
# Search Request
#
#      curl http://me.local.com/wp-json/mcst/v1/cars?search=hurst%20shifter
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
# Request by Types child of relationship
#
#      curl http://me.local.com/wp-json/mcst/v1/cars?mcst-childof-manufacturer=25
#
#Request by Types parent of relationship
#
#      curl http://me.local.com/wp-json/mcst/v1/cars?mcst-parentof-engine=79
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
    public $fields;

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
        global $wpdb;
        $fields = [ ];
        $params = $request->get_params( );
        foreach( $params as $field => $value ) {
            if ( taxonomy_exists( $field ) ) {
                # The REST API requires the term id for taxonomy fields so replace the term name/slug with the term id
                $tax_ids = array_unique( array_merge(
                    get_terms( [ 'taxonomy' => $field, 'name' => $value, 'fields' => 'ids' ] ),
                    get_terms( [ 'taxonomy' => $field, 'slug' => $value, 'fields' => 'ids' ] )
                ) );
                $request->set_param( $field, $tax_ids ? $tax_ids : [ 0 ] );
            } else if ( $value && in_array( $field, $this->fields ) ) {
                # this field is a Toolset Types custom field
                $fields[ $field ] = $value;
            }
        }
        if ( $fields ) {
            $post_type = $this->post_type;
            add_filter( 'posts_clauses_request', function( $clauses, $query ) use ( $fields, $post_type ) {
                # setup the environment for the search widget
                global $wpdb;
                # TODO: Is the following specific enough to intercept only the main query of parent::get_items( )?
                if ( empty( $query->query_vars[ 'post_type' ] ) || $query->query_vars[ 'post_type' ] !== $post_type ) {
                    return $clauses;
                }
                $orig_request = $_REQUEST;
                $_REQUEST = [ ];
                $_REQUEST[ 'post_type' ] = $post_type;
                $_REQUEST[ 'search_types_custom_fields_and_or' ] = 'and';
                foreach ( $fields as $field => $value ) {
                    if ( is_array( $value ) ) {
                        $values = $value;
                    } else {
                        $values = [ $value ];
                    }
                    # the REST parameters must be mapped into the search widget's search parameters
                    if ( ( $parentof = preg_match( '/mcst-parentof-(\w+)/', $field, $matches ) ) || preg_match( '/mcst-childof-(\w+)/', $field, $matches ) ) {
                        # this is search by parent-of or child-of relationship
                        error_log( '$matches=' . print_r( $matches, true ) );
                        $titles = array_filter( $values, function( $value ) {
                            return !is_numeric( $value );
                        } );
                        if ( $titles ) {
                            # some values are not post ids so try and match on post titles
                            $ids = array_diff( $values, $titles );
                            $titles = array_map( function( $title ) {
                                global $wpdb;
                                return 'post_title LIKE \'%' . trim( $wpdb->prepare( '%s', $title ), '\'"' ) . '%\'';
                            }, $titles );
                            error_log( '$titles=' . print_r( $titles, true ) );
                            $ids_from_titles = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type = '{$matches[1]}' AND post_status = 'publish' AND ( "
                                . implode( ' OR ', $titles ) . ' )' );
                            error_log( '$ids_from_titles=' . print_r( $ids_from_titles, true ) );
                            $values = array_unique( array_merge( $ids, $ids_from_titles ) );
                            if ( !count( $values ) ) {
                                $values[ ] = '0';
                            }
                        }
                        if ( is_array( $value ) || ( $titles &&  count( $values ) > 1 ) ) {
                            $value = $values;
                        } else {
                            $value = $values[ 0 ];
                        }                        
                        if ( $parentof ) {
                            $_REQUEST[ "inverse_{$matches[1]}__wpcf_belongs_{$post_type}_id" ] = $value;
                        } else {
                            $_REQUEST[ "_wpcf_belongs_{$matches[1]}_id" ] = $value;
                        }
                    } else {
                        $wpcf_field = self::$wpcf_fields[ $field ];
                        $field_type = $wpcf_field[ 'type' ];
                        $field_data = $wpcf_field[ 'data' ];
                        switch ( $field_type ) {
                        case 'checkboxes':
                        case 'radio':
                        case 'select':
                            # the checkboxes, radio and select values must be re-mapped to its internal value
                            $values =array_map( function( $value ) use ( $field_data, $field_type ) {
                                $value_lower = strtolower( $value );
                                foreach ( $field_data[ 'options' ] as $key => $option ) {
                                    if ( ( ( $field_type === 'checkboxes' || $field_type === 'select' ) && strtolower( $option[ 'title' ] ) === $value_lower )
                                        || ( $field_type === 'radio' && ( strtolower( $option[ 'title' ] ) === $value_lower
                                            || strtolower( $option[ 'display_value' ] ) === $value_lower ) ) ) {
                                        return $key;
                                    }
                                }
                                return '';
                            }, $values );
                            break;
                        case 'checkbox':
                            # for a checkbox set truthy values to the internal true value
                            $values = array_map( function( $value ) use ( $field_data ) {
                                $value_lower = strtolower( $value );
                                if ( $value_lower === strtolower( $field_data[ 'set_value' ] ) || $value_lower === '1' || $value_lower === 'true' || $value_lower === 'yes'
                                    || $value_lower === strtolower( __( 'yes', Search_Types_Custom_Fields_Widget::LANGUAGE_DOMAIN ) ) ) {
                                    return $field_data[ 'set_value' ];
                                } else {
                                    return '0';
                                }
                            }, $values );
                            break;
                        default:
                            break;
                        }
                        if ( is_array( $value ) ) {
                            $value = $values;
                        } else {
                            $value = $values[ 0 ];
                        }
                        $_REQUEST[ "wpcf-$field" ] = $value;
                    }
                }
                $query = new WP_Query( [ 's' => 'XQ9Z5', 'fields' => 'ids', 'mcst' => true ] );
                # TODO: add clauses for Types custom fields here
                if ( $query->posts ) {
                    $clauses[ 'where' ] .= " AND ( {$wpdb->posts}.ID IN ( " . implode( ', ', $query->posts ) . ' ) ) ';
                } else {
                    $clauses[ 'where' ] .= ' AND ( 1 = 2 )';
                }
                $_REQUEST = $orig_request;
                return $clauses;
            }, 10, 2 );
            # not necessary to remove Toolset Types custom fields as parent::get_items() ignores fields it doesn't know about
        }
        $response = parent::get_items( $request );
        return $response;
    }

    public function get_collection_params( ) {
        $params = parent::get_collection_params( );
        if ( !empty( $this->fields ) ) {
            foreach ( $this->fields as $field ) {
                if ( array_key_exists( $field, $params ) ) {
                    # don't override parent's params - TODO: override may be better?
                    continue;
                }
                if ( preg_match( '/mcst-parentof-(\w+)/', $field ) ) {
                    $description = sprintf( __( 'Limit result set to the %s parent of the specified %s.' ), $this->post_type, substr( $field, 14 ) );
                } else if ( preg_match( '/mcst-childof-(\w+)/', $field ) ) {
                    $description = sprintf( __( 'Limit result set to the %s children of the specified %s.' ), $this->post_type, substr( $field, 13 ) );
                } else {
                    $description = sprintf( __( 'Limit result set to all items that have the specified value assigned in the %s Types custom field.' ), $field );
                }
                $params[ $field ] = [
                    'description'       => $description,
                    'type'              => 'array',
                    'sanitize_callback' => [ $this, '_sanitize_collection_param' ],
                    'default'           => [ ],
                ];
            }
        }
        return $params;
    }
    
    protected function add_additional_fields_to_object( $object, $request ) {

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
      return $object;
    }
 
    protected function _get_types_field( $object, $field_name, $request, $object_type ) {
        global $post;
        static $models = [ ];
        # cache the model for reuse by later calls on the same post with another field
        if ( !array_key_exists( $post->ID, $models ) ) {
            $models[ $post->ID ] = Search_Types_Custom_Fields_Widget::get_items_for_post( $post, $this->post_type );
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
    
    public function _sanitize_collection_param( $values, $request, $name ) {
        # N.B. $value may be an array
        if ( !is_array( $values ) ) {
            $values = [ $values ];
        }
        if ( preg_match( '/mcst-parentof-(\w+)/', $name ) || preg_match( '/mcst-childof-(\w+)/', $name ) ) {
            #$type = 'id';
            $type = 'string';
        } else {
            $type = self::$wpcf_fields[ $name ][ 'type' ];
        }
        foreach ( $values as &$value ) {
            switch ( $type ) {
            case 'id':
                $value = (string) absint( $value );
                break;
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
        return $values;
    }

    public static function _sanitize_field( $values, $request, $name ) {
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
    
    public function additional_prepare_item_for_response( $response, $post, $request ) {
        foreach ( $response->data as $name => &$values ) {
            if ( taxonomy_exists( $name ) ) {
                $values = get_terms( [ 'taxonomy' => $name, 'include' => $values, 'fields' => 'names' ] );
            }
        }
        return $response;
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
            $post_type = $this->prepare_item_for_response( $obj, $request );
            $data[ $obj->name ] = $this->prepare_response_for_collection( $post_type );
        }
        return rest_ensure_response( $data );
    }
}

add_action( 'rest_api_init', function( ) {
    global $wp_post_types, $wp_taxonomies, $wpdb;
    # get Types custom fields, Types custom post types and Types custom taxonomies from the options database
    $wpcf_custom_types = get_option( 'wpcf-custom-types', [ ] );
    $wpcf_fields = get_option( 'wpcf-fields', [ ] );
    $wpcf_custom_taxonomies = get_option( 'wpcf-custom-taxonomies', [ ] );
    MCST_WP_REST_Posts_Controller::set_wpcf_fields( $wpcf_fields );
    $results=$wpdb->get_results( <<<EOD
SELECT g.meta_value custom_types, f.meta_value fields FROM $wpdb->postmeta f, $wpdb->postmeta g
    WHERE f.post_id = g.post_id AND f.meta_key = '_wp_types_group_fields' AND g.meta_key = '_wp_types_group_post_types'
EOD
    );
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
    foreach ( $fields_of as $custom_type => $fields ) {
        # add REST attributes to the global $wp_post_types
        if ( empty( $wp_post_types[ $custom_type ] ) ) {
            continue;
        }
        $wp_post_types[ $custom_type ]->show_in_rest           = TRUE;
        #$wp_post_types[ $custom_type ]->rest_base             = $custom_type;
        $rest_base = $wp_post_types[ $custom_type ]->rest_base = strtolower( $wpcf_custom_types[ $custom_type ][ 'labels' ][ 'name' ] );
        $wp_post_types[ $custom_type ]->rest_controller_class  = 'MCST_WP_REST_Posts_Controller';
        $singular_name                                         = $wpcf_custom_types[ $custom_type ][ 'labels' ][ 'singular_name' ];
        MCST_WP_REST_Posts_Controller::add_mapping_model( strtoupper( substr( $rest_base, 0, 1 ) ) . substr( $rest_base, 1 ),
                                                          strtoupper( substr( $singular_name, 0, 1 ) ) . substr( $singular_name, 1 ) );
        # create a REST controller for this Types custom post type
        $controller = new MCST_WP_REST_Posts_Controller( $custom_type );
        $controller->fields = [ ];
        # add the Types custom fields, custom taxonomies, child of and parent of fields
        if ( $widget_fields = Search_Types_Custom_Fields_Widget::get_fields( $custom_type ) ) {
            $widget_fields = array_filter( array_map( function( $field ) {
                # for now only do Types custom fields which have prefix "wpcf-"
                if ( substr_compare( $field, 'wpcf-', 0, 5 ) === 0 ) {
                    # Toolset Types custom field
                    return substr( $field, 5 );
                } else if ( substr_compare( $field, 'tax-tag-', 0, 8 ) === 0 ) {
                    # Toolset Types custom taxonomy
                    return FALSE;
                } else if ( substr_compare( $field, '_wpcf_belongs_', 0, 14 ) === 0 && substr_compare( $field, '_id', -3, 3 ) === 0 ) {
                    # child of psuedo field
                    # replace the ugly psuedo field name with a more user friendly name
                    return 'mcst-childof-' . substr( $field, 14, -3 );
                } else if ( preg_match( '/^inverse_(\w+)__wpcf_belongs_(\w+)_id$/', $field, $matches ) === 1 ) {
                    # parent of psuedo field
                    # replace the ugly psuedo field name with a more user friendly name
                    return "mcst-parentof-$matches[1]";
                } else {
                    return FALSE;
                }
            }, $widget_fields ) );
            #$fields = array_intersect( $fields, $widget_fields );
            foreach ( $widget_fields as $field ) {
                if ( preg_match( '/mcst-parentof-(\w+)/', $field ) ) {
                    $description = sprintf( __( 'The %s children of this %s.' ), substr( $field, 14 ), $custom_type );
                } else if ( preg_match( '/mcst-childof-(\w+)/', $field ) ) {
                    $description = sprintf( __( 'The %s parent of this %s.' ), substr( $field, 13 ), $custom_type );
                } else {
                    $wpcf_field = $wpcf_fields[ $field ];
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
                            'sanitize_callback' => [ $controller, '_sanitize_field' ],
                        ]
                    ]
                ] );
                $controller->fields[ ] = $field;
            }
        }
        $controller->register_routes( );
        add_filter( "rest_prepare_{$custom_type}", [ $controller, 'additional_prepare_item_for_response' ], 10, 3 );
    }

    # add REST attributes to the global $wp_taxonomies
    $wpcf_custom_taxonomies = get_option( 'wpcf-custom-taxonomies', [ ] );
    foreach ( $wpcf_custom_taxonomies as $tax_slug => $taxonomy ) {
        if ( empty( $taxonomy[ '_builtin' ] ) ) {
            $wp_taxonomies[ $tax_slug ]->show_in_rest          = true;
            $wp_taxonomies[ $tax_slug ]->rest_base             = $tax_slug;
            $wp_taxonomies[ $tax_slug ]->rest_controller_class = 'WP_REST_Terms_Controller';
        }
    }

    $controller = new MCST_WP_REST_Post_Types_Controller( );
    $controller->register_routes( );
} );

add_filter( 'rest_prepare_post_type', function( $response, $post_type, $request ) {
    if ( in_array( $post_type->name, MCST_WP_REST_Posts_Controller::$post_types ) ) {
        # fix namespace on links to Types custom post types
        $response->remove_link( 'https://api.w.org/items' );
        $response->add_links( [	'https://api.w.org/items' => [ 'href' => rest_url( MCST_WP_REST_Posts_Controller::REST_NAME_SPACE . '/'
            . ( !empty( $post_type->rest_base ) ? $post_type->rest_base : $post_type->name ) ) ] ] );
    }
    return $response;
}, 10, 3 );

# mcst-api.js will be used to support a backbone.js client

/*
add_action( 'wp_enqueue_scripts', function( ) {
		wp_enqueue_script( 'mcst-api', plugins_url( 'js/mcst-api.js', __FILE__ ), [ 'jquery', 'backbone', 'underscore' ], FALSE, TRUE );
		wp_localize_script( 'mcst-api', 'mcstApiSettings', MCST_WP_REST_Posts_Controller::get_settings( ) );
    
}, -100 );
*/

# The following AJAX actions are for standalone mode. See .../wp-content/plugins/search-types-custom-fields-widget/backbone-client.html.

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
