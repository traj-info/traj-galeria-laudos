jQuery(document).ready(function(){
	var $dialog = JQuery('<div></div>')
		.html('Tem certeza que deseja deletar os exames selecionados? Essa ação não pode ser revertida.')
		.dialog({
			autoOpen: false,
			title: 'Confirmar exclusão'
		});
		
	JQuery('#send_form').click(function(){
		$dialog.dialog('open');
		return false;
	});
});