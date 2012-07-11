<?php
/*
Plugin Name: Galeria de Laudos M�dicos
Plugin URI: http://www.trajettoria.com
Description: Plugin para controle e exibi��o de laudos m�dicos para m�dicos e pacientes
Author: Renato Zuma Bange
Version: 1.0
Author URI: http://www.trajettoria.com
*/

class GaleriaLaudos {
	
	public static function install() {}	
	
	public static function loadAdminInterface() {
		
		if ( current_user_can('manage_options') ) {
			echo (
				'<div class="options-box">
					<ul>
						<li>Inserir novo laudo</li>
						<li>Editar ou excluir laudos existentes</li>
					</ul>
				</div>'
			);
		}
		
	}
	
	public static function loadUserInterface() {
		
		
		
	}
	

}

// Shortcode for user interface
add_shortcode('traj-galerialaudos-user', array('GaleriaLaudos', 'loadUserInterface'));
// Shortcode for admin interface
add_shortcode('traj-galerialaudos-admin', array('GaleriaLaudos', 'loadAdminInterface'));

?>
