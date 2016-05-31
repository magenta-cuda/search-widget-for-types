jQuery(document).ready(function(){
    var select=jQuery("form[id|='search-types-custom-fields-widget'] select.post_type");
    select.find("option[value='no-selection']").prop("selected",true);
    select.change(function(){
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
                    if(jQuery(this).text()==stcfwSearchTranslations.open){
                        jQuery(this).text(stcfwSearchTranslations.close);
                        jQuery("div.scpbcfw-search-field-values",this.parentNode).css("display","block");
                    }else{
                        jQuery(this).text(stcfwSearchTranslations.open);
                        jQuery("div.scpbcfw-search-field-values",this.parentNode).css("display","none");
                    }
                    return false;
                });
                form.find("div.scpbcfw-search-field-values input[type='checkbox'],div.scpbcfw-search-field-values input[type='text']").change(function(){
                    var checked=false;
                    jQuery(this).parents("div.scpbcfw-search-field-values").find("input[type='checkbox']").each(function(){
                        checked=checked||jQuery(this).prop("checked");
                    });
                    jQuery(this).parents("div.scpbcfw-search-field-values").find("input[type='text']").each(function(){
                        checked=checked||jQuery(this).val();
                    });
                    var container=jQuery(this).parents("div.scpbcfw-search-fields");
                    if(checked){
                        container.removeClass("stcfw-nohighlight").addClass("stcfw-highlight");
                    }else{
                        container.removeClass("stcfw-highlight").addClass("stcfw-nohighlight");
                    }
                });
            }
        );
        var container=select.parents("div.scpbcfw-search-post-type");
        if(form.find("select#post_type option:selected").val()==="no-selection"){
            container.removeClass("stcfw-highlight").addClass("stcfw-nohighlight");
        }else{
            container.removeClass("stcfw-nohighlight").addClass("stcfw-highlight");
        }
    });
    jQuery("form[id|='search-types-custom-fields-widget']").each(function(){
        if(jQuery(this).find("select.post_type option.real_post_type").length===1){
            var postType=jQuery(this).find("select.post_type");
            postType.find("option.real_post_type").prop("selected",true);
            postType.change().parent("div").css("display","none");
        }
    });
    /*
    jQuery("div.scpbcfw-search-field-values").each(function(){
        var checked=false;
        jQuery(this).find("input[type='checkbox']").each(function(){
            checked=checked||jQuery(this).prop("checked");
        });
        jQuery(this).parents("div.scpbcfw-search-fields").css("background-color",checked?"transparent":"lightgray");
    });
    */
    jQuery("input#scpbcfw-search-fields-submit").click(function(e){
        var div=jQuery("div#stcfw-inline_search_results");
        if(div.length){
            var query="action=stcfw_get_posts&"+jQuery(this).parents("form.scpbcfw-search-fields-form").serialize();
            console.log("input#scpbcfw-search-fields-submit::click():query=",query);
            jQuery.get(ajaxurl,query,function(r){
                console.log("input#scpbcfw-search-fields-submit::post():r=",r);
                if(r.success){
                    var data=JSON.parse(r.data);
                    console.log("input#scpbcfw-search-fields-submit::post():data=",data);
                    div.find("div.stcfw-inner_envelope").text(data);
                }else{
                    div.text(r.data);
                }
                div.show();
            });
            e.preventDefault();
        }
    });
    jQuery("button.stcfw-close_inner_envelope").click(function(e){
        jQuery(this).parents("div.stcfw-outer_envelope").find("div.stcfw-inner_envelope").toggle();
    });
});
