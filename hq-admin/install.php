<?php
/**
 * HiveQueen Installer
 *
 * @package HiveQueen
 * @subpackage Administration
 */


// TODO: Goyo Debug 29/02/2016
ini_set('display_errors', 'On');
error_reporting(E_ALL);


// Sanity check.
if ( false ) {
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Error: PHP is not running</title>
</head>
<body class="hq-core-ui">
        <h1 id="logo"><a href="https://github.com/gcorral/hivequeen">HiveQueen</a></h1>
        <h2>Error: PHP is not running</h2>
        <p>HiveQueen requires that your web server is running PHP. Your server does not have PHP installed, or PHP is turned off.</p>
</body>
</html>
<?php
}


/**
 * We are installing HiveQueen.
 *
 * @since 1.5.1
 * @var bool
 */
define( 'HQ_INSTALLING', true );


/** Load HiveQueen Bootstrap */
require_once( dirname( dirname( __FILE__ ) ) . '/hq-load.php' );


/** Load HiveQueen Administration Upgrade API */
require_once( ABSPATH . 'hq-admin/includes/upgrade.php' );

/** Load HiveQueen Translation Install API */
// TODO: No translation 
// require_once( ABSPATH . 'hq-admin/includes/translation-install.php' );

/** Load hqdb */
require_once( ABSPATH . HQINC . '/hq-db.php' );

nocache_headers();

$step = isset( $_GET['step'] ) ? (int) $_GET['step'] : 0;


/**
 * Display install header.
 *
 * @since 2.5.0
 *
 * @param string $body_classes
 */
function display_header( $body_classes = '' ) {
        header( 'Content-Type: text/html; charset=utf-8' );
        if ( is_rtl() ) {
                $body_classes .= 'rtl';
        }
        if ( $body_classes ) {
                $body_classes = ' ' . $body_classes;
        }
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
        <meta name="viewport" content="width=device-width" />
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title><?php _e( 'HiveQueen &rsaquo; Installation' ); ?></title>
        <?php
                hq_admin_css( 'install', true );
                hq_admin_css( 'dashicons', true );
        ?>
</head>
<body class="hq-core-ui<?php echo $body_classes ?>">
<h1 id="logo"><a href="<?php echo esc_url( __( 'https://github.com/gcorral/hivequeen' ) ); ?>" tabindex="-1"><?php _e( 'HiveQueen' ); ?></a></h1>

<?php
} // end display_header()

/**
 * Display installer setup form.
 *
 * @since 2.8.0
 *
 * @param string|null $error
 */
