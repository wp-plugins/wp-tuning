<?php
/*
	Plugin Name: WP Tuning
	Description: Quick and usefull wordpress tunes!
	Author URI: http://www.ksenzov.ru
	Author: gl_SPICE
	Version: 1
*/

defined( 'ABSPATH' ) or die( 'Plugin file cannot be accessed directly.' );

if ( ! class_exists( 'WP_Tuning' ) ) {
	class WP_Tuning {
		protected $tag = 'wp_tuning';
		protected $name = 'WP Tuning';
		protected $version = '1';
		protected $options = array();

		protected $settings = array(
			'used_res' => array(
				'title'		=> 'Show Resources Usage',
				'desc'		=> 'Display memory and queries usage in footer',
				'fields'	=> array(
					'admin'		=> array( 'title' => 'Admin Panel', 'type' => 'checkbox' ),
					'theme'		=> array( 'title' => 'Theme Backend', 'type' => 'checkbox' ),
					'format'	=> array( 'title' => 'Output format', 'desc' => '%ram% — memory usage<br>%sql% — sql queries number<br>%time% — generation time', 'default' => '%ram% Mb | %sql% SQL | %time% s')
				)
			),
			'header_cleanup' => array(
				'title'		=> 'Header Cleanup',
				'desc'		=> 'Remove unnecessary extra links from the page header',
				'fields'	=> array(
					'rsd_link'					=> array( 'title' => 'Really Simple Discovery', 'type' => 'checkbox' ),
					'wp_generator'				=> array( 'title' => 'Wordpress generator tag', 'type' => 'checkbox' ),
					'feed_links'				=> array( 'title' => 'Standard feed links', 'type' => 'checkbox' ),
					'feed_links_extra'			=> array( 'title' => 'Extra feed links', 'type' => 'checkbox' ),
					'index_rel_link'			=> array( 'title' => 'Post Rel Links - Index', 'type' => 'checkbox' ),
					'start_post_rel_link'		=> array( 'title' => 'Post Rel Links - Start', 'type' => 'checkbox' ),
					'wlwmanifest_link'			=> array( 'title' => 'Wlwmanifest', 'type' => 'checkbox' ),
					'parent_post_rel_link'		=> array( 'title' => 'Parent post link', 'type' => 'checkbox' ),
					'adjacent_posts_rel_link'	=> array( 'title' => 'Prev/Next post links', 'type' => 'checkbox' ),
					'wp_shortlink_wp_head'		=> array( 'title' => 'Shortlink for the page', 'type' => 'checkbox' )
				)
			),
			'feeds' => array(
				'title'		=> 'Feeds',
				'fields'	=> array(
					'disable'	=> array( 'title' => 'Disable all feeds', 'type' => 'checkbox' ),
				)
			),
			'locations' => array(
				'title'		=> 'Locations',
				'fields'	=> array(
					'theme_uri'	=> array( 'title' => 'Theme URI', 'desc' => 'http://theme.example.com' )
				)
			),
			'insert_analytics' => array(
				'title'		=> 'Insert Analytics',
				'desc'		=> 'Include Google Analytics and Yandex.Metrics codes',
				'fields'	=> array(
					'ga_id'		=> array( 'title' => 'Google Analytics ID', 'desc' => 'Something like UA-12069412-2' ),
					'ym_id'		=> array( 'title' => 'Yandex Metric ID', 'desc' => 'Something like 14793448' ),
					'to_footer'	=> array( 'title' => 'Move to footer', 'type' => 'checkbox' ),
				)
			)
		);

		/* Initiate the plugin */

		public function __construct() {
		
			if ( $options = get_option( $this->tag . '_settings' ) ) {
				$this->options = $options;
			}
			
			if ( is_admin() ) {
				add_action( 'admin_menu', array( &$this, 'add_plugin_page' ) );
				add_action( 'admin_init', array( &$this, 'settings' ) );
			}
			
			$this->do_actions();
			
		}
		
		/* Do all actions */
		
		public function do_actions() {
		
			if ( $this->options['used_res_admin'] && is_admin() )
				add_action( 'admin_footer_text', array( &$this, 'used_res' ) );

			if ( $this->options['used_res_theme'] )
				add_action( 'wp_footer', array( &$this, 'used_res' ) );
	
			add_action( 'init', array( &$this, 'header_cleanup' ) );
			
			if ( $this->options['feeds_disable'] ) {
				add_action( 'do_feed', array( &$this, 'disable_feed' ), 1 );
				add_action( 'do_feed_rdf', array( &$this, 'disable_feed' ), 1 );
				add_action( 'do_feed_rss', array( &$this, 'disable_feed' ), 1 );
				add_action( 'do_feed_rss2', array( &$this, 'disable_feed' ), 1 );
				add_action( 'do_feed_atom', array( &$this, 'disable_feed' ), 1 );
				add_action( 'do_feed_rss2_comments', array( &$this, 'disable_feed' ), 1 );
				add_action( 'do_feed_atom_comments', array( &$this, 'disable_feed' ), 1 );
			}
			
			if ( $this->options['locations_theme_uri'] )
				add_filter( 'theme_root_uri', array( &$this, 'theme_uri' ) );
	
			if ( $this->options['insert_analytics_to_footer'] ) {
				add_action( 'wp_footer', array( &$this, 'add_ga_code' ) );
				add_action( 'wp_footer', array( &$this, 'add_ym_code' ) );
			} else {
				add_action( 'wp_head', array( &$this, 'add_ga_code' ) );
				add_action( 'wp_head', array( &$this, 'add_ym_code' ) );
			}
		}
		
		/* Print resourses usage */
		
		public function used_res() {
		
			if ( current_user_can('manage_options') && $this->options['used_res_format'] ) {
				echo str_replace(
					array('%ram%', '%sql%', '%time%'),
					array(round(memory_get_usage() / 1024 / 1024, 2), get_num_queries(), timer_stop(0, 2)),
					$this->options['used_res_format']
				);
			}
			
		}
		
		/* Remove some data from header */

		public function header_cleanup() {
			
			$header_cleanup_actions = array(
				'rsd_link', 'wp_generator', 'feed_links', 'feed_links_extra',
				'index_rel_link', 'wlwmanifest_link', 'parent_post_rel_link',
				'start_post_rel_link', 'adjacent_posts_rel_link', 'wp_shortlink_wp_head'
			);
			
			foreach ( $header_cleanup_actions as $key ) {
				if ( $this->options['header_cleanup_' . $key] )
					remove_action( 'wp_head', $key );
			}
			
		}

		/* Google Analytics */
		
		public function add_ga_code() {
			$ga_id = $this->options['insert_analytics_ga_id'];
			if ($ga_id) echo "<script>(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','//www.google-analytics.com/analytics.js','ga');ga('create','{$ga_id}','auto');ga('send','pageview');</script>";
		}

		/* Yandex Metrika */
		
		public function add_ym_code() {
			$ym_id = $this->options['insert_analytics_ym_id'];
			if ($ym_id) echo "<script>(function(d,w,c){(w[c]=w[c]||[]).push(function(){try{w.yaCounter{$ym_id}=new Ya.Metrika({id:{$ym_id}});}catch(e){}});var n=d.getElementsByTagName('script')[0],s=d.createElement('script'),f=function(){n.parentNode.insertBefore(s,n);};s.type='text/javascript';s.async=true;s.src=(d.location.protocol=='https:'?'https:':'http:')+'//mc.yandex.ru/metrika/watch.js';if(w.opera=='[object Opera]'){d.addEventListener('DOMContentLoaded',f,false);}else{f();}})(document,window,'yandex_metrika_callbacks');</script>";
		}
		
		/* Disable feeds */
		
		function disable_feed() {
			$exit_msg   = __('Page not found, please visit <a href="'. home_url() . '">homepage</a>', 'wp-tuning' );
			$exit_title = __('Page not found', 'wp-tuning' );
			wp_die( $exit_msg, $exit_title, array( 'response' => 404 ) );
		}
		
		/* Theme URI */
		
		function theme_uri() {
			return $this->options['locations_theme_uri'];
			//return 'http://theme.panaseya.ru';
		}
		
		/* Add admin options page */
		
		public function add_plugin_page() {
			add_options_page(
				__('WP Tuning Options', 'wp-tuning'),
				__('WP Tuning', 'wp-tuning'), 
				'manage_options', 'wp-tuning-options',
				array($this, 'create_admin_page')
			);
		}
		
		/* Admin options page markup */
		
		public function create_admin_page() {
			echo '<div class="wrap">';
			echo '<h2>' . __('WP Tuning Options', 'wp-tuning') . '</h2>';
			echo '<form method="post" action="options.php">';
				settings_fields( 'wp-tuning-options' );
				do_settings_sections( 'wp-tuning-options' );
				submit_button();
			echo '</form>';
			echo '</div>';
		}

		/* Add the setting fields to the Plugin settings page */
		
		public function settings() {
			foreach ( $this->settings AS $section_id => $section ) {
				
				add_settings_section(
					$section_id . '_section',
					__( $section['title'], 'wp-tuning' ),
					function () { if ($section['desc']) echo $section['desc']; },
					'wp-tuning-options'
				);
				
				foreach ( $section['fields'] AS $field_id => $field ) {
					$field['id'] = $field_id;
					$field['section'] = $section_id;
					
					add_settings_field(
						$section_id . '_' . $field_id,
						__( $field['title'], 'wp-tuning' ),
						array( &$this, 'settings_field' ),
						'wp-tuning-options',
						$section_id . '_section',
						$field
					);
				}
			}
				
			register_setting(
				'wp-tuning-options',
				$this->tag . '_settings',
				array( &$this, 'settings_validate' )
			);
		}

		/* Append a settings field to the fields section */
		
		public function settings_field( array $options = array() ) {
		
			$atts = array(
				'id'	=> $this->tag . '_' . $options['section'] . '_' . $options['id'],
				'name'	=> $this->tag . '_settings' . '[' . $options['section'] . '_' . $options['id'] . ']',
				'type'	=> ( isset( $options['type'] ) ? $options['type'] : 'text' )
			);
			
			if ( isset( $this->options[$options['section'].'_'.$options['id']] ) ) {
				$atts['value'] = $this->options[$options['section'].'_'.$options['id']];
			}
			
			switch ($atts['type']) {
			
				case 'text':
					$atts['class'] = 'regular-text';
				break;
					
				case 'checkbox':
					if ( $atts['value'] ) 
						$atts['checked'] = 'checked';
					$atts['value'] = true;
				break;
					
			}
			
			array_walk( $atts, function( &$item, $key ) {
				$item = esc_attr( $key ) . '="' . esc_attr( $item ) . '"';
			} );
			
			echo '<label>';
			echo '<input ' . join( ' ', $atts ) . '>';
			if ( array_key_exists( 'hint', $options ) )
				esc_html_e( ' ' . $options['hint'] );
			echo '</label>';
			if ( array_key_exists( 'desc', $options ) )
				_e( '<p class="description">' . $options['desc'] . '</p>', 'wp-tuning' );
		}

		/* Validate the settings saved */
		
		public function settings_validate( $input ) {
			$errors = array();
			
			foreach ( $input AS $key => $value ) {
				if ( $value == '' ) {
					unset( $input[$key] );
					continue;
				}
				
				$validator = false;
				
				if ( isset( $this->settings[$key]['validator'] ) ) {
					$validator = $this->settings[$key]['validator'];
				}
				
				switch ( $validator ) {
					case 'numeric':
						if ( is_numeric( $value ) ) {
							$input[$key] = intval( $value );
						} else {
							$errors[] = $key . ' must be a numeric value.';
							unset( $input[$key] );
						}
						break;
					default:
						 $input[$key] = strip_tags( $value );
						break;
				}
			}
			
			if ( count( $errors ) > 0 ) {
				add_settings_error(
					$this->tag,
					$this->tag,
					implode( '<br />', $errors ),
					'error'
				);
			}
			
			return $input;
		}
	}
	new WP_Tuning;
}