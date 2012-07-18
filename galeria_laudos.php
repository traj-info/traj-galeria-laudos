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
	const nomeInputError = 'O nome deve conter apenas letras.';
	const matriculaInputError = 'A matricula deve conter apenas numeros.';
	const tituloInputError = 'O titulo deve conter apenas letras e/ou numeros.';
	
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
				"<div class='traj_menu'>
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
			
			// checking if form has already been sent
			if ( isset( $_POST['create_laudo'] ) ) {
							
				$dataHora = current_time('mysql');
				
				if ( !isset( $_POST['paciente_id'] ) ) {
					
					$nomePaciente = $_POST['nome_paciente'];
					if ( preg_match( '/[^a-z\s]/i', $nomePaciente ) == TRUE ) {
						$inputErrors['nome'] = self::nomeInputError;
					}
					$matrPaciente = $_POST['matr_paciente'];
					if ( preg_match( '/[^0-9]/', $matrPaciente ) == TRUE ) {
						$inputErrors['matricula'] = self::matriculaInputError;
					}
					$patientArr = array(
						'nome' => $nomePaciente,
						'matricula' => $matrPaciente,
						'datahora' => $dataHora,
					);
					
				}
				
				$titulo = $_POST['titulo'];
				if ( preg_match( '/[^0-9a-z\s]/i', $titulo ) == TRUE ) {
					$inputErrors['titulo'] = self::tituloInputError;
				}
				
				$obsPaciente = sanitize_text_field( $_POST['obs_paciente'] );
				$obsMedico = sanitize_text_field( $_POST['obs_medico'] );
				$senhas = array(
					'senhaPaciente' => wp_generate_password(8, FALSE),
					'senhaMedico' => wp_generate_password(8, FALSE),
				);
				
				$arquivos = GaleriaLaudos::processUploads( explode( ',', self::allowedMimeTypes ), self::maxFileSize );
				if ( is_array( $arquivos ) ) {
					$arquivos = implode( ',', GaleriaLaudos::processUploads( explode( ',', self::allowedMimeTypes ), self::maxFileSize ) );
				}
																													# @todo estudar sanitize_text_field();
																													# @todo estudar esc_textarea();
				
				if ( !empty( $inputErrors ) ) {
					echo "<p class='msg_on_failure' id='unsuccessful_exam_create' >Por favor, corrija os erros demonstrados abaixo.</p>";
					$disableSubmit = FALSE;
				} else {
					
					if ( isset( $_POST['paciente_id'] ) ) {
					
						$pacienteID = $_POST['paciente_id'];
						$pacienteSelecionado = $wpdb->get_var("SELECT nome FROM traj_pacientes WHERE id = $pacienteID");
					
					} else {
					
						$wpdb->insert( 'traj_pacientes', $patientArr );
						$pacienteID = $wpdb->insert_id;
					
					}
					
					$examArr = array( 
						'paciente_id' => $pacienteID,
						'titulo' => $titulo,
						'obs_paciente' => $obsPaciente,
						'obs_medico' => $obsMedico,
						'senha_paciente' => $senhas['senhaPaciente'],
						'senha_medico' => $senhas['senhaMedico'],
						'datahora_criacao' => $dataHora,
						'arquivos' => $arquivos,
					);
					
					$wpdb->insert( 
						'traj_exames', 
						$examArr, 
						array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', )
					);
				
					echo "<p class='msg_on_success' id='successful_exam_create' >Exame cadastrado com sucesso! Veja os detalhes abaixo:</p>";
			
					echo (
						"<div class='passwords' id='display_passwords'>
							<p>
								Senha do medico: ".$senhas['senhaMedico'].
							"</p>
							<p>
								Senha do paciente: ".$senhas['senhaPaciente'].
							"</p>
						</div>"
					);
					
					$disableSubmit = TRUE;
				
				}
				
			}
			
			// let's cast our exam array to a new object so that we can use it as param in getForm() to construct our forms
			$examArr = (OBJECT) $examArr;
				
			switch ( $_GET['new_exam'] ) {
								
				case 'new_patient':
					
					$inputCreatePatient = (
						"<fieldset>
							<legend>Cadastro de paciente</legend>
							<p>
								<label for='nome_paciente'>Nome:</label>
								<input type='text' name='nome_paciente' id='nome_paciente' value='$nomePaciente' /><span class='input_error' id='titulo_error'>".$inputErrors['nome']."</span>
							</p>
							<p>
								<label for='matr_paciente'>Matrícula:</label>
								<input type='text' name='matr_paciente' id='matr_paciente' value='$matrPaciente' /><span class='input_error' id='titulo_error'>".$inputErrors['matricula']."</span>
							</p>
						</fieldset>"
					);
					// this will tell if form has actually been sent
					$inputCreatePatient .= "<input type='hidden' name='create_laudo' value='set' />";
					
					$formNew = self::getForm( 'createnew_laudo_form', $disableSubmit, $examArr, $inputCreatePatient, $inputErrors ); 
					
					echo $formNew;
					
					break;
				
				case 'select_patient':
					// constructing select dropdown menu...
					$pacientes = $wpdb->get_results( 'SELECT id, nome FROM traj_pacientes', OBJECT_K );
					 
					if ( !isset( $pacienteSelecionado ) ) {
						$inputSelectPatient = GaleriaLaudos::getDropdownList( $pacientes );
					} else {
						$inputSelectPatient = GaleriaLaudos::getDropdownList( $pacientes, $pacienteSelecionado );
					}
					// this will tell if form has actually been sent
					$inputSelectPatient .= "<input type='hidden' name='create_laudo' value='set' />";
					
					$formSelect = self::getForm( 'createselect_laudo_form', $disableSubmit, $examArr, $inputSelectPatient, $inputErrors );
					
					echo $formSelect;
												
					break;
					
				default:
					
					break;
			}
			
			if ( isset( $_GET['edit_exam'] ) ) {
				
				$pacientes = $wpdb->get_results( 'SELECT id, nome FROM traj_pacientes', OBJECT_K );
				
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
							
							$pacienteID = preg_replace('/[^0-9]/', '', $_GET['paciente_id'] );
							
							if ( array_key_exists( $pacienteID, $pacientes ) ) {
								
								$exams = $wpdb->get_results( "SELECT * FROM traj_exames WHERE paciente_ID = $pacienteID", OBJECT_K );
								
								$table = "<table class='traj_table' id'traj_table_exams' >";
								$table .= "<tr><th>Exame</th><th>Criado em</th><th>Modificado em</th><th>Editar</th><th>Excluir</th></tr>";
								
								foreach ( $exams as $exam ) {
									
									$table .= "<tr><td>".$exam->titulo."</td><td>".$exam->datahora_criacao."</td><td>".$exam->datahora_modificacao."</td>";
									$table .= "<td class='traj_table_editrow'><a href='$currentPageURL&edit_exam=change_details&paciente_id=".$pacienteID."&exame_id=".$exam->id."&option=edit'>editar</a></td>";
									$table .= "<td class='traj_table_delrow'><a href='$currentPageURL&edit_exam=change_details&paciente_id=".$pacienteID."&exame_id=".$exam->id."&option=delete' class='confirm_deletion'>deletar</a></td></tr>";
									
								}
								
								$table .= "</table>";
								
								if( !isset( $_GET['exame_id'] ) ) {
									
									echo "<p class='msg' id='exame_de' >Exames de " . $pacientes[$pacienteID]->nome . "</p>";
									echo $table;
								
								} else {
									// make sure the passed param has only numbers and nothing else
									$exameID = preg_replace( '/[^0-9]/', '', $_GET['exame_id'] );	
									// what does the user want to do with this exam?
									switch ( $_GET['option'] ) {
										// in case he wants to edit the exam...
										case 'edit':
											// hidden input to post the form
											$inputEditLaudo = "<input type='hidden' name='edit_laudo' value='set' />";
											// reset input errors array 
											$inputErrors = array();
											// validate 'titulo' input field
											if ( preg_match( '/[^0-9a-z\s]/i', $_POST['titulo'] ) == TRUE ) {
												$inputErrors['titulo'] = self::tituloInputError;
											}
											// check if form has not been sent yet or if there were input errors
											if ( !isset( $_POST['edit_laudo'] ) || !empty( $inputErrors ) ) {
												// form has not been sent yet, so let's do it
												echo self::getForm( 'edit_laudo_form', $disableSubmit=FALSE, $exams[$exameID], $inputEditLaudo, $inputErrors );
													
											} else {
												// form has been sent and there were no input errors... time to sanitize text field inputs
												$obsMedico = sanitize_text_field( $_POST['obs_medico'] );
												$obsPaciente = sanitize_text_field( $_POST['obs_paciente'] );
												
												if ( $wpdb->update( 'traj_exames', array( 'titulo' => $_POST['titulo'], 'obs_medico' => $obsMedico, 'obs_paciente' => $obsPaciente, 'datahora_modificacao' => current_time('mysql') ), array( 'id' => $exameID ) ) )
													echo "<p class='msg_on_success' id='successful_exam_edit' >Exame editado com sucesso!</p>";
												else
													echo "<p class='msg_on_failure' id='unsuccessful_exam_edit' >Não foi possível atualizar os dados do exame. Contate o administrador do sistema.</p>";
		
											}
											
											break;
										// in case he wants to delete the exam...	
										case 'delete':
											// check if deletion has been performed...
											if ( $wpdb->delete('traj_exames', array( 'id' => $exameID ) ) ) { 								# was he really sure he meant to do that....... trimmmmmm trajettoria gets a call
												echo "<p class='msg_on_success' id='successful_exam_delete' >Exame deletado com sucesso!</p>";
											// nothing has been touched!
											} else {
												echo "<p class='msg_on_failure' id='unsuccessful_exam_delete' >Não foi possível deletar o exame. Contate o administrador do sistema.</p>";
											}
										
											break;
										
										default:
											
											break;
									}

								}
								
							}
							
						}
						
						break;
					
					case 'change_pws':
						
						if ( !isset( $_GET['paciente_id'] ) ) {
							
							echo $form;
							
						} else {
							
							$pacienteID = preg_replace( '/[^-0-9]/', '', $_GET['paciente_id'] );
							
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
											'senha_paciente' => wp_generate_password(8, FALSE),
											'senha_medico' => wp_generate_password(8, FALSE),
										);
										
										$wpdb->update( 'traj_exames', $senhas, array( id => $examID ) );
										
									}
									
									echo "<p class='msg_on_success' id='successfull_pw_change'>Novas senhas geradas com sucesso!</p>";	
									
									foreach ( $exams as $exam ) {
											
										if( in_array( $exam->id, $examIDs ) ) {
											echo (
												"<p class='msg_on_success' id='successful_pw_change_examtitle'>".$exam->titulo."</p>
												<ul class='msg_on_success' id='successful_pw_change_list' >
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
	 * getForm
	 * 
	 * - required $formID string ( the html id property for the div which will wrap the form )
	 * - required $disableSubmit boolean ( if true, submit button will be disabled. If false, it stays enabled )
	 * - optional $valuesObj object ( an object which methods return strings. Use this to access pre-defined values for 'titulo', 'obs_medico' and 'obs_paciente' input fields )
	 * - optional $customInput string ( use this if you want to have custom input fields )
	 * - optional $inputErrors array ( use this to span input errors or maybe recommendations for the fields )
	 * 
	 * - return string with the form
	 */
	public static function getForm( $formID, $disableSubmit, $valuesObj="", $customInput="", $inputErrors="" ) {
		
		$form = "<div class='traj_form' id='$formID'>
					<form method='POST' enctype='multipart/form-data' action=''>";
		$form .= $customInput;
		$form .= "		<fieldset>
							<legend>Cadastro de exame</legend>
							<p>
								<label for='title'>Título:</label>
								<input type='text' name='titulo' id='laudo_title' value='$valuesObj->titulo' /><span class='input_error' id='titulo_error'>".$inputErrors['titulo']."</span>
							</p>
							<p>
								<label for='obs_medico'>Anotações para o médico:</label>
								<textarea name='obs_medico' id='obs_medico'>$valuesObj->obs_medico</textarea>
							</p>
							<p>
								<label for='obs_paciente'>Anotações para a paciente:</label>
								<textarea name='obs_paciente' id='obs_paciente'>$valuesObj->obs_paciente</textarea>
							</p>
	    					<div class='file_upload' id='file1'><input name='file[]' type='file'/>1</div>
	    					<div id='file_tools'>
	        					<div id='add_file'>Adicionar</div>
	        						<div id='del_file'>Remover</div>
	    					</div>
						</fieldset>
						<input type='submit' value='Enviar' ";
		if ( $disableSubmit === TRUE ) {
			$form .= "disabled='disabled'";
		}
		$form .= "/>
					</form>
				</div>";
		
		return $form;
		
	}
	
	/*
	 * getDropdownList
	 * 
	 * - required $queryResults object ( database query results to populate the dropdown list )
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
		// Register the scripts for the plugin
		wp_register_script( 'multiple-uploads', plugins_url( '/js/multiple-uploads.js', __FILE__ ), array( 'jquery' ) );
		wp_register_script( 'dialog-box', plugins_url( '/js/dialog-box.js', __FILE__ ), array( 'jquery-ui-dialog', 'jquery-ui-core', 'jquery-ui-tabs' ) );
		// Enqueue the scripts
		wp_enqueue_script( 'multiple-uploads' );
		wp_enqueue_script( 'dialog-box' );
	
	}
	
	public static function loadStyle() {
		wp_enqueue_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
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
		
		global $wpdb;
		
		$currentPageURL = get_permalink();
		
		$inputErrors = array();
		
		$matricula = $_POST['matricula_input'];
		$senha = $_POST['senha_input'];
		
		$result = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) as total FROM traj_pacientes WHERE matricula = $matricula" ) );
		if( $result['total'] == 0 ) {
			$inputErrors['matricula'] = "<p class='msg_on_failure' id='wrong_matricula'>A matricula digitada nao existe. Por favor, tente novamente.</p>";
		}

		/*
		 * modificar
		$exame = $wpdb->get_var( "SELECT id FROM traj_exames WHERE paciente_id = $pacienteID AND ( senha_paciente = $senha OR senha_medico = $senha )"  );
		if ( $exame === FALSE ) {
			$inputErrors['senha'] = "<p class='msg_on_failure' id='wrong_password'></p>";
		}
		*/
		
		if ( !isset( $_POST['submit'] ) || !empty( $inputErrors ) )  {
			
			foreach ( $inputErrors as $error ) {
				echo $error;
			}
			
			echo (
				"<div>
					<p class='msg' id='confirm_user'>Entre com a matricula do paciente e a senha associada ao exame para poder visualizar os dados do mesmo.</p>
					<form method='POST' enctype='multipart/form-data' action=''>
						<input type='hidden' name='submit' value='set' />
						<input type='text' name='matricula_input' id='traj_matricula_input' />
						<input type='password' name='password_input' id='traj_password_input' />
						<input type='submit' value='Ok' />
					</form>
				</div>"
			);
			
		} else {
			
			$exame = $wpdb->get_row( "SELECT * FROM traj_exames WHERE id = $pacienteID", OBJECT_K );
			echo $pacienteID;
			echo $exame;
			echo "tudo certo";
			
		}
		
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

add_action( 'wp_enqueue_styles', array( 'GaleriaLaudos', 'loadStyle' ) );

?>