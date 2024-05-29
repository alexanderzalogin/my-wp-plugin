<?php
/**
 * Plugin Name: My WP Plugin
 * Description: My WP Plugin
 * Author: Alex Z.
 * Author URI: https://github.com/alexanderzalogin
 * Version: 1.0.0
 * Text Domain: my-wp-plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class myWpPlugin
{
    public function __construct()
    {
        add_action('init', array($this, 'createCustomPostType'));
        add_action('wp_enqueue_scripts', array($this, 'loadAssets'));
        add_shortcode('my-wp-plugin', array($this, 'loadShortcode'));
        add_action('wp_footer', array($this, 'loadScripts'));
        add_action('rest_api_init', array($this, 'registerRestApi'));
    }

    public function createCustomPostType()
    {
        $args = array(
            'public' => true,
            'has_archive' => true,
            'supports' => array('title'),
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'capability' => 'manage_options',
            'labels' => array(
                'name' => 'My Plugin',
                'singular_name' => 'My plugin entry'
            ),
            'menu_icon' => 'dashicons-media-text',
        );

        register_post_type('my_wp_plugin', $args);
    }

    public function loadAssets()
    {
        wp_enqueue_style(
            'my-wp-plugin',
            plugin_dir_url(__FILE__) . '/css/my-wp-plugin.css',
            array(),
            1,
            'all'
        );

        wp_enqueue_style('bootstrap',
            plugin_dir_url(__FILE__) . '/css/bootstrap.min.css',
            array(),
            '5.1.3',
            'all'
        );


        wp_enqueue_script(
            'my-wp-plugin',
            plugin_dir_url(__FILE__) . '/js/my-wp-plugin.js',
            array('jquery'),
            1,
            true
        );
    }

    public function loadShortcode(): void
    {
        ?>
        <div class="my-wp-plugin col-lg-4 col-md-4 col-sm-4 container justify-content-center">
            <h1 class="row justify-content-center">Send us your feedback</h1>
            <p class="row justify-content-center">Please fill the below form</p>
            <form id="my-wp-plugin_form">
                <div class="form-group mb-2">
                    <input type="text" name="name" class="form-control" placeholder="Name">
                </div>
                <div class="form-group mb-2">
                    <input type="email" name="email" class="form-control" placeholder="Email">
                </div>
                <div class="form-group mb-2">
                    <input type="tel" name="phone" class="form-control" placeholder="Phone">
                </div>
                <div class="form-group mb-2">
                    <textarea name="message" class="form-control" placeholder="Your message"></textarea>
                </div>
                <button class="btn btn-success btn-block">Send message</button>
            </form>
        </div>
    <?php }

    public function loadScripts(): void
    {
        ?>
        <script>
            let $ = jQuery.noConflict();
            jQuery(document).ready(function ($) {


                $('#my-wp-plugin_form').submit(function (event) {
                    event.preventDefault();
                    // security token to protect URLs and forms from malicious attacks
                    let nonce = '<?php echo wp_create_nonce('wp_rest');?>';
                    let form = $(this).serialize();

                    $.ajax({
                        method: 'post',
                        url: '<?php echo get_rest_url(null, 'my-wp-plugin/v1/send-email');?>',
                        headers: {'X-WP-Nonce': nonce},
                        data: form
                    })
                })
            });
        </script>
    <?php }

    public function registerRestApi(): void
    {
        register_rest_route('my-wp-plugin/v1', 'send-email', array(
            'methods' => 'POST',
            'callback' => array($this, 'handleMyWpPluginForm')
        ));
    }

    public function handleMyWpPluginForm($data)
    {
        $headers = $data->get_headers();
        $params = $data->get_params();
        $nonce = $headers['x_wp_nonce'][0];

        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_REST_Response('Message not sent', 422);
        }

        $post_id = wp_insert_post([
            'post_type' => 'my_wp_plugin',
            'post_title' => 'New message',
            'post_status' => 'publish'
        ]);

        if ($post_id) {
            return new WP_REST_Response('Thanks for the feedback!', 200);
        }
    }
}

new myWpPlugin;