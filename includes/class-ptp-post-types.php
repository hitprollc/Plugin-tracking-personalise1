<?php
/**
 * Register custom post type for shipments.
 */

defined( 'ABSPATH' ) || exit;

class PTP_Post_Types {

    /**
     * Register the shipment post type.
     */
    public static function register(): void {
        $labels = [
            'name'                  => __( 'Envois', 'plugin-tracking-personalise' ),
            'singular_name'         => __( 'Envoi', 'plugin-tracking-personalise' ),
            'menu_name'             => __( 'Tracking', 'plugin-tracking-personalise' ),
            'add_new'               => __( 'Ajouter un envoi', 'plugin-tracking-personalise' ),
            'add_new_item'          => __( 'Nouvel envoi', 'plugin-tracking-personalise' ),
            'edit_item'             => __( 'Modifier l\'envoi', 'plugin-tracking-personalise' ),
            'new_item'              => __( 'Nouvel envoi', 'plugin-tracking-personalise' ),
            'view_item'             => __( 'Voir l\'envoi', 'plugin-tracking-personalise' ),
            'search_items'          => __( 'Rechercher des envois', 'plugin-tracking-personalise' ),
            'not_found'             => __( 'Aucun envoi trouvé', 'plugin-tracking-personalise' ),
            'not_found_in_trash'    => __( 'Aucun envoi dans la corbeille', 'plugin-tracking-personalise' ),
            'all_items'             => __( 'Tous les envois', 'plugin-tracking-personalise' ),
        ];

        $args = [
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'query_var'           => true,
            'rewrite'             => false,
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => 56,
            'menu_icon'           => 'dashicons-location',
            'supports'            => [ 'title' ],
            'show_in_rest'        => false,
        ];

        register_post_type( 'ptp_shipment', $args );
    }
}
