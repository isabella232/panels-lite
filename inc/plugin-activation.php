<?php

/**
 * Add the admin page if
 */
function siteorigin_panels_lite_plugin_activation_page(){
	if( !isset( $_GET[  'siteorigin-pa-install' ] ) ) return;

	add_theme_page(
		__('Install Page Builder Plugin', 'siteorigin'),
		__('Install Page Builder Plugin', 'siteorigin'),
		'install_plugins',
		'siteorigin_panels_lite_plugin_activation',
		'siteorigin_panels_lite_plugin_activation_render_page'
	);
}
add_action('admin_menu', 'siteorigin_panels_lite_plugin_activation_page');

function siteorigin_panels_lite_plugin_activation_render_page(){
	?>
	<div class="wrap">
		<?php siteorigin_panels_lite_plugin_activation_do_plugin_install() ?>
	</div>
	<?php
}

/**
 * Install a plugin
 */
function siteorigin_panels_lite_plugin_activation_do_plugin_install(){
	/** All plugin information will be stored in an array for processing */
	$plugin = array();

	/** Checks for actions from hover links to process the installation */
	if ( isset( $_GET[sanitize_key( 'plugin' )] ) && ( isset( $_GET[ 'siteorigin-pa-install' ] ) && 'install-plugin' == $_GET[sanitize_key( 'siteorigin-pa-install' )] ) && current_user_can('install_plugins') ) {
		check_admin_referer( 'siteorigin-pa-install' );

		$plugin['name']   = $_GET['plugin_name']; // Plugin name
		$plugin['slug']   = $_GET['plugin']; // Plugin slug

		if(!empty($_GET['plugin_source'])) {
			$plugin['source'] = $_GET['plugin_source'];
		}
		else {
			$plugin['source'] = false;
		}

		/** Pass all necessary information via URL if WP_Filesystem is needed */
		$url = wp_nonce_url(
			add_query_arg(
				array(
					'page'          => 'siteorigin_panels_lite_plugin_activation',
					'plugin'        => urlencode( $plugin['slug'] ),
					'plugin_name'   => urlencode( $plugin['name'] ),
					'plugin_source' => urlencode( $plugin['source'] ),
					'siteorigin-pa-install' => 'install-plugin',
				),
				admin_url( 'themes.php' )
			),
			'siteorigin-pa-install'
		);
		$method = ''; // Leave blank so WP_Filesystem can populate it as necessary
		$fields = array( sanitize_key( 'siteorigin-pa-install' ) ); // Extra fields to pass to WP_Filesystem

		if ( false === ( $creds = request_filesystem_credentials( $url, $method, false, false, $fields ) ) )
			return true;

		if ( ! WP_Filesystem( $creds ) ) {
			request_filesystem_credentials( $url, $method, true, false, $fields ); // Setup WP_Filesystem
			return true;
		}

		require_once ABSPATH . 'wp-admin/includes/plugin-install.php'; // Need for plugins_api
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php'; // Need for upgrade classes

		/** Prep variables for Plugin_Installer_Skin class */
		$title = sprintf( __('Installing %s', 'siteorigin'), $plugin['name'] );
		$url   = add_query_arg( array( 'action' => 'install-plugin', 'plugin' => $plugin['slug'] ), 'update.php' );
		if ( isset( $_GET['from'] ) )
			$url .= add_query_arg( 'from', urlencode( stripslashes( $_GET['from'] ) ), $url );

		$nonce = 'install-plugin_' . $plugin['slug'];

		// Find the source of the plugin
		$source = !empty( $plugin['source'] ) ? $plugin['source'] : 'http://downloads.wordpress.org/plugin/'.urlencode($plugin['slug']).'.zip';

		/** Create a new instance of Plugin_Upgrader */
		$upgrader = new Plugin_Upgrader( $skin = new Plugin_Installer_Skin( compact( 'type', 'title', 'url', 'nonce', 'plugin', 'api' ) ) );

		/** Perform the action and install the plugin from the $source urldecode() */
		$upgrader->install( $source );

		/** Flush plugins cache so we can make sure that the installed plugins list is always up to date */
		wp_cache_flush();
	}
}

/**
 * Get the admin install URL
 *
 * @return bool|string
 */
function siteorigin_panels_lite_plugin_activation_install_url(){

	return wp_nonce_url(
		add_query_arg(
			array(
				'page'          => 'siteorigin_panels_lite_plugin_activation',
				'plugin'        => 'siteorigin-panels',
				'plugin_name'   => __('Page Builder', 'siteorigin'),
				'plugin_source' => !empty($source) ? urlencode($source) : false,
				'siteorigin-pa-install' => 'install-plugin',
			),
			admin_url( 'themes.php' )
		),
		'siteorigin-pa-install'
	);
}

/**
 * If we're trying to install Page Builder, and it already exists, then activate it instead.
 *
 * @return bool
 */
function siteorigin_panels_lite_check_installing(){
	// This is to prevent the issue where this URL is called from outside the admin
	if( !is_admin() || !function_exists('get_plugins') ) return false;
	if( empty($_GET['page']) || $_GET['page'] != 'siteorigin_panels_lite_plugin_activation') return false;

	$plugin = 'siteorigin-panels';
	$plugins = get_plugins();
	$plugins = array_keys($plugins);

	$installed = false;
	foreach($plugins as $plugin_path){
		if(strpos($plugin_path, $plugin.'/') === 0) {
			$installed = true;
			break;
		}
	}

	if($installed && !is_plugin_active($plugin)){
		$redirect = wp_nonce_url( self_admin_url('plugins.php?action=activate&plugin='.$plugin_path), 'activate-plugin_'.$plugin_path);
		$redirect = str_replace('&amp;', '&', $redirect);
		wp_redirect( $redirect );
		exit();
	}
}
add_action('admin_init', 'siteorigin_panels_lite_check_installing');