jQuery(document).ready(function(){
    jQuery("form[id|='search-types-custom-fields-widget'] select.post_type").change(function(){
        var id=jQuery(this).parents("form.scpbcfw-search-fields-form")[0].id.match(/-(\d+)$/)[1];
        var form=jQuery("form#search-types-custom-fields-widget-"+id);
        jQuery.post(
            ajaxurl,{
                action:"get_form_for_post_type",
                stcfw_get_form_nonce:form.find("input#scpbcfw-search-fields-nonce").val(),
                post_type:form.find("select#post_type option:selected").val(),
                search_types_custom_fields_widget_option:form.find("input#search_types_custom_fields_widget_option").val(),
                search_types_custom_fields_widget_number:form.find("input#search_types_custom_fields_widget_number").val()
            },
            function(response){
                form.find("div#search-types-custom-fields-parameters").html(response);
                form.find("input#scpbcfw-search-fields-submit").prop("disabled",false);
                form.find("div#scpbcfw-search-fields-submit-container").css("display",response?"block":"none");
                form.find("div.scpbcfw-display-button").click(function(event){
                    if(jQuery(this).text()=="Open"){
                        jQuery(this).text("Close");
                        jQuery("div.scpbcfw-search-field-values",this.parentNode).css("display","block");
                    }else{
                        jQuery(this).text("Open");
                        jQuery("div.scpbcfw-search-field-values",this.parentNode).css("display","none");
                    }
                    return false;
                });
            }
        );
    });
    jQuery("form[id|='search-types-custom-fields-widget']").each(function(){
        if(jQuery(this).find("select.post_type option.real_post_type").length===1){
            var postType=jQuery(this).find("select.post_type");
            postType.find("option.real_post_type").prop("selected",true);
            postType.change().parent("div").css("display","none");
        }
    });
});
