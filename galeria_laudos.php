<?php
/*
Plugin Name: Galeria de Laudos Médicos
Plugin URI: http://www.trajettoria.com
Description: Plugin para controle e exibição de laudos médicos para médicos e pacientes
Author: Renato Zuma Bange
Version: 1.0
Author URI: http://www.trajettoria.com
*/

class GaleriaLaudos {
	
	/*
	 * 
	 */
	public static function install() {}	
	
	/*
	 * 
	 */
	public static function initialize() {}
	
	/*
	 * 
	 */
	public static function loadAdminInterface() {
		
		if ( current_user_can('manage_options') ) {
			echo (
				"<div class='options-box'>
					<ul>
						<li><a href='".add_query_arg( 'adminoption', 1 )."' >Inserir novo laudo</a></li>
						<li><a href='".add_query_arg( 'adminoption', 2 )."' >Editar ou excluir laudos existentes</a></li>
					</ul>
				</div>"
			);
			
			$adminoption = $_GET['adminoption']; // pesquisar fun��o mais apropriada!
			
			switch ( $adminoption ) {
				case 1:
					echo (
						"<form method='POST' enctype='multipart/form-data' action=''>
    						<div class='file_upload' id='file1'><input name='file[]' type='file'/>1</div>
    						<div id='file_tools'>
        						<div id='add_file'>Adicionar</div>
       	 						<div id='del_file'>Remover</div>
    						</div>
    						<input type='submit' name='upload' value='Upload'/>
						</form>"
					);
					
					GaleriaLaudos::processUploads();
					
					break;
				case 2:
					
					break;
				default:
					
					break;
			}
			
		}
		
	}
	
	/*
	 * processUploads
	 * 
	 * - clean file name
	 * - @todo resolve allowed file extensions
	 * - move file from temp dir to uploads folder
	 * - check if file name already exists
	 * - @todo store all data in db
	 * - @todo fixme >> final file is corrupted <<
	 */
	public static function processUploads() {
			
		$files = $_FILES['file'];
		
		if ( is_array( $files ) ) {
			
			foreach ( $files['name'] as $key => $value ) {
				
				// work only with successfully uploaded files
				if ( $files['error'][$key] == 0 ) {
					
					// temporary file name assigned by the server
					$filetmp = $files['tmp_name'][$key];
					// actual file name (it'll be sanitized later)
					$filename = $files['name'][$key];
					// get the file type (check if it's an allowed file type later)
					$filetype = wp_check_filetype( $filename );
					// sanitize file name
					$filetitle = sanitize_file_name( basename( $filename, '.'.$filetype['ext'] ) );
					
					$filename = $filetitle.'.'.$filetype['ext'];
					$upload_dir = plugin_dir_path( __FILE__ ).'uploads';
					
					$i = 0;
					while ( file_exists( $upload_dir.'/'.$filename ) ) {
						$filename = $filetitle.'_'.$i.'.'.$filetype['ext'];
					}
					
					$filedest = $upload_dir.'/'.$filename;
					
					move_uploaded_file( $filetmp, $filedest );
					
				}
				
			}
			
		}
		
	}
	
	/*
	 * 
	 */
	public static function loadUserInterface() {
		
		
		
	}
	

}

// Plugin activation hook
register_activation_hook( __FILE__, array( 'GaleriaLaudos', 'install' ) );
// Filter to initialize the plugin
add_filter( 'init', array( 'GaleriaLaudos', 'initialize' ) );
// Shortcode for user interface
add_shortcode( 'traj-galerialaudos-user', array( 'GaleriaLaudos', 'loadUserInterface' ) );
// Shortcode for admin interface
add_shortcode( 'traj-galerialaudos-admin', array( 'GaleriaLaudos', 'loadAdminInterface' ) );
// Action to load jQuery script

/*
 * 
 */
function loadScript() {
	// Register the script for the plugin
	wp_register_script( 'multiple-uploads', plugins_url( '/js/multiple-uploads.js', __FILE__ ), array( 'jquery' ) );
	// Enqueue the script
	wp_enqueue_script( 'multiple-uploads' );
	
}
add_action( 'wp_enqueue_scripts', 'loadScript' );

?>