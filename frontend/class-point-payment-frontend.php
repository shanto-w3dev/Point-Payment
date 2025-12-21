<?php
// Point Payment Frontend Class
if ( ! defined( 'ABSPATH' ) ) exit;


class Point_Payment_Frontend {
    public static function add_endpoint() {
        add_rewrite_endpoint( 'point-balance', EP_ROOT | EP_PAGES );
    }
    public function __construct() {
        add_action( 'init', array( __CLASS__, 'add_endpoint' ) );
        add_action( 'woocommerce_account_point-balance_endpoint', array( $this, 'point_balance_content' ) );
        add_filter( 'woocommerce_account_menu_items', array( $this, 'add_account_menu_item' ), 5, 1 );
    }

    public function add_account_menu_item( $items ) {
        // Insert 'My Points' above 'edit-address' if it exists, otherwise at the top
        $new_items = array();
        foreach ( $items as $key => $label ) {
            if ( $key === 'edit-address' ) {
                $new_items['point-balance'] = __( 'My Points', 'point-payment' );
            }
            $new_items[$key] = $label;
        }
        // If 'edit-address' not found, add at the top
        if ( ! isset( $new_items['point-balance'] ) ) {
            $new_items = array_merge( array( 'point-balance' => __( 'My Points', 'point-payment' ) ), $items );
        }
        return $new_items;
    }

    public function point_balance_content() {
        $user_id = get_current_user_id();
        $points = (int) get_user_meta( $user_id, 'point_payment_points', true );
        $history = get_user_meta( $user_id, 'point_payment_history', true );
        if ( ! is_array( $history ) ) $history = array();
        echo '<h3>' . __( 'Your Current Points:', 'point-payment' ) . ' ' . esc_html( $points ) . '</h3>';
        echo '<h4>' . __( 'Point History', 'point-payment' ) . '</h4>';
        if ( empty( $history ) ) {
            echo '<p>' . __( 'No point transactions yet.', 'point-payment' ) . '</p>';
        } else {
            echo '<table><tr><th>' . __( 'Type', 'point-payment' ) . '</th><th>' . __( 'Points', 'point-payment' ) . '</th><th>' . __( 'Order', 'point-payment' ) . '</th><th>' . __( 'Date', 'point-payment' ) . '</th></tr>';
            foreach ( array_reverse( $history ) as $row ) {
                echo '<tr>';
                echo '<td>' . esc_html( ucfirst( $row['type'] ) ) . '</td>';
                echo '<td>' . esc_html( $row['points'] ) . '</td>';
                echo '<td>' . ( ! empty( $row['order_id'] ) ? esc_html( $row['order_id'] ) : '-' ) . '</td>';
                echo '<td>' . esc_html( $row['date'] ) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    }
}

// Initialize frontend logic
if ( ! is_admin() ) {
    new Point_Payment_Frontend();
}

// Flush rewrite rules on plugin activation
register_activation_hook( dirname( __DIR__, 2 ) . '/point-payment.php', function() {
    Point_Payment_Frontend::add_endpoint();
    flush_rewrite_rules();
});
