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
			
			$adminoption = $_GET['adminoption']; // @todo pesquisar função mais apropriada!
			
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
 	* 
 	*/
	public static function loadScript() {
		// Register the script for the plugin
		wp_register_script( 'multiple-uploads', plugins_url( '/js/multiple-uploads.js', __FILE__ ), array( 'jquery' ) );
		// Enqueue the script
		wp_enqueue_script( 'multiple-uploads' );
	
	}
	
	/*
	 * processUploads
	 * 
	 * - sanitize file name
	 * - resolve allowed file extensions
	 * - move file from temp dir to uploads folder
	 * - check if file name already exists
	 * - @todo store all data in db
	 */
	public static function processUploads() {
			
		$files = $_FILES['file'];
		$allowedMimeTypes = array( 
			0 => 'image/jpeg',
			1 => 'image/gif',
			2 => 'video/x-flv',
		);
		
		if ( is_array( $files ) ) {
			
			foreach ( $files['name'] as $key => $value ) {
				
				// work only with successfully uploaded files
				if ( $files['error'][$key] == 0 ) {
					
					// full actual file name (it'll be sanitized later)
					$filename = $files['name'][$key];
					// get the file mime and extension
					$filetype = wp_check_filetype( $filename );
					// check if file is allowed by mime type			
					if ( !in_array( $filetype['type'], $allowedMimeTypes ) ) {
						echo "Falha no envio do arquivo '$filename'. Verifique se a extensão corresponde a uma das permitidas (jpg, gif ou flv).";
						continue;
					}		
					// temporary file name assigned by the server
					$filetmp = $files['tmp_name'][$key];
					// sanitize file name and drop the extension, we need just the clean title
					$filetitle = sanitize_file_name( basename( $filename, '.'.$filetype['ext'] ) );
					// construct fresh new sanitized file name
					$filename = $filetitle.'.'.$filetype['ext'];
					// all processed files will be found here
					$upload_dir = plugin_dir_path( __FILE__ ).'uploads';
					// resolve existing file names by adding '_$i', where $i is the number of times it has just repeated 
					$i = 1;
					while ( file_exists( $upload_dir.'/'.$filename ) ) {
						$filename = $filetitle.'_'.$i.'.'.$filetype['ext'];
						$i++;
					}
					// final file path
					$filedest = $upload_dir.'/'.$filename;
					// move the file from temp dir to final file path
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
add_action( 'wp_enqueue_scripts', array( 'GaleriaLaudos', 'loadScript' ) );

?>