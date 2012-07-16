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
	
	const allowedMimeTypes = 'image/jpeg,image/gif,video/x-flv';
	const maxFileSize = 10485760;
	
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
		global $post;
		
		
		if ( current_user_can('manage_options') ) {
			
			$currentPageURL = get_permalink();
			
			echo (
				"<div class='galerialaudos_options_list'>
					<ul>
						<li><a href='#' >Cadastrar exame</a></li>
							<ul>
								<li><a href='$currentPageURL&new_exam=new_patient'>Novo paciente</a></li>
								<li><a href='$currentPageURL&new_exam=select_patient'>Selecionar paciente existente</a></li>
							</ul>
						<li><a href='$currentPageURL&edit_exam=change_details' >Editar ou excluir exames existentes</a></li>
						<li><a href='$currentPageURL&edit_exam=change_pws' >Alterar senhas</a></li>
					</ul>
				</div>"
			);
			
			if ( isset( $_POST['create_laudo'] ) ) {
							
				$dataHora = current_time('mysql');
				
				if ( !isset( $_POST['paciente_id'] ) ) {
					$nomePaciente = $_POST['nome_paciente'];
					$matrPaciente = $_POST['matr_paciente'];
					$wpdb->insert(
						'traj_pacientes',
						array(
							'nome' => $nomePaciente,
							'matricula' => $matrPaciente,
							'datahora' => $dataHora,
						)
					);
					$pacienteID = $wpdb->insert_id;
				} else {
					$pacienteID = $_POST['paciente_id'];
					$pacienteSelecionado = $wpdb->get_var("SELECT nome FROM traj_pacientes WHERE id = $pacienteID");
				} 
				
				$titulo = $_POST['titulo'];
				$obsPaciente = $_POST['obs_paciente'];
				$obsMedico = $_POST['obs_medico'];
				$senhas = array(
					'senhaPaciente' => wp_generate_password(10, FALSE),
					'senhaMedico' => wp_generate_password(10, FALSE),
				);
				
				$arquivos = GaleriaLaudos::processUploads( explode( ',', self::allowedMimeTypes ), self::maxFileSize );
				if ( is_array( $arquivos ) ) {
					$arquivos = implode( ',', GaleriaLaudos::processUploads( explode( ',', self::allowedMimeTypes ), self::maxFileSize ) );
				}
																													# @todo estudar sanitize_text_field();
																													# @todo estudar esc_textarea();
					
				$wpdb->insert( 
					'traj_exames', 
					array( 
						'paciente_id' => $pacienteID,
						'titulo' => $titulo,
						'obs_paciente' => $obsPaciente,
						'obs_medico' => $obsMedico,
						'senha_paciente' => $senhas['senhaPaciente'],
						'senha_medico' => $senhas['senhaMedico'],
						'datahora_criacao' => $dataHora,
						'arquivos' => $arquivos,
					), 
					array( 
						'%d',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
					) 
				);
				
				echo "<p class='msg_on_success' id='successful_exam' >Exame cadastrado com sucesso! Veja os detalhes abaixo:</p>";
				
			}
	
			$formTop =		"<div class='galerialaudos_forms' id='create_laudo_form'>
								<form method='POST' enctype='multipart/form-data' action=''>";
			$formBottom = 	"	<fieldset>
									<legend>Cadastro de exame</legend>
									<p>
										<label for='title'>Título:</label>
										<input type='text' name='titulo' id='laudo_title' value='$titulo' />
									</p>
									<p>
										<label for='obs_medico'>Anotações para o médico:</label>
										<textarea name='obs_medico' id='obs_medico'>$obsMedico</textarea>
									</p>
									<p>
										<label for='obs_paciente'>Anotações para a paciente:</label>
										<textarea name='obs_paciente' id='obs_paciente'>$obsPaciente</textarea>
									</p>
		    						<div class='file_upload' id='file1'><input name='file[]' type='file'/>1</div>
		    						<div id='file_tools'>
		        						<div id='add_file'>Adicionar</div>
		       	 						<div id='del_file'>Remover</div>
		    						</div>
								</fieldset>
								<input type='submit' name='create_laudo' value='Enviar' ";
			// disable submit button if form has already been sent
			if ( isset( $_POST[ 'create_laudo' ] ) ) 
				$formBottom .= "disabled='disabled' ";
			$formBottom .= "/>
								</form>
							</div>";
			if ( !empty( $senhas ) ) {
				
				$displayPasswords = "<div class='passwords' id='display_passwords'>
										<p>
											Senha do médico: ".$senhas['senhaMedico'].
										"</p>
										<p>
											Senha do paciente: ".$senhas['senhaPaciente'].
										"</p>
									</div>";
									
			}		
				
			switch ( $_GET['new_exam'] ) {
								
				case 'new_patient':
					echo (
							"$displayPasswords
							$formTop
								<fieldset>
									<legend>Cadastro de paciente</legend>
									<p>
										<label for='nome_paciente'>Nome:</label>
										<input type='text' name='nome_paciente' id='nome_paciente' value='$nomePaciente' />
									</p>
									<p>
										<label for='matr_paciente'>Matrícula:</label>
										<input type='text' name='matr_paciente' id='matr_paciente' value='$matrPaciente' />
									</p>
								</fieldset>
							$formBottom"
					);
					break;
				
				case 'select_patient':
					// constructing select dropdown menu...
					$pacientes = $wpdb->get_results( 'SELECT id, nome FROM traj_pacientes', OBJECT_K);
					 
					if ( !isset( $pacienteSelecionado ) ) {
						$dropdownList = GaleriaLaudos::getDropdownList( $pacientes );
					} else {
						$dropdownList = GaleriaLaudos::getDropdownList( $pacientes, $pacienteSelecionado );
					}
					
					// echo final form
					echo (
						"$displayPasswords
						$formTop
						$dropdownList
						$formBottom"
					);							
					break;
					
				default:
					
					break;
			}
			
			if ( isset( $_GET['edit_exam'] ) ) {
				
				$pacientes = $wpdb->get_results( 'SELECT id, nome FROM traj_pacientes', OBJECT_K);
				
				$form = "<form method='GET' enctype='multipart/form-data' action=''>";
				$form .= "	<input type='hidden' name='page_id' value='".$post->ID."' />";
				$form .= "	<input type='hidden' name='edit_exam' value='".$_GET['edit_exam']."' />";
				$form .= GaleriaLaudos::getDropdownList( $pacientes );
				$form .= "	<input type='submit' value='Enviar' />
						</form>";
			
				switch ( $_GET['edit_exam'] ) {
					
					case 'change_details':
						
						if ( !isset( $_GET['paciente_id'] ) ) {
							
							echo $form;
						
						} else {
							
							$pacienteID = preg_replace('/[^-0-9]/', '', $_GET['paciente_id'] );
							
							if ( array_key_exists( $pacienteID, $pacientes ) ) {
								
								$exams = $wpdb->get_results( "SELECT * FROM traj_exames WHERE paciente_ID = $pacienteID" );
								
								$table = "<table class='traj_table' id'traj_table_exams' >";
								$table .= "<tr><th>Exame</th><th>Criado em</th><th>Modificado em</th><th>Editar</th><th>Excluir</th></tr>";
								
								foreach ( $exams as $exam ) {
	
									$table .= "<tr><td>".$exam->titulo."</td><td>".$exam->datahora_criacao."</td><td>".$exam->datahora_modificacao."</td><td class='traj_table_editrow'>editar</td><td class='traj_table_delrow'>deletar</td></tr>";
									
								}
								
								$table .= "</table>";
								
								echo $table;
								
							}
							
						}
						
						break;
					
					case 'change_pws':
						
						if ( !isset( $_GET['paciente_id'] ) ) {
							
							echo $form;
							
						} else {
							
							$pacienteID = preg_replace('/[^-0-9]/', '', $_GET['paciente_id'] );
							
							if ( array_key_exists( $pacienteID, $pacientes ) ) {
								
								$exams = $wpdb->get_results( "SELECT id, titulo, senha_paciente, senha_medico FROM traj_exames WHERE paciente_id = $pacienteID", OBJECT_K );
							
								if ( !isset( $_POST['exam'] ) ) {
								
									$formChangePW = "<form method='POST' enctype='multipart/form-data' action=''>";
									
									foreach ( $exams as $exam ) {
										
										$formChangePW .= "<p>";
										$formChangePW .= "<input class='traj_checkbox' type='checkbox' name='exam[]' value='".$exam->id."' /> ".$exam->titulo;
										$formChangePW .= "</p>";
										
									}
									
									$formChangePW .= "<input type='submit' value='Enviar' />";
									$formChangePW .= "</form>";
									
									echo $formChangePW;

								} else {
								
									$examIDs = preg_replace('/[^-0-9]/', '', $_POST['exam'] );
									

									foreach ( $examIDs as $examID ) {
										
										$senhas = array(
											'senha_paciente' => wp_generate_password(10, FALSE),
											'senha_medico' => wp_generate_password(10, FALSE),
										);
										
										$wpdb->update('traj_exames', $senhas, array( id => $examID ) );
										
									}
									
									echo "<p class='msg_on_success' id='successfull_pw_change'>Novas senhas geradas com sucesso!</p>";	
									
									foreach ($exams as $exam) {
											
										if( in_array( $exam->id, $examIDs ) ) {
											echo (
												"<ul class='msg_on_success' id='successful_pw_change_list' >
													<li>Senha para o médico: " . $exam->senha_medico . "</li>
													<li>Senha para o paciente: " . $exam->senha_paciente . "</li>
												</ul>"
											);

										}
										
									}
										
								}
									
							}
							
						}
						
						break;
						
					default:
						
						break;
					
				}

			}
					
		}
			
	}
	
	/*
	 * getDropdownList
	 * 
	 * - required $queryResults string ( database query results to populate the dropdown list )
	 * - optional $selectedOption string ( the first option to show in the dropdown list. Default is 'Selecione...' )
	 * 
	 * - construct a dropdown list from a database query to be used in a form
	 * 
	 * - return string with dropdownlist markup's populated from database
	 */
	public static function getDropdownList( $queryResults, $selectedOption='Selecione...' ) {
					
					$dropdownList = (
						"<fieldset>
							<legend>Seleção de paciente</legend>
							<p>
								<label for='nome_paciente'>Pacientes cadastrados:</label>
								<select name='paciente_id' id='paciente_dropdown'>
									<option value='selecione'>"
					);
					 
					if ( $selectedOption == 'Selecione...' ) {
						$dropdownList .= $selectedOption;
					} else {
						$dropdownList .= $selectedOption." (selecionado)";
					}
					
					$dropdownList .= "</option>";
							
					foreach ( $queryResults as $option ) {
						$dropdownList .= "<option value='".$option->id."'>".$option->nome."</option>";
					}
					$dropdownList .= ( 
						"		</select>
							</p>
						</fieldset>" 
					);
					
					return $dropdownList;
		
	}

	/*
	 * loadScript
	 * 
	 * - register javascript/jquery scripts to be used by the plugin
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
	 * - optional $allowedMimetypes array of strings (mime types to allow)
	 * - optional $maxFilesize integer (limit size of each file)
	 * 
	 * - sanitize file name
	 * - resolve allowed file extensions
	 * - check file size (limit = 10mb)
	 * - check if file name already exists
	 * - copy file from temp dir to uploads folder
	 * 
	 * - return array of processed filenames
	 */
	public static function processUploads( $allowedMimetypes=NULL, $maxFilesize=NULL ) {
		
		$files = $_FILES['file'];
		$processedFiles = NULL;
		
		if ( is_array( $files ) ) {
			
			foreach ( $files['name'] as $key => $value ) {
				
				// work only with successfully uploaded files
				if ( $files['error'][$key] == 0 ) {
					
					// full actual file name (it'll be sanitized later)
					$filename = $files['name'][$key];
					// get the file mime and extension
					$filetype = wp_check_filetype( $filename );
					// check if file is allowed by mime type. If it's not allowed, echo the error and move to next file			
					if ( !in_array( $filetype['type'], $allowedMimetypes ) ) {
						echo "Falha no envio do arquivo '$filename'. Verifique se a extensao corresponde a uma das permitidas (jpg, gif ou flv).";
						continue;
					}
					// get the file size
					$filesize = $files['size'][$key];
					// check if file size exceeds $maxFilesize. If it does, echo the error end move to next file
					if ( $filesize > $maxFilesize ) {
						echo "Falha no envio do arquivo '$filename'. O tamanho ultrapassou o limite de 10mb.";
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
					if ( !copy( $filetmp, $filedest ) ) {
						echo "O arquivo '$filename' não pôde ser copiado para a pasta destino.";
					}
					
					$processedFiles[] = $filename;
					
				}
				
			}
			
		}
		
		return $processedFiles;
		
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