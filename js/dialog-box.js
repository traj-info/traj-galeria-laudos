jQuery(function() {
    jQuery(".confirm_deletion").on("click", function(e) {
        var link = this;

        e.preventDefault();

        jQuery("<div>Ao deletar um exame, os arquivos associados também são deletados. Tem certeza que deseja prosseguir?</div>").dialog({
            buttons: {
                "Ok": function() {
                    window.location = link.href;
                },
                "Cancel": function() {
                    jQuery(this).dialog("close");
                }
            }
        });
    });
});