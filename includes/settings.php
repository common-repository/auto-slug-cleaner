<?php
/**
 * Adds settings part to plugin
 * Originally, wrote by Pippin Williamson
 *
 * @author          Pippin Williamson
 */
if ( ! defined( 'ABSPATH' ) ) exit; // No direct access allowed ;)

add_action( 'admin_menu', 'asc_add_settings_menu', 11 );

/**
 * Add admin page settings
 */
function asc_add_settings_menu() {
	global $asc_settings;
	
	add_submenu_page(
		'options-general.php',
		__( 'Slug Cleaner', 'auto-slug-cleaner' ),
		__( 'Slug Cleaner', 'auto-slug-cleaner' ),
		'manage_options',
		'auto-slug-cleaner',
		'asc_render_settings'
	);
}

/**
 * Gets saved settings from WP core
 *
 * @since           2.0
 * @return          array Settings
 */
function asc_get_settings() {
	$settings = get_option( 'asc_settings' );
	
	if ( empty( $settings ) ) {
		update_option( 'asc_settings', array(
			'words'	=>  '',
		) );
	}
	
	return apply_filters( 'asc_get_settings', $settings );
}

/**
 * Registers settings in WP core
 *
 * @since           2.0
 * @return          void
 */
function asc_register_settings() {
	if ( false == get_option( 'asc_settings' ) )
		add_option( 'asc_settings' );

	foreach( asc_get_registered_settings() as $tab => $settings ) {
		add_settings_section(
			'asc_settings_' . $tab,
			__return_null(),
			'__return_false',
			'asc_settings_' . $tab
		);

		foreach( $settings as $option ) {
			$name = isset( $option['name'] ) ? $option['name'] : '';

			add_settings_field(
				'asc_settings[' . $option['id'] . ']',
				$name,
				function_exists( 'asc_' . $option['type'] . '_callback' ) ? 'asc_' . $option['type'] . '_callback' : 'asc_missing_callback',
				'asc_settings_' . $tab,
				'asc_settings_' . $tab,
				array(
					'id'      => isset( $option['id'] ) ? $option['id'] : null,
					'desc'    => ! empty( $option['desc'] ) ? $option['desc'] : '',
					'name'    => isset( $option['name'] ) ? $option['name'] : null,
					'section' => $tab,
					'size'    => isset( $option['size'] ) ? $option['size'] : null,
					'options' => isset( $option['options'] ) ? $option['options'] : '',
					'std'     => isset( $option['std'] ) ? $option['std'] : ''
				)
			);

			register_setting( 'asc_settings', 'asc_settings', 'asc_settings_sanitize' );
		}
	}
}
add_action( 'admin_init', 'asc_register_settings' );

/**
 * Gets settings tabs
 *
 * @since               2.0
 * @return              array Tabs list
 */
function asc_get_tabs() {
    $tabs = array(
        'content'	=>  sprintf( __( '%s Content', 'auto-slug-cleaner' ), '<span class="dashicons dashicons-download"></span>' ),
    );
    return $tabs;
}

/**
 * Sanitizes and saves settings after submit
 *
 * @since               2.0
 * @param               array $input Settings input
 * @return              array New settings
 */
function asc_settings_sanitize( $input = array() ) {

	global $asc_settings;

	if( empty( $_POST['_wp_http_referer'] ) )
		return $input;

	parse_str( $_POST['_wp_http_referer'], $referrer );

	$settings  	= asc_get_registered_settings();
	$tab       	= isset( $referrer['tab'] ) ? $referrer['tab'] : 'content';

	$input 		= $input ? $input : array();
	$input 		= apply_filters( 'asc_settings_' . $tab . '_sanitize', $input );

	// Loop through each setting being saved and pass it through a sanitization filter
	foreach( $input as $key => $value ) {

		// Get the setting type (checkbox, select, etc)
		$type = isset( $settings[ $tab ][ $key ][ 'type' ] ) ? $settings[ $tab ][ $key ][ 'type' ] : false;

		if( $type ) {
			// Field type specific filter
			$input[ $key ] = apply_filters( 'asc_settings_sanitize_' . $type, $value, $key );
		}

		// General filter
		$input[ $key ] = apply_filters( 'asc_settings_sanitize', $value, $key );
	}


	// Loop through the whitelist and unset any that are empty for the tab being saved
	if( ! empty( $settings[ $tab ] ) ) {
		foreach( $settings[ $tab ] as $key => $value ) {

			// settings used to have numeric keys, now they have keys that match the option ID. This ensures both methods work
			if( is_numeric( $key ) ) {
				$key = $value['id'];
			}

			if( empty( $input[ $key ] ) ) {
				unset( $asc_settings[ $key ] );
			}

		}
	}

	// Merge our new settings with the existing
	$output = array_merge( $asc_settings, $input );

	add_settings_error( 'wpp-notices', '', __( 'Settings updated', 'auto-slug-cleaner' ), 'updated' );

	return $output;

}

