<?php
/*
Plugin Name: Galeria de Laudos Medicos
Plugin URI: http://www.trajettoria.com
Description: Plugin para controle e exibicao de laudos medicos para medicos e pacientes
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
		
		global $wpdb;
		if ( current_user_can('manage_options') ) {
			echo (
				"<div class='galerialaudos_options_list'>
					<ul>
						<li><a href='".add_query_arg( 'admin_option', 1 )."' >Cadastrar exame</a></li>
						<li><a href='".add_query_arg( 'admin_option', 2 )."' >Editar ou excluir exames existentes</a></li>
						<li><a href='".add_query_arg( 'admin_option', 3 )."' >Alterar senhas</a></li>
					</ul>
				</div>"
			);
			
			$adminOption = $_GET['admin_option']; // @todo pesquisar função mais apropriada!
			
			switch ( $adminOption ) {
				case 1:
					// constructing select dropdown menu...
					$pacientes = $wpdb->get_results( 'SELECT id, nome FROM traj_paciente', ARRAY_A);
					$pacientesDropdown = (
						"<select name='nomePacientes' id='nome_pacientes'>
							<option value='selecione'>Selecione...</option>"
					);		
					foreach ( $pacientes as $key => $paciente ) {
						$pacientesDropdown .= '<option value="'.$paciente['id'].'">'.$paciente['nome'].'</option>';
					}
					$pacientesDropdown .= '</select>';
					
					// echo final form
					echo (
						"<div class='galerialaudos_forms' id='select_paciente'>
							<form methos='POST' action''>
							<p>
								<label for='nome_pacientes'>Selecione um paciente cadastrado:</label>"
					);
					echo $pacientesDropdown;
					echo (
						"	</p>
							<input type='submit' name='selecionar' value='ok' />
							</form>
						</div>"
					);

					echo (
						"<div class='galerialaudos_forms' id='create_paciente'>
							<form method='POST' enctype='multipart/form-data' action=''>
							<p>
								<label for='nome_novopaciente'>Nome:</label>
								<input type='text' id='nome_novopaciente' />
							</p>
							<p>
								<label for='matricula_novopaciente'>Matrícula:</label>
								<input type='text' id='matricula_novopaciente '/>
							</p>
							<input type='submit' name='criar' value='ok' />
						</form>"
					);
					
					
					/*
					echo (
						"<div class='galerialaudos_forms' id='create_laudo'>
							<form method='POST' enctype='multipart/form-data' action=''>
							<p>
								<label for='title'>Título:</label>
								<input type='text' name='titulo' id='laudo_title' />
							</p>
							<p>
								<label for='obs_medico'>Anotações para o médico:</label>
								<textarea name='obsmedico' id='obs_medico'></textarea>
							</p>
							<p>
								<label for='obs_paciente'>Anotações para a paciente:</label>
								<textarea name='obspaciente' id='obs_paciente'></textarea>
							</p>
    						<div class='file_upload' id='file1'><input name='file[]' type='file'/>1</div>
    						<div id='file_tools'>
        						<div id='add_file'>Adicionar</div>
       	 						<div id='del_file'>Remover</div>
    						</div>
    						<input type='submit' name='upload' value='Upload' />
							</form>
						</div>"
					);
					
					GaleriaLaudos::processUploads();
					*/
					
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
	 * - check file size (limit = 10mb)
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
		$maxFilesize = 10485760;
		
		if ( is_array( $files ) ) {
			
			foreach ( $files['name'] as $key => $value ) {
				
				// work only with successfully uploaded files
				if ( $files['error'][$key] == 0 ) {
					
					// full actual file name (it'll be sanitized later)
					$filename = $files['name'][$key];
					// get the file mime and extension
					$filetype = wp_check_filetype( $filename );
					// check if file is allowed by mime type. If it's not allowed, echo the error and move to next file			
					if ( !in_array( $filetype['type'], $allowedMimeTypes ) ) {
						echo "Falha no envio do arquivo '$filename'. Verifique se a extensao corresponde a uma das permitidas (jpg, gif ou flv).";
						continue;
					}
					// get the file size
					$filesize = $files['size'][$key];
					// check if file size exceeds $maxFilesize. If it does, echo the error end move to next file
					if ( $filesize > $maxFilesize ) {
						echo "Fala no envio do arquivo '$filename'. O tamanho ultrapassou o limite de 10mb.";
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