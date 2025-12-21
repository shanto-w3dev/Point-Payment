<?php
// Point Payment Admin Class
if ( ! defined( 'ABSPATH' ) ) exit;


class Point_Payment_Admin {
        public function enqueue_admin_styles() {
            wp_enqueue_style(
                'point-payment-admin',
                plugins_url( '../assets/css/admin.css', __FILE__ ),
                array(),
                '1.0.0'
            );
        }
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_post_point_payment_add_points', array( $this, 'handle_add_points' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
    }

    public function add_admin_menu() {
        add_menu_page(
            __( 'Point Payment', 'point-payment' ),
            __( 'Point Payment', 'point-payment' ),
            'manage_options',
            'point-payment',
            array( $this, 'admin_page' ),
            'dashicons-tickets',
            56
        );
    }

    public function admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        // Handle messages
        if ( isset( $_GET['message'] ) ) {
            echo '<div class="updated"><p>' . esc_html( $_GET['message'] ) . '</p></div>';
        }

        // Get all users
        $users = get_users( array( 'fields' => array( 'ID', 'display_name' ) ) );
        // Get conversion rate from WooCommerce settings
        $conversion_rate = get_option( 'woocommerce_point_payment_settings' );
        $conversion_rate = isset( $conversion_rate['conversion_rate'] ) ? $conversion_rate['conversion_rate'] : 1;

        ?>
        <div class="wrap">
            <h1><?php _e( 'Point Payment Admin', 'point-payment' ); ?></h1>
            <h2><?php _e( 'Add Points to User', 'point-payment' ); ?></h2>
            <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
                <input type="hidden" name="action" value="point_payment_add_points">
                <?php wp_nonce_field( 'point_payment_add_points' ); ?>
                <select class="point-user-selector" name="user_id" required>
                    <option value=""><?php _e( 'Select User', 'point-payment' ); ?></option>
                    <?php foreach ( $users as $user ) : ?>
                        <option value="<?php echo esc_attr( $user->ID ); ?>"><?php echo esc_html( $user->display_name ); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="points" min="1" required placeholder="<?php _e( 'Points', 'point-payment' ); ?>">
                <button type="submit" class="button button-primary"><?php _e( 'Add Points', 'point-payment' ); ?></button>
            </form>

            <h2><?php _e( 'Users and Current Points', 'point-payment' ); ?></h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e( 'User', 'point-payment' ); ?></th>
                        <th><?php _e( 'Points', 'point-payment' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $users as $user ) : ?>
                        <tr>
                            <td><?php echo esc_html( $user->display_name ); ?></td>
                            <td><?php echo (int) get_user_meta( $user->ID, 'point_payment_points', true ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2><?php _e( 'Current Conversion Rate', 'point-payment' ); ?></h2>
            <p><?php echo esc_html( $conversion_rate ); ?> <?php _e( 'currency per point', 'point-payment' ); ?></p>
            <p><?php _e( 'To change the conversion rate, go to WooCommerce > Settings > Payments > Point Payment.', 'point-payment' ); ?></p>
        </div>
        <?php
    }

    public function handle_add_points() {
        if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'point_payment_add_points' ) ) {
            wp_die( __( 'Not allowed', 'point-payment' ) );
        }
        $user_id = intval( $_POST['user_id'] );
        $points  = intval( $_POST['points'] );
        if ( $user_id && $points > 0 ) {
            $current = (int) get_user_meta( $user_id, 'point_payment_points', true );
            update_user_meta( $user_id, 'point_payment_points', $current + $points );
            $msg = urlencode( __( 'Points added successfully!', 'point-payment' ) );
        } else {
            $msg = urlencode( __( 'Invalid input.', 'point-payment' ) );
        }
        wp_redirect( admin_url( 'admin.php?page=point-payment&message=' . $msg ) );
        exit;
    }
}

// Initialize admin logic
if ( is_admin() ) {
    new Point_Payment_Admin();
}