/**
 * Get settings fields
 *
 * @since           2.0
 * @return          array Fields
 */
function asc_get_registered_settings() {
	$options = array(
		'enable'		=>	__( 'Enable', 'auto-slug-cleaner' ),
		'disable'		=>	__( 'Disable', 'auto-slug-cleaner' )
	);

    $settings = apply_filters( 'asc_registered_settings', array(
        'content'             =>  apply_filters( 'asc_content_settings', array(
			'words'      =>  array(
				'id'            =>  'words',
				'name'          =>  __( 'Words', 'auto-slug-cleaner' ),
				'type'          =>  'textarea',
				'desc'          =>  __( 'Enter your additional word in any line for remove in slug.', 'auto-slug-cleaner' )
			),
        ) ),
    ) );
    return $settings;
}


/* Form Callbacks Made by EDD Development Team */
function asc_header_callback( $args ) {
	echo '<hr/>';
}

function asc_checkbox_callback( $args ) {
	global $asc_settings;

	$checked = isset($asc_settings[$args['id']]) ? checked(1, $asc_settings[$args['id']], false) : '';
	$html = '<input type="checkbox" id="asc_settings[' . $args['id'] . ']" name="asc_settings[' . $args['id'] . ']" value="1" ' . $checked . '/>';
	$html .= '<label for="asc_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

function asc_multicheck_callback( $args ) {
    global $asc_settings;

    $html = '';
    foreach( $args['options'] as $key => $value ) {
        $option_name = $args['id'] . '-' . $key;
        asc_checkbox_callback( array(
            'id'        =>  $option_name,
            'desc'      =>  $value
        ) );
        echo '<br>';
    }

    echo $html;
}

function asc_radio_callback( $args ) {
	global $asc_settings;

	foreach ( $args['options'] as $key => $option ) :
		$checked = false;

		if ( isset( $asc_settings[ $args['id'] ] ) && $asc_settings[ $args['id'] ] == $key )
			$checked = true;
		elseif( isset( $args['std'] ) && $args['std'] == $key && ! isset( $asc_settings[ $args['id'] ] ) )
			$checked = false;

		echo '<input name="asc_settings[' . $args['id'] . ']"" id="asc_settings[' . $args['id'] . '][' . $key . ']" type="radio" value="' . $key . '" ' . checked(true, $checked, false) . '/>';
		echo '<label for="asc_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label>&nbsp;&nbsp;';
	endforeach;

	echo '<p class="description">' . $args['desc'] . '</p>';
}

function asc_text_callback( $args ) {
	global $asc_settings;

	if ( isset( $asc_settings[ $args['id'] ] ) )
		$value = $asc_settings[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="text" class="' . $size . '-text" id="asc_settings[' . $args['id'] . ']" name="asc_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<label for="asc_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

function asc_number_callback( $args ) {
	global $asc_settings;

	if ( isset( $asc_settings[ $args['id'] ] ) )
		$value = $asc_settings[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$max  = isset( $args['max'] ) ? $args['max'] : 999999;
	$min  = isset( $args['min'] ) ? $args['min'] : 0;
	$step = isset( $args['step'] ) ? $args['step'] : 1;

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="number" step="' . esc_attr( $step ) . '" max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" class="' . $size . '-text" id="asc_settings[' . $args['id'] . ']" name="asc_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<label for="asc_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

function asc_textarea_callback( $args ) {
	global $asc_settings;

	if ( isset( $asc_settings[ $args['id'] ] ) )
		$value = $asc_settings[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<textarea class="large-text" cols="50" rows="5" id="asc_settings[' . $args['id'] . ']" name="asc_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
	$html .= '<label for="asc_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

function asc_password_callback( $args ) {
	global $asc_settings;

	if ( isset( $asc_settings[ $args['id'] ] ) )
		$value = $asc_settings[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="password" class="' . $size . '-text" id="asc_settings[' . $args['id'] . ']" name="asc_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '"/>';
	$html .= '<label for="asc_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

function asc_missing_callback($args) {
	echo '&ndash;';
	return false;
}


function asc_select_callback($args) {
	global $asc_settings;

	if ( isset( $asc_settings[ $args['id'] ] ) )
		$value = $asc_settings[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$html = '<select id="asc_settings[' . $args['id'] . ']" name="asc_settings[' . $args['id'] . ']"/>';

	foreach ( $args['options'] as $option => $name ) :
		$selected = selected( $option, $value, false );
		$html .= '<option value="' . $option . '" ' . $selected . '>' . $name . '</option>';
	endforeach;

	$html .= '</select>';
	$html .= '<label for="asc_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

function asc_color_select_callback( $args ) {
	global $asc_settings;

	if ( isset( $asc_settings[ $args['id'] ] ) )
		$value = $asc_settings[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$html = '<select id="asc_settings[' . $args['id'] . ']" name="asc_settings[' . $args['id'] . ']"/>';

	foreach ( $args['options'] as $option => $color ) :
		$selected = selected( $option, $value, false );
		$html .= '<option value="' . $option . '" ' . $selected . '>' . $color['label'] . '</option>';
	endforeach;

	$html .= '</select>';
	$html .= '<label for="asc_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

function asc_rich_editor_callback( $args ) {
	global $asc_settings, $wp_version;

	if ( isset( $asc_settings[ $args['id'] ] ) )
		$value = $asc_settings[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	if ( $wp_version >= 3.3 && function_exists( 'wp_editor' ) ) {
		$html = wp_editor( stripslashes( $value ), 'asc_settings[' . $args['id'] . ']', array( 'textarea_name' => 'asc_settings[' . $args['id'] . ']' ) );
	} else {
		$html = '<textarea class="large-text" rows="10" id="asc_settings[' . $args['id'] . ']" name="asc_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
	}

	$html .= '<br/><label for="asc_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

function asc_upload_callback( $args ) {
	global $asc_settings;

	if ( isset( $asc_settings[ $args['id'] ] ) )
		$value = $asc_settings[$args['id']];
	else
		$value = isset($args['std']) ? $args['std'] : '';

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="text" class="' . $size . '-text asc_upload_field" id="asc_settings[' . $args['id'] . ']" name="asc_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<span>&nbsp;<input type="button" class="asc_settings_upload_button button-secondary" value="' . __( 'Upload File', 'auto-slug-cleaner' ) . '"/></span>';
	$html .= '<label for="asc_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

function asc_color_callback( $args ) {
	global $asc_settings;

	if ( isset( $asc_settings[ $args['id'] ] ) )
		$value = $asc_settings[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$default = isset( $args['std'] ) ? $args['std'] : '';

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="text" class="wpp-color-picker" id="asc_settings[' . $args['id'] . ']" name="asc_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '" data-default-color="' . esc_attr( $default ) . '" />';
	$html .= '<label for="asc_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

function asc_render_settings() {
	global $asc_settings;
	$active_tab = isset( $_GET[ 'tab' ] ) && array_key_exists( $_GET['tab'], asc_get_tabs() ) ? $_GET[ 'tab' ] : 'content';

	ob_start();
	?>
	<div class="wrap wpp-settings-wrap">
		<h2><?php _e('Settings','auto-slug-cleaner') ?></h2>
		<h2 class="nav-tab-wrapper">
			<?php
			foreach( asc_get_tabs() as $tab_id => $tab_name ) {

				$tab_url = add_query_arg( array(
					'settings-updated' => false,
					'tab' => $tab_id
				) );

				$active = $active_tab == $tab_id ? ' nav-tab-active' : '';

				echo '<a href="' . esc_url( $tab_url ) . '" title="' . esc_attr( $tab_name ) . '" class="nav-tab' . $active . '">';
					echo $tab_name;
				echo '</a>';
			}
			?>
		</h2>
		<?php echo settings_errors( 'wpp-notices' ); ?>
		<div id="tab_container">
			<form method="post" action="options.php">
				<table class="form-table">
				<?php
				settings_fields( 'asc_settings' );
				do_settings_fields( 'asc_settings_' . $active_tab, 'asc_settings_' . $active_tab );
				?>
				</table>
				<?php submit_button(); ?>
			</form>
		</div><!-- #tab_container-->
	</div><!-- .wrap -->
	<?php
	echo ob_get_clean();
}