<?php
/**
 * Plugin Name: WP Emoji Disabler
 * Plugin URI: https://github.com/Azanumer/wp-emoji-disabler
 * Description: Removes WordPress's emoji detection script, styles and TinyMCE emoji plugin — two fewer requests and no inline JS on every page. Optional: strip emoji characters from post content on save.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Azan Umer
 * Author URI: https://azanumer.cv
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: wp-emoji-disabler
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WED_VERSION', '1.0.0' );
define( 'WED_OPTION', 'wed_settings' );

/**
 * Saved settings, merged over defaults.
 *
 * @return array{frontend: bool, admin: bool, strip_content: bool}
 */
function wed_settings() {
    $defaults = array(
        'frontend'      => true,
        'admin'         => true,
        'strip_content' => false,
    );
    $saved = get_option( WED_OPTION, array() );
    return is_array( $saved ) ? array_merge( $defaults, $saved ) : $defaults;
}

/**
 * Remove every hook WordPress uses to print emoji scripts and styles.
 */
function wed_disable_emoji() {
    $settings = wed_settings();

    if ( $settings['frontend'] ) {
        remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
        remove_action( 'wp_print_styles', 'print_emoji_styles' );
        remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
        remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
        remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    }

    if ( $settings['admin'] ) {
        remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
        remove_action( 'admin_print_styles', 'print_emoji_styles' );
    }

    if ( $settings['frontend'] || $settings['admin'] ) {
        // No SVG URL, so nothing tries to fetch Twemoji assets.
        add_filter( 'emoji_svg_url', '__return_false' );
        // Drop the wpemoji plugin from TinyMCE.
        add_filter( 'tiny_mce_plugins', 'wed_filter_tinymce_plugins' );
    }
}
add_action( 'init', 'wed_disable_emoji' );

/**
 * Remove the wpemoji plugin from TinyMCE's plugin list.
 */
function wed_filter_tinymce_plugins( $plugins ) {
    if ( is_array( $plugins ) ) {
        return array_values( array_diff( $plugins, array( 'wpemoji' ) ) );
    }
    return $plugins;
}

/**
 * Strip emoji characters from a string. Covers the main emoji blocks
 * (emoticons, symbols, pictographs, dingbats, transport, flags via
 * regional indicators) plus variation selectors and ZWJ joiners.
 */
function wed_strip_emoji( $text ) {
    if ( ! is_string( $text ) || '' === $text ) {
        return $text;
    }
    $stripped = preg_replace(
        '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2190}-\x{21FF}\x{2300}-\x{23FF}\x{2B00}-\x{2BFF}\x{FE00}-\x{FE0F}\x{200D}\x{20D0}-\x{20FF}\x{1F1E6}-\x{1F1FF}]/u',
        '',
        $text
    );
    return null === $stripped ? $text : $stripped;
}

/**
 * Optionally strip emoji from post content (and title/excerpt) on save.
 */
function wed_strip_on_save( $data ) {
    if ( ! wed_settings()['strip_content'] ) {
        return $data;
    }
    // Runs before WP's own sanitisation, so nothing else re-adds them.
    if ( isset( $data['post_content'] ) ) {
        $data['post_content'] = wed_strip_emoji( $data['post_content'] );
    }
    if ( isset( $data['post_title'] ) ) {
        $data['post_title'] = wed_strip_emoji( $data['post_title'] );
    }
    if ( isset( $data['post_excerpt'] ) ) {
        $data['post_excerpt'] = wed_strip_emoji( $data['post_excerpt'] );
    }
    return $data;
}
add_filter( 'wp_insert_post_data', 'wed_strip_on_save', 9 );

/**
 * Settings page.
 */
function wed_admin_menu() {
    add_options_page(
        __( 'Emoji Disabler', 'wp-emoji-disabler' ),
        __( 'Emoji Disabler', 'wp-emoji-disabler' ),
        'manage_options',
        'wed-emoji-disabler',
        'wed_settings_page'
    );
}
add_action( 'admin_menu', 'wed_admin_menu' );

function wed_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_POST['wed_save'] ) ) {
        check_admin_referer( 'wed_save' );
        $settings = array(
            'frontend'      => isset( $_POST['wed_frontend'] ),
            'admin'         => isset( $_POST['wed_admin'] ),
            'strip_content' => isset( $_POST['wed_strip_content'] ),
        );
        update_option( WED_OPTION, $settings );
        echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'wp-emoji-disabler' ) . '</p></div>';
    }

    $settings = wed_settings();

    echo '<div class="wrap"><h1>' . esc_html__( 'Emoji Disabler', 'wp-emoji-disabler' ) . '</h1>';
    echo '<p>' . esc_html__( 'WordPress loads an emoji detection script and styles on every page, even if your content never uses emoji. This plugin removes them — check the page source before and after to see the difference.', 'wp-emoji-disabler' ) . '</p>';
    echo '<form method="post"><table class="form-table"><tbody>';
    printf(
        '<tr><th scope="row">%s</th><td><label><input type="checkbox" name="wed_frontend" value="1"%s> %s</label></td></tr>',
        esc_html__( 'Front-end', 'wp-emoji-disabler' ),
        checked( $settings['frontend'], true, false ),
        esc_html__( 'Remove the emoji script and styles from the public site.', 'wp-emoji-disabler' )
    );
    printf(
        '<tr><th scope="row">%s</th><td><label><input type="checkbox" name="wed_admin" value="1"%s> %s</label></td></tr>',
        esc_html__( 'Admin area', 'wp-emoji-disabler' ),
        checked( $settings['admin'], true, false ),
        esc_html__( 'Remove the emoji script and styles from wp-admin.', 'wp-emoji-disabler' )
    );
    printf(
        '<tr><th scope="row">%s</th><td><label><input type="checkbox" name="wed_strip_content" value="1"%s> %s</label><p class="description">%s</p></td></tr>',
        esc_html__( 'Strip emoji on save', 'wp-emoji-disabler' ),
        checked( $settings['strip_content'], true, false ),
        esc_html__( 'Remove emoji characters from post titles, content and excerpts when a post is saved.', 'wp-emoji-disabler' ),
        esc_html__( 'Off by default — only enable it if you want emoji-free content, e.g. for plain-text feeds or legacy databases.', 'wp-emoji-disabler' )
    );
    echo '</tbody></table>';
    wp_nonce_field( 'wed_save' );
    submit_button( __( 'Save settings', 'wp-emoji-disabler' ), 'primary', 'wed_save' );
    echo '</form></div>';
}
