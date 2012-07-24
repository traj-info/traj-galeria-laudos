jQuery(document).ready(function() {
    jQuery(".traj_del-exam").on("click", function(e) {
        var link = this;

        e.preventDefault();

        jQuery("<div>Tem certeza que deseja continuar? Todos os arquivos associados ao exame TAMBÉM serão deletados.</div>").dialog({
			title: "Confirmar remoção de exame",
            buttons: {
                "Cancelar": function() {
                    jQuery(this).dialog("close");
                },
                "Ok": function() {
                    window.location = link.href;
                }
            }
        });
    });
});