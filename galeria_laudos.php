<?php
/*
Plugin Name: Galeria de Laudos Médicos
Plugin URI: http://www.trajettoria.com
Description: Plugin para controle e exibicao de laudos medicos para medicos e pacientes
Author: Renato Zuma Bange
Version: 1.0
Author URI: http://www.trajettoria.com
*/

class GaleriaLaudos {
	
	// file upload config
	const allowedMimeTypes = 'image/jpeg,image/gif,image/png,image/bmp,video/x-flv';
	const maxFileSize = 10485760;
	// error messages
	const nomeInputError = 'O nome deve conter apenas letras.';
	const matriculaInputError = 'A matrícula deve conter apenas números.';
	const matriculaInputExists = 'A matrícula digitada já existe.';
	const pacienteNotSelected = 'É necessário selecionar um paciente.';
	const tituloInputError = 'O título deve conter apenas letras e/ou números.';
	const inputIsEmpty = 'Esse campo é obrigatório.';
	const inputsAreEmpty = 'É necessário preencher um dos campos abaixo ou enviar ao menos um arquivo.';
	
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
		
		// first of all, check user perms to make sure he is an admin
		if ( current_user_can('manage_options') ) {
			// current page URL
			$currentPageURL = get_permalink();
			// the control panel menu
			echo (
				"<div class='traj_menu'>
					<ul>
						<li>Cadastrar exame</li>
							<ul>
								<li><a href='$currentPageURL&new_exam=new_patient'>Novo paciente</a></li>
								<li><a href='$currentPageURL&new_exam=select_patient'>Selecionar paciente existente</a></li>
							</ul>
						<li><a href='$currentPageURL&edit=exam' >Exames Cadastrados</a></li>
						<li><a href='$currentPageURL&edit=patient' >Pacientes Cadastrados</a></li>
						<li><a href='$currentPageURL&edit=pws' >Alterar senhas</a></li>
					</ul>
				</div>"
			);
			
			// checking if form for new exam has already been sent
			if ( isset( $_POST['create_laudo'] ) ) {
							
				$dataHora = current_time('mysql');
				
				if ( !isset( $_POST['paciente_id'] ) ) {
					
					$nomePaciente = trim( $_POST['nome_paciente'] );
					if ( preg_match( '/[^a-z\s\w]/iu', $nomePaciente ) == TRUE ) {
						$inputErrors['nome'] = self::nomeInputError;
					} elseif ( empty($nomePaciente) ) {
						$inputErrors['nome'] = self::inputIsEmpty;
					}
					$matrPaciente = trim( $_POST['matr_paciente'] );
					$result = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) as totalMatriculas FROM traj_pacientes WHERE matricula = $matrPaciente" ) );
					if ( preg_match( '/[^0-9]/', $matrPaciente ) == TRUE ) {
						$inputErrors['matricula'] = self::matriculaInputError;
					} elseif ( $result['totalMatriculas'] != 0 ) {
						$inputErrors['matricula'] = self::matriculaInputExists;
					} elseif ( empty($matrPaciente) ) {
						$inputErrors['matricula'] = self::inputIsEmpty;
					}
					$patientArr = array(
						'nome' => $nomePaciente,
						'matricula' => $matrPaciente,
						'datahora' => $dataHora,
					);
					
				} elseif ( $_POST['paciente_id'] == 'selecione' ) {
					$inputErrors['paciente'] = self::pacienteNotSelected;
				}
				
				$titulo = trim( $_POST['titulo'] );
				if ( preg_match( '/[^0-9a-z\s\w]/iu', $titulo ) == TRUE ) {
					$inputErrors['titulo'] = self::tituloInputError;
				} elseif ( empty($titulo) ) {
					$inputErrors['titulo'] = self::inputIsEmpty;
				}
				
				$obsPaciente = sanitize_text_field( $_POST['obs_paciente'] );
				$obsMedico = sanitize_text_field( $_POST['obs_medico'] );
				if ( is_array( $_FILES['file'] ) && !empty( $_FILES['file'] ) ) {
					$arquivos = @implode( ',', GaleriaLaudos::processUploads( explode( ',', self::allowedMimeTypes ), self::maxFileSize ) );
				}
				
				if ( empty( $obsPaciente ) && empty( $obsMedico ) && empty( $arquivos ) ) {
					$inputErrors['exame'] = self::inputsAreEmpty;
				}
				
				$senhas = array(
					'senhaPaciente' => wp_generate_password(8, FALSE),
					'senhaMedico' => wp_generate_password(8, FALSE),
				);
				
				if ( !empty( $inputErrors ) ) {
					echo "<p class='msg_on_failure' id='unsuccessful_exam_create' >Por favor, corrija os erros demonstrados abaixo.</p>";
					echo "<span class='input_error' id='selected_error'>".$inputErrors['paciente']."</span>";
					$disableSubmit = FALSE;
					$disabled = "";
				} else {
					// if paciente_id has been posted, he has selected a patient
					if ( isset( $_POST['paciente_id'] ) ) {
						// get selected patient id
						$pacienteID = $_POST['paciente_id'];
						// get his name
						$pacienteSelecionado = $wpdb->get_var("SELECT nome FROM traj_pacientes WHERE id = $pacienteID");
					// else, he is creating a new patient
					} else {
						// insert new patient into database
						$wpdb->insert( 'traj_pacientes', $patientArr );
						// get new patient id
						$pacienteID = $wpdb->insert_id;
					
					}
					// prepare new exam details in $examArr
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
					// insert data into db
					$wpdb->insert( 
						'traj_exames', 
						$examArr, 
						array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', )
					);
					// echo success msg
					echo "<p class='msg_on_success' id='successful_exam_create' >Exame cadastrado com sucesso! Veja os detalhes abaixo:</p>";
					// echo passwords for the new exam
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
					// everything is set, so we can disable the submit button
					$disableSubmit = TRUE;
					$disabled = "disabled='disabled'";
				
				}
				
			}
			
