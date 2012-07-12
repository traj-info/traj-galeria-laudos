jQuery(document).ready(function(){
    var counter = 2;
    jQuery('#del_file').hide();
    jQuery('#add_file').click(function(){
    	jQuery('#file_tools').before('<div class="file_upload" id="file'+counter+'"><input name="file[]" type="file">'+counter+'</div>');
    	jQuery('#del_file').fadeIn(0);
    counter++;
    });
    jQuery('#del_file').click(function(){
        if(counter==3){
        	jQuery('#del_file').hide();
        }   
        counter--;
        jQuery('#file'+counter).remove();
    });
});