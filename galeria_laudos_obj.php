<?php

// plugin header here

class GaleriaLaudos {
	
	private $pluginPath;
	private $pluginUrl;
	
	public function __construct() {
		
		// Set Plugin Path
		$this->pluginPath = dirname(__FILE__);
		// Set Plugin URL
		$this->pluginUrl = WP_PLUGIN_URL . '/traj-galeria-laudos';		
		
		// Shortcode for user interface
		add_shortcode('traj-galerialaudos-usuario', array($this, 'user-shortcode'));
		// Shortcode for admin interface
		add_shortcode('traj-galerialaudos-admin', array($this, 'admin-shortcode'));
	
		// Add shortcode support for widgets
		add_filter('widget_text', 'do_shortcode');
		
	}
	

}

$galeriaLaudos = new GaleriaLaudos();

?>