			// let's cast our exam array to a new object so that we can use it as param in getForm() to construct our forms
			$examArr = (OBJECT) $examArr;
			// how does the user want to create the new exam?
			switch ( $_GET['new_exam'] ) {
				// in case he wants to create a new patient for it
				case 'new_patient':
					// prepare a 'custom input' for getForm()
					$inputCreatePatient = (
						"<fieldset>
							<legend>Cadastro de paciente</legend>
							<p>
								<label for='nome_paciente'>Nome:</label>
								<input type='text' name='nome_paciente' id='nome_paciente' value='$nomePaciente' $disabled/><span class='input_error' id='titulo_error'>".$inputErrors['nome']."</span>
							</p>
							<p>
								<label for='matr_paciente'>Matrícula:</label>
								<input type='text' name='matr_paciente' id='matr_paciente' value='$matrPaciente' $disabled/><span class='input_error' id='titulo_error'>".$inputErrors['matricula']."</span>
							</p>
						</fieldset>"
					);
					// this will later tell if form was sent
					$inputCreatePatient .= "<input type='hidden' name='create_laudo' value='set' />";
					// echo form with inputs for a new patient
					echo self::getForm( 'createnew_laudo_form', $disableSubmit, $examArr, $inputCreatePatient, $inputErrors );
					
					break;
				// in case he wants to create the new exam for an existing patient
				case 'select_patient':
					// constructing select dropdown menu...
					$pacientes = $wpdb->get_results( 'SELECT id, nome, matricula FROM traj_pacientes', OBJECT_K );
					// if this is his first attempt to fill the form
					if ( !isset( $pacienteSelecionado ) ) {
						// we have no selected patient yet...
						$inputSelectPatient = GaleriaLaudos::getDropdownList( $pacientes );
					// else, he has filled the form properly and we are just showing him what he has done
					} else {
						// we have a selected patient
						$inputSelectPatient = GaleriaLaudos::getDropdownList( $pacientes, $pacienteSelecionado );
					}
					// this will tell if form has actually been sent
					$inputSelectPatient .= "<input type='hidden' name='create_laudo' value='set' />";
					// echo form with a select dropdown list
					echo self::getForm( 'createselect_laudo_form', $disableSubmit, $examArr, $inputSelectPatient, $inputErrors );
												
					break;
					
				default:
					
					break;
			}
			// if user wants to 'edit/delete exam' or even just change the passwords
			if ( isset( $_GET['edit'] ) ) {
				// get all existing patients
				$pacientes = $wpdb->get_results( 'SELECT id, nome, matricula FROM traj_pacientes', OBJECT_K );
				// prepare form for patient select
				$form = "<form method='GET' enctype='multipart/form-data' action=''>";
				$form .= "	<input type='hidden' name='page_id' value='".$post->ID."' />";
				$form .= "	<input type='hidden' name='edit' value='".$_GET['edit']."' />";
				$form .= GaleriaLaudos::getDropdownList( $pacientes );
				$form .= "	<input type='submit' value='Enviar' />";
				$form .= "</form>";
				// now what exactly does he want to do?
				switch ( $_GET['edit'] ) {
					// in case he wants to change exam details
					case 'exam':
						// check if form has been sent (using GET here should be a security risk, but he's an admin anyway and it makes for a much easier debug process)
						if ( !isset( $_GET['paciente_id'] ) ) {
							// let him select a patient
							echo $form;
						// we have a chosen patient
						} else {
							// fix query string just in case he has accidently typed something in the address bar 
							$pacienteID = preg_replace('/[^0-9]/', '', $_GET['paciente_id'] );
							// another check to see if query string param matches an existing patient
							if ( array_key_exists( $pacienteID, $pacientes ) ) {
								
								$exams = $wpdb->get_results( "SELECT * FROM traj_exames WHERE paciente_ID = $pacienteID", OBJECT_K );
								
								$table = "<table class='traj_table' id'traj_table_exams' >";
								$table .= "<tr><th>Exame</th><th>Criado em</th><th>Modificado em</th><th>Editar</th><th>Excluir</th></tr>";
								
								foreach ( $exams as $exam ) {
									
									$table .= "<tr><td>".$exam->titulo."</td><td>".self::mysqlToBR( $exam->datahora_criacao )."</td><td>".self::mysqlToBR( $exam->datahora_modificacao )."</td>";
									$table .= "<td class='traj_table_editrow'><a href='$currentPageURL&edit=exam&paciente_id=".$pacienteID."&exame_id=".$exam->id."&option=edit'>editar</a></td>";
									$table .= "<td class='traj_table_delrow'><a href='$currentPageURL&edit=exam&paciente_id=".$pacienteID."&exame_id=".$exam->id."&option=delete' class='confirm_deletion'>deletar</a></td></tr>";
									
								}
								
								$table .= "</table>";
								
								if( !isset( $_GET['exame_id'] ) ) {
									
									echo "<p class='traj_msg' id='exame_de' >Exames de " . $pacientes[$pacienteID]->nome . "</p>";
									echo $table;
								
								} else {
									// make sure the passed param has only numbers and nothing else
									$exameID = preg_replace( '/[^0-9]/', '', $_GET['exame_id'] );	
									// what does the user want to do with this exam?
									switch ( $_GET['option'] ) {
										// in case he wants to edit it...
										case 'edit':
											// hidden input to check form posting
											$inputEditLaudo = "<input type='hidden' name='edit_laudo' value='set' />";
											// checkbox input for old files (if there's any)... user might want to delete them
											if ( !empty( $exams[$exameID]->arquivos ) ) {
												$inputEditLaudo .= "<div class='traj_oldfiles' id='traj_delconfirm_box'>";
												$inputEditLaudo .= "<p class='traj_msg' id='traj_delconfirm'>Você pode deletar os arquivos do exame marcando-os abaixo (essa ação não pode ser revertida):</p>";
												$oldFiles = explode( ',', $exams[$exameID]->arquivos );
												foreach ( $oldFiles as $file ) {

													$inputEditLaudo .= "<p class='traj_checkbox'>";
													$inputEditLaudo .= "<input type='checkbox' name='oldfile[]' value='".$file."' /> ".$file;
													$inputEditLaudo .= "</p>";
													
												}
												$inputEditLaudo .= "</div>";
											}
											// reset input errors array 
											$inputErrors = array();
											// validate 'titulo' input field
											if ( preg_match( '/[^0-9a-z\s\w]/iu', $_POST['titulo'] ) == TRUE ) {
												$inputErrors['titulo'] = self::tituloInputError;
											}
											// check if form has not been sent yet or if there were input errors
											if ( !isset( $_POST['edit_laudo'] ) || !empty( $inputErrors ) ) {
												// show form
												echo self::getForm( 'edit_laudo_form', $disableSubmit=FALSE, $exams[$exameID], $inputEditLaudo, $inputErrors );
											// everything went fine...		
											} else {
												// sanitize text field inputs
												$obsMedico = sanitize_text_field( $_POST['obs_medico'] );
												$obsPaciente = sanitize_text_field( $_POST['obs_paciente'] );
												// if user has checked any existing file to be deleted
												if ( is_array( $_POST['oldfile'] ) ) {
													// delete files
													foreach ( $_POST['oldfile'] as $key => $value) {
														unlink( plugin_dir_path( __FILE__ ).'uploads/'.$value );
													}
													// this will get the old files to keep (the ones not checked by the user)
													$oldFiles = implode( ',', array_diff( $oldFiles, $_POST['oldfile'] ) );
												// else check if there were old files anyway
												} elseif ( is_array( $oldFiles ) ) {
													// implode $oldFiles back to it's string type so that we can save it into db later
													$oldFiles = implode( ',', $oldFiles );
												}
												
												$arquivos = GaleriaLaudos::processUploads( explode( ',', self::allowedMimeTypes ), self::maxFileSize );
												
												if ( is_array( $arquivos ) ) {
													$arquivos = implode( ',', $arquivos ).",".$oldFiles;
												} else {
													$arquivos = $oldFiles;
												}
												
												if ( substr($arquivos, -1) == ',' )
													$arquivos = substr($arquivos, 0, -1);
												
												if ( $wpdb->update( 'traj_exames', array( 'titulo' => $_POST['titulo'], 'obs_medico' => $obsMedico, 'obs_paciente' => $obsPaciente, 'datahora_modificacao' => current_time('mysql'), 'arquivos' => $arquivos ), array( 'id' => $exameID ) ) )
													echo "<p class='msg_on_success' id='successful_exam_edit' >Exame editado com sucesso!</p>";
												else
													echo "<p class='msg_on_failure' id='unsuccessful_exam_edit' >Não foi possível atualizar os dados do exame. Contate o administrador do sistema.</p>";
		
											}
											
											break;
										// in case he wants to delete exam...	
										case 'delete':
											// check if exam deletion has been performed...
											if ( $wpdb->delete('traj_exames', array( 'id' => $exameID ) ) ) { 								#@todo ( implement jQuery confirm dialog ) was he really sure he meant to do that....... trimmmmmm trajettoria gets a call
												// check if there were files related to exam
												if ( !empty( $exams[$exameID]->arquivos ) ) {
													// explode string of filenames to array of filenames
													$examFiles = explode( ',', $exams[$exameID]->arquivos );
													// iterate over each filename
													foreach ( $examFiles as $file ) {
														// delete file
														unlink( plugin_dir_path( __FILE__ ).'uploads/'.$file );
														
													}
												}
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
					
					case 'patient':
						
						if( !isset( $_GET['patient_id'] ) ) {
							
							$patientsTable = "<table class='traj_table' id'traj_table_patients' >";
							$patientsTable .= "<tr><th>Paciente</th><th>Matrícula</th><th>Editar</th><th>Excluir</th></tr>";
							
							foreach ( $pacientes as $patient ) {
								
								$patientsTable .= "<tr><td>".$patient->nome."</td><td>".$patient->matricula."</td>";
								$patientsTable .= "<td class='traj_table_editrow'><a href='$currentPageURL&edit=patient&patient_id=".$patient->id."&option=edit'>editar</a></td>";
								$patientsTable .= "<td class='traj_table_delrow'><a href='$currentPageURL&edit=patient&patient_id=".$patient->id."&option=delete' class='confirm_deletion'>deletar</a></td></tr>";
								
							}
							
							$patientsTable .= "</table>";
							
							echo $patientsTable;
							
						} else {
							
							echo (
								"<fieldset>
									<legend>Edição de paciente</legend>
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
							
						}
							
						break;	
						
					case 'pws':
						
						if ( !isset( $_GET['paciente_id'] ) ) {
							
							echo $form;
							
						} else {
							
							$pacienteID = preg_replace( '/[^-0-9]/', '', $_GET['paciente_id'] );
							
							if ( array_key_exists( $pacienteID, $pacientes ) ) {
								
								$exams = $wpdb->get_results( "SELECT id, titulo, senha_paciente, senha_medico FROM traj_exames WHERE paciente_id = $pacienteID", OBJECT_K );
							
								if ( !isset( $_POST['exam'] ) ) {
								
									$formChangePW = "<form method='POST' enctype='multipart/form-data' action=''>";
									
									foreach ( $exams as $exam ) {
										
										$formChangePW .= "<p class='traj_checkbox'>";
										$formChangePW .= "<input type='checkbox' name='exam[]' value='".$exam->id."' /> ".$exam->titulo;
										$formChangePW .= "</p>";
										
									}
									
									$formChangePW .= "<input type='submit' value='Enviar' />";
									$formChangePW .= "</form>";
									
									echo $formChangePW;

								} else {
								
									$examIDs = preg_replace('/[^-0-9]/', '', $_POST['exam'] );
									

									foreach ( $examIDs as $key => $id ) {
										
										$senhas[$id] = array(
											'senha_paciente' => wp_generate_password(8, FALSE),
											'senha_medico' => wp_generate_password(8, FALSE),
										);
										
										$wpdb->update( 'traj_exames', $senhas[$id], array( id => $id ) );
										
									}
									
									echo "<p class='msg_on_success' id='successfull_pw_change'>Novas senhas geradas com sucesso!</p>";	
									
									foreach ( $exams as $exam ) {
											
										if( in_array( $exam->id, $examIDs ) ) {
											echo (
												"<p class='msg_on_success' id='successful_pw_change_examtitle'>".$exam->titulo."</p>
												<ul class='msg_on_success' id='successful_pw_change_list' >
													<li>Senha para o médico: " . $senhas[$exam->id]['senha_medico'] . "</li>
													<li>Senha para o paciente: " . $senhas[$exam->id]['senha_paciente'] . "</li>
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
		
		$disable = "";
		if ( $disableSubmit === TRUE ) {
			$disable = "disabled='disabled'";
		}
		$form = "<div class='traj_form' id='$formID'>
					<form method='POST' enctype='multipart/form-data' action=''>";
		$form .= $customInput;
		$form .= "		<fieldset>
							<legend>Cadastro de exame</legend>
							<p>
								<label for='title'>Título:</label>
								<input type='text' name='titulo' id='laudo_title' value='".$valuesObj->titulo."' $disable/><span class='input_error' id='titulo_error'>".$inputErrors['titulo']."</span>
							</p>
							<span class='input_error' id='exame_error'>".$inputErrors['exame']."</span>
							<p>
								<label for='obs_medico'>Anotações para o médico:</label>
								<textarea name='obs_medico' id='obs_medico' $disable >".$valuesObj->obs_medico."</textarea>
							</p>
							<p>
								<label for='obs_paciente'>Anotações para a paciente:</label>
								<textarea name='obs_paciente' id='obs_paciente' $disable>".$valuesObj->obs_paciente."</textarea>
							</p>
	    					<div class='file_upload' id='file1'><input name='file[]' type='file' $disable/>1</div>
	    					<div id='file_tools'>
	        					<div id='add_file'>Adicionar</div>
	        						<div id='del_file'>Remover</div>
	    					</div>
						</fieldset>
						<input type='submit' value='Enviar' $disable/>
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
					
					$disabled = "";
					if ( $selectedOption != 'Selecione...' ) {
						$disabled = "disabled='disabled'";
					}
		
					$dropdownList = (
						"<fieldset>
							<legend>Seleção de paciente</legend>
							<p>
								<label for='nome_paciente'>Pacientes cadastrados:</label>
								<select name='paciente_id' id='paciente_dropdown' $disabled>
									<option value='selecione'>$selectedOption</option>"
					);
							
					foreach ( $queryResults as $option ) {
						$dropdownList .= "<option value='".$option->id."'>".$option->nome." : ".$option->matricula."</option>";
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
		
		wp_register_style('jquery-ui-css', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css', TRUE );
		wp_enqueue_style( 'jquery-ui-css' );
		// Register the scripts for the plugin
		wp_register_script( 'multiple-uploads', plugins_url( '/js/multiple-uploads.js', __FILE__ ), array( 'jquery' ) );
		wp_register_script( 'dialog-box', plugins_url( '/js/dialog-box.js', __FILE__ ), array( 'jquery-ui-dialog', 'jquery-ui-core', 'jquery-ui-tabs' ) );
		// Enqueue the scripts
		wp_enqueue_script( 'multiple-uploads' );
		wp_enqueue_script( 'dialog-box' );
	
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
					// drop the extension, sanitize file name and remove accents, we need just the clean title
					$filetitle = remove_accents( sanitize_file_name( basename( $filename, '.'.$filetype['ext'] ) ) );
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
	
	public static function mysqlToBR( $mysqlTime ) {
		$dateTime = explode( ' ', $mysqlTime );
		$dateTime[0] = explode( '-', $dateTime[0] );
		$dateTime = $dateTime[0][2].'-'.$dateTime[0][1].'-'.$dateTime[0][0].' '.$dateTime[1];
		
		return $dateTime;
	}
	
	/*
	 * 
	 */
	public static function loadUserInterface() {
		// wp database global
		global $wpdb;
		// get current page URL
		$currentPageURL = get_permalink();
		// reset errors array
		$inputErrors = array();
		// if form has been sent
		if ( isset( $_POST['submit'] ) ) {

			$matricula = $_POST['matricula_input'];
			$senha = $_POST['senha_input'];
			// search database for a patient whose 'matricula' matches user input
			$result = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) as totalPatients FROM traj_pacientes WHERE matricula = $matricula" ) );
			// if there's no such a patient
			if( $result['totalPatients'] == 0 ) {
				// prepare 'wrong matricula' error msg
				$inputErrors['matricula'] = "<p class='msg_on_failure' id='wrong_matricula'>A matricula digitada nao existe. Por favor, tente novamente.</p>";
			// else, we found a patient
			} else {
				// get patient ID
				$pacienteID = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM traj_pacientes WHERE matricula = $matricula" ) );
				// search database for an exam which patient ID and password (be it patient's or medic's password) matches user input
				$result = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) as totalExams FROM traj_exames WHERE paciente_id = $pacienteID AND ( senha_paciente = '$senha' OR senha_medico = '$senha' )" ) );
				// if no luck
				if ( $result['totalExams'] == 0 ) {
					// prepare 'wrong senha' error msg
					$inputErrors['senha'] = "<p class='msg_on_failure' id='wrong_senha'>A senha digitada nao corresponde a nenhum exame cadastrado para essa matricula. Por favor, tente novamente.</p>";
				}
			}
			
		}
		// if form has not been sent OR there were errors in his last attempt to send it
		if ( !isset( $_POST['submit'] ) || !empty( $inputErrors ) )  {
			// show errors
			foreach ( $inputErrors as $error ) {
				echo $error;
			}
			// show form
			echo (
				"<div>
					<p class='msg' id='confirm_user'>Entre com a matrícula do paciente e a senha associada ao exame para poder visualizar os dados do mesmo.</p>
					<form method='POST' enctype='multipart/form-data' action=''>
						<input type='hidden' name='submit' value='set' />
						<p>
							<label for='traj_matricula_input'>Matrícula:</label>
							<input type='text' name='matricula_input' id='traj_matricula_input' />
						</p>
						<p>
							<label for='traj_password_input'>Senha:</label>
							<input type='password' name='senha_input' id='traj_password_input' />
						</p>
						<input type='submit' value='ok' />
					</form>
				</div>"
			);
		// else, he has sent the form and there were no errors
		} else {
			// get all exam details
			$exame = $wpdb->get_row( "SELECT * FROM traj_exames WHERE paciente_id = $pacienteID AND ( senha_paciente = '$senha' OR senha_medico = '$senha' )" );
			// show exam title
			echo "<h2 class='traj_exam_title'>".$exame->titulo."</h2>";
			// if there are any related files...
			if ( !empty( $exame->arquivos ) ) {
				// rebuild files array
				$arquivos = explode( ',', $exame->arquivos );
				// iterate over files to separate them by type
				foreach ( $arquivos as $filename ) {
					// check file type
					$filetype = wp_check_filetype( $filename );
					// if it's a video
					if ( $filetype['ext'] == 'flv' ) {
						$videos[] = $filename;
					// else it's an image
					} else {
						$images[] = $filename;	
					}
				}
				// if there's at least one video and the video player function exists
				if ( !empty( $videos ) && function_exists('hana_flv_player_template_call') ) {
					// title for the video galery
					echo "<h3 class='traj_exam_files'>Vídeos do exame</h3>";
					// time to display the videos!
					foreach ( $videos as $vid ) {
						// configure video thumbs here
						$hana_arg="
						video='".plugins_url( "/uploads/$vid", __FILE__ )."'
						player='2'
						width='220'
						height='150'
						more_2=\"showStopButton: true, showScrubber: true, showVolumeSlider: false,showMuteVolumeButton: true, 
						showFullScreenButton: true, showMenu: false, controlsOverVideo: 'locked',controlBarBackgroundColor: -1,
						controlBarGloss: 'none', usePlayOverlay:true \"
						";
						echo "<div class='traj_video_thumbs'>".hana_flv_player_template_call($hana_arg)."</div>";
						
					}
	
				}
				// if there's at least one image
				if ( !empty( $images ) ) {
					// title for the image galery
					echo "<h3 class='traj_exam_files'>Imagens do exame</h3>";
					// time to display the images!
					foreach ( $images as $img ) {
						// configure image thumbs here
						echo "<a href='".plugins_url( "/uploads/$img", __FILE__ )."' rel='wp-video-lightbox'><img class='traj_image_thumbs' height='150' src='".plugins_url( "/uploads/$img", __FILE__ )."' /></a>";
					}
					
				}
				
			}
			// if user is the patient
			if ( $senha == $exame->senha_paciente ) {
				// show comments for patient
				echo "<p class='traj_exam_obs' id='traj_obs_paciente'>".$exame->obs_paciente."</p>";
			// else, he is the medic
			} else {
				// show comments for medic
				echo "<p class='traj_exam_obs' id='traj_obs_medico'>".$exame->obs_medico."</p>";
				
			}
			
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

?>