function display_setup_form( $error = null ) {
        global $hqdb;

        $sql = $hqdb->prepare( "SHOW TABLES LIKE %s", $hqdb->esc_like( $hqdb->users ) );
        $user_table = ( $hqdb->get_var( $sql ) != null );

        // Ensure that Blogs appear in search engines by default.
        $blog_public = 1;
        if ( isset( $_POST['weblog_title'] ) ) {
                $blog_public = isset( $_POST['blog_public'] );
        }

        $weblog_title = isset( $_POST['weblog_title'] ) ? trim( hq_unslash( $_POST['weblog_title'] ) ) : '';
        $user_name = isset($_POST['user_name']) ? trim( hq_unslash( $_POST['user_name'] ) ) : '';
        $admin_email  = isset( $_POST['admin_email']  ) ? trim( hq_unslash( $_POST['admin_email'] ) ) : '';

        if ( ! is_null( $error ) ) {
?>
<p class="message"><?php echo $error; ?></p>
<?php } ?>
<form id="setup" method="post" action="install.php?step=2" novalidate="novalidate">
        <table class="form-table">
                <tr>
                        <th scope="row"><label for="weblog_title"><?php _e( 'Site Title' ); ?></label></th>
                        <td><input name="weblog_title" type="text" id="weblog_title" size="25" value="<?php echo esc_attr( $weblog_title ); ?>" /></td>
                </tr>
                <tr>
                        <th scope="row"><label for="user_login"><?php _e('Username'); ?></label></th>
                        <td>
                        <?php
                        if ( $user_table ) {
                                _e('User(s) already exists.');
                                echo '<input name="user_name" type="hidden" value="admin" />';
                        } else {
                                ?><input name="user_name" type="text" id="user_login" size="25" value="<?php echo esc_attr( sanitize_user( $user_name, true ) ); ?>" />
                                <p><?php _e( 'Usernames can have only alphanumeric characters, spaces, underscores, hyphens, periods, and the @ symbol.' ); ?></p>
                        <?php
                        } ?>
                        </td>
                </tr>
                <?php if ( ! $user_table ) : ?>
                <tr class="form-field form-required user-pass1-wrap">
                        <th scope="row">
                                <label for="pass1">
                                        <?php _e( 'Password' ); ?>
                                </label>
                        </th>
                        <td>
                                <div class="">
                                        <?php $initial_password = isset( $_POST['admin_password'] ) ? stripslashes( $_POST['admin_password'] ) : hq_generate_password( 18 ); ?>
                                        <input type="password" name="admin_password" id="pass1" class="regular-text" autocomplete="off" data-reveal="1" data-pw="<?php echo esc_attr( $initial_password ); ?>" aria-describedby="pass-strength-result" />
                                        <button type="button" class="button button-secondary hq-hide-pw hide-if-no-js" data-start-masked="<?php echo (int) isset( $_POST['admin_password'] ); ?>" data-toggle="0" aria-label="<?php esc_attr_e( 'Hide password' ); ?>">
                                                <span class="dashicons dashicons-hidden"></span>
                                                <span class="text"><?php _e( 'Hide' ); ?></span>
                                        </button>
                                        <div id="pass-strength-result" aria-live="polite"></div>
                                </div>
                                <p><span class="description important hide-if-no-js">
                                <strong><?php _e( 'Important:' ); ?></strong>
                                <?php /* translators: The non-breaking space prevents 1Password from thinking the text "log in" should trigger a password save prompt. */ ?>
                                <?php _e( 'You will need this password to log&nbsp;in. Please store it in a secure location.' ); ?></span></p>
                        </td>
                </tr>
                <tr class="form-field form-required user-pass2-wrap hide-if-js">
                        <th scope="row">
                                <label for="pass2"><?php _e( 'Repeat Password' ); ?>
                                        <span class="description"><?php _e( '(required)' ); ?></span>
                                </label>
                        </th>
                        <td>
                                <input name="admin_password2" type="password" id="pass2" autocomplete="off" />
                        </td>
                </tr>
                <tr class="pw-weak">
                        <th scope="row"><?php _e( 'Confirm Password' ); ?></th>
                        <td>
                                <label>
                                        <input type="checkbox" name="pw_weak" class="pw-checkbox" />
                                        <?php _e( 'Confirm use of weak password' ); ?>
                                </label>
                        </td>
                </tr>
                <?php endif; ?>
                <tr>
                        <th scope="row"><label for="admin_email"><?php _e( 'Your E-mail' ); ?></label></th>
                        <td><input name="admin_email" type="email" id="admin_email" size="25" value="<?php echo esc_attr( $admin_email ); ?>" />
                        <p><?php _e( 'Double-check your email address before continuing.' ); ?></p></td>
                </tr>
                <tr>
                        <th scope="row"><?php _e( 'Privacy' ); ?></th>
                        <td colspan="2"><label><input type="checkbox" name="blog_public" id="blog_public" value="1" <?php checked( $blog_public ); ?> /> <?php _e( 'Allow search engines to index this site' ); ?></label></td>
                </tr>
        </table>
        <p class="step"><?php submit_button( __( 'Install HiveQueen' ), 'large', 'Submit', false, array( 'id' => 'submit' ) ); ?></p>
        <input type="hidden" name="language" value="<?php echo isset( $_REQUEST['language'] ) ? esc_attr( $_REQUEST['language'] ) : ''; ?>" />
</form>
<?php
} // end display_setup_form()

// Let's check to make sure HQ isn't already installed.
if ( is_hq_installed() ) {
        display_header();
        die( '<h1>' . __( 'Already Installed' ) . '</h1><p>' . __( 'You appear to have already installed WordPress. To reinstall please clear your old database tables first.' ) . '</p><p class="step"><a href="../hq-login.php" class="button button-large">' . __( 'Log In' ) . '</a></p></body></html>' );
}

/**
 * @global string $hq_version
 * @global string $required_php_version
 * @global string $required_mysql_version
 * @global hqdb   $hqdb
 */
global $hq_version, $required_php_version, $required_mysql_version;

$php_version    = phpversion();
$mysql_version  = $hqdb->db_version();
$php_compat     = version_compare( $php_version, $required_php_version, '>=' );
$mysql_compat   = version_compare( $mysql_version, $required_mysql_version, '>=' ) || file_exists( HQ_CONTENT_DIR . '/db.php' );

if ( !$mysql_compat && !$php_compat )
        $compat = sprintf( __( 'You cannot install because <a href="https://github.com/gcorral/hivequeen">HiveQueen %1$s</a> requires PHP version %2$s or higher and MySQL version %3$s or higher. You are running PHP version %4$s and MySQL version %5$s.' ), $hq_version, $required_php_version, $required_mysql_version, $php_version, $mysql_version );
elseif ( !$php_compat )
        $compat = sprintf( __( 'You cannot install because <a href="https://github.com/gcorral/hivequeen">HiveQueen %1$s</a> requires PHP version %2$s or higher. You are running version %3$s.' ), $hq_version, $required_php_version, $php_version );
elseif ( !$mysql_compat )
        $compat = sprintf( __( 'You cannot install because <a href="https://github.com/gcorral/hivequeen">HiveQueen %1$s</a> requires MySQL version %2$s or higher. You are running version %3$s.' ), $hq_version, $required_mysql_version, $mysql_version );

if ( !$mysql_compat || !$php_compat ) {
        display_header();
        die( '<h1>' . __( 'Insufficient Requirements' ) . '</h1><p>' . $compat . '</p></body></html>' );
}

if ( ! is_string( $hqdb->base_prefix ) || '' === $hqdb->base_prefix ) {
        display_header();
        die( '<h1>' . __( 'Configuration Error' ) . '</h1><p>' . __( 'Your <code>hq-config.php</code> file has an empty database table prefix, which is not supported.' ) . '</p></body></html>' );
}

// Set error message if DO_NOT_UPGRADE_GLOBAL_TABLES isn't set as it will break install.
if ( defined( 'DO_NOT_UPGRADE_GLOBAL_TABLES' ) ) {
        display_header();
        die( '<h1>' . __( 'Configuration Error' ) . '</h1><p>' . __( 'The constant DO_NOT_UPGRADE_GLOBAL_TABLES cannot be defined when installing HiveQueen.' ) . '</p></body></html>' );
}

/**
 * @global string    $hq_local_package
 * @global HQ_Locale $hq_locale
 */
$language = '';
if ( ! empty( $_REQUEST['language'] ) ) {
        $language = preg_replace( '/[^a-zA-Z_]/', '', $_REQUEST['language'] );
} elseif ( isset( $GLOBALS['hq_local_package'] ) ) {
        $language = $GLOBALS['hq_local_package'];
}

switch($step) {
        case 0: // Step 0

                // TODO: no translation

                //if ( hq_can_install_language_pack() && empty( $language ) && ( $languages = hq_get_available_translations() ) ) {
                if ( false && empty( $language ) && ( $languages = hq_get_available_translations() ) ) {
                        display_header( 'language-chooser' );
                        echo '<form id="setup" method="post" action="?step=1">';
                        hq_install_language_form( $languages );
                        echo '</form>';
                        break;
                }

                // Deliberately fall through if we can't reach the translations API.

        case 1: // Step 1, direct link or from language chooser.
                if ( ! empty( $language ) ) {
                        //TODO: Goyo not download languaje
                        // $loaded_language = hq_download_language_pack( $language );
                        //if ( $loaded_language ) {
                        if ( true ) {
                                load_default_textdomain( $loaded_language );
                                $GLOBALS['hq_locale'] = new HQ_Locale();
                        }
                }

                display_header();
?>
<h1><?php _ex( 'Welcome', 'Howdy' ); ?></h1>
<p><?php _e( 'Welcome to the famous five-minute HiveQueen installation process! Just fill in the information below and you&#8217;ll be on your way to using it.' ); ?></p>

<h1><?php _e( 'Information needed' ); ?></h1>
<p><?php _e( 'Please provide the following information. Don&#8217;t worry, you can always change these settings later.' ); ?></p>

<?php
                display_setup_form();
                break;
        case 2:
                if ( ! empty( $language ) && load_default_textdomain( $language ) ) {
                        $loaded_language = $language;
                        $GLOBALS['hq_locale'] = new HQ_Locale();
                } else {
                        $loaded_language = 'en_US';
                }

                if ( ! empty( $hqdb->error ) )

                       hq_die( $hqdb->error->get_error_message() );

                display_header();
                // Fill in the data we gathered
                $weblog_title = isset( $_POST['weblog_title'] ) ? trim( hq_unslash( $_POST['weblog_title'] ) ) : '';
                $user_name = isset($_POST['user_name']) ? trim( hq_unslash( $_POST['user_name'] ) ) : '';
                $admin_password = isset($_POST['admin_password']) ? hq_unslash( $_POST['admin_password'] ) : '';
                $admin_password_check = isset($_POST['admin_password2']) ? hq_unslash( $_POST['admin_password2'] ) : '';
                $admin_email  = isset( $_POST['admin_email'] ) ?trim( hq_unslash( $_POST['admin_email'] ) ) : '';
                $public       = isset( $_POST['blog_public'] ) ? (int) $_POST['blog_public'] : 0;

                // Check e-mail address.
                $error = false;
                if ( empty( $user_name ) ) {
                        // TODO: poka-yoke
                        display_setup_form( __( 'Please provide a valid username.' ) );
                        $error = true;
                } elseif ( $user_name != sanitize_user( $user_name, true ) ) {
                        display_setup_form( __( 'The username you provided has invalid characters.' ) );
                        $error = true;
                } elseif ( $admin_password != $admin_password_check ) {
                        // TODO: poka-yoke
                        display_setup_form( __( 'Your passwords do not match. Please try again.' ) );
                        $error = true;
                } elseif ( empty( $admin_email ) ) {
                        // TODO: poka-yoke
                        display_setup_form( __( 'You must provide an email address.' ) );
                        $error = true;
                } elseif ( ! is_email( $admin_email ) ) {
                        // TODO: poka-yoke
                        display_setup_form( __( 'Sorry, that isn&#8217;t a valid email address. Email addresses look like <code>username@example.com</code>.' ) );
                        $error = true;
                }

                if ( $error === false ) {
                        $hqdb->show_errors();
                        $result = hq_install( $weblog_title, $user_name, $admin_email, $public, '', hq_slash( $admin_password ), $loaded_language );
?>

<h1><?php _e( 'Success!' ); ?></h1>

<p><?php _e( 'HiveQueen has been installed. Were you expecting more steps? Sorry to disappoint.' ); ?></p>

<table class="form-table install-success">
        <tr>
                <th><?php _e( 'Username' ); ?></th>
                <td><?php echo esc_html( sanitize_user( $user_name, true ) ); ?></td>
        </tr>
        <tr>
                <th><?php _e( 'Password' ); ?></th>
               <td><?php
                if ( ! empty( $result['password'] ) && empty( $admin_password_check ) ): ?>
                        <code><?php echo esc_html( $result['password'] ) ?></code><br />
                <?php endif ?>
                        <p><?php echo $result['password_message'] ?></p>
                </td>
        </tr>
</table>

<p class="step"><a href="../hq-login.php" class="button button-large"><?php _e( 'Log In' ); ?></a></p>

<?php
                }
                break;
}
if ( !hq_is_mobile() ) {
?>
<script type="text/javascript">var t = document.getElementById('weblog_title'); if (t){ t.focus(); }</script>
<?php } ?>
<?php hq_print_scripts( 'user-profile' ); ?>
<?php hq_print_scripts( 'language-chooser' ); ?>
<script type="text/javascript">
jQuery( function( $ ) {
        $( '.hide-if-no-js' ).removeClass( 'hide-if-no-js' );
} );
</script>
</body>
</html>

