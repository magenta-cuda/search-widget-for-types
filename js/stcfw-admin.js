var stcfwInitialized=[];
function stcfwInitialize(target){
    var jqTarget=jQuery(target);
    // make sure we don't initialize a widget more than once
    var marker=jQuery(target).find("div.scpbcfw-admin-button");
    if(stcfwInitialized.indexOf(marker[0])!==-1){return;}
    stcfwInitialized.push(marker[0]);
    jqTarget.find("div.scpbcfw-admin-display-button").click(function(e){
        if(jQuery(this).text()==stcfwAdminTranslations.open){
            jQuery(this).text(stcfwAdminTranslations.close);
            jQuery("div.scpbcfw-search-field-values",this.parentNode).css("display","block");
        }else{
            jQuery(this).text(stcfwAdminTranslations.open);
            jQuery("div.scpbcfw-search-field-values",this.parentNode).css("display","none");
        }
        return false;
    });
    jqTarget.find("input[type='checkbox'].scpbcfw-enable-table-view-option").change(function(e){
        var tableView=jQuery(this);
        jqTarget.find("input[type='number'].scpbcfw-search-table-width")
            .prop("disabled",!tableView.prop("checked"));
        jqTarget.find("input[type='checkbox'].scpbcfw-enable-use-backbone-option")
            .prop("disabled",!tableView.prop("checked"))
            .each(function(){
                if(!tableView.prop("checked")){
                    jQuery(this).prop("checked",false);
                }
            })
            .change();    
        jqTarget.find("input[type='checkbox'].scpbcfw-select-content-macro-display-field").prop("disabled",!jQuery(this).prop("checked"));
    });
    jqTarget.find("input[type='checkbox'].scpbcfw-enable-use-backbone-option").change(function(e){
        var useBackbone=jQuery(this);
        jqTarget.find("input[type='number'].scpbcfw-search-table-width")
            .prop("disabled",useBackbone.prop("checked"));
        jqTarget.find("input[type='checkbox'].scpbcfw-enable-use-bootstrap-option")
            .prop("disabled",!useBackbone.prop("checked"))
            .each(function(){
                if(!useBackbone.prop("checked")){
                    jQuery(this).prop("checked",false);
                }
            })
            .change();
    });
    jqTarget.find("input[type='checkbox'].scpbcfw-enable-use-bootstrap-option").change(function(e){
        var useBootstrap=jQuery(this);
        jqTarget.find("input[type='checkbox'].scpbcfw-enable-do-not-load-bootstrap-option")
            .prop("disabled",!useBootstrap.prop("checked"))
            .each(function(){
                if(!useBootstrap.prop("checked")){
                    jQuery(this).prop("checked",false);
                }
            });
    });
    // set background of div to indicate whether post type has been selected for searching or not
    jqTarget.find("div.scpbcfw-search-field-values").each(function(){
        var checked=false;
        jQuery(this).find("input.scpbcfw-selectable-field[type='checkbox']").each(function(){
            checked=checked||jQuery(this).prop("checked");
        });
        var container=jQuery(this).parents("div.scpbcfw-admin-search-fields");
        if(checked){
            container.removeClass("stcfw-nohighlight").addClass("stcfw-highlight");
        }else{
            container.removeClass("stcfw-highlight").addClass("stcfw-nohighlight");
        }
    });
    // on checkbox change reset background of div to indicate whether post type has been selected for searching or not
    jqTarget.find("input.scpbcfw-selectable-field[type='checkbox']").change(function(e){
        var checked=false;
        jQuery(this).parents("div.scpbcfw-search-field-values").find("input.scpbcfw-selectable-field[type='checkbox']").each(function(){
            checked=checked||jQuery(this).prop("checked");
        });
        var container=jQuery(this).parents("div.scpbcfw-admin-search-fields");
        if(checked){
            container.removeClass("stcfw-nohighlight").addClass("stcfw-highlight");
        }else{
            container.removeClass("stcfw-highlight").addClass("stcfw-nohighlight");
        }
    });
    // drag and drop handlers
    jqTarget.find("div.scpbcfw-selectable-field").draggable({cursor:"crosshair",revert:true});
    jqTarget.find("div.scpbcfw-selectable-field-after").droppable({accept:"div.scpbcfw-selectable-field",tolerance:"touch",
        hoverClass:"scpbcfw-hover",drop:function(e,u){
            jQuery(this.parentNode).after(u.draggable);
            var o="";
            jQuery("input.scpbcfw-selectable-field[type='checkbox']",this.parentNode.parentNode).each(function(i){
                o+=jQuery(this).val()+";";
            });
            jQuery("input.scpbcfw-selectable-field-order[type='hidden']",this.parentNode.parentNode).val(o);
    }});
    jqTarget.find("input[type='checkbox'].scpbcfw-enable-table-view-option").change();
    jqTarget.find("button#scpbcfw-build-user-templates").click(function(e){
        var form=jQuery(this).parents("form").first();
        var heading=form.find("h4.scpbcfw-admin-heading");
        var widgetId=form.find("input[name='widget-id']");
        if(!heading.length||!widgetId.length){
            window.alert("Error 161106: Search Widget is not compatible with this version of WordPress.");
            return;
        }
        if(window.confirm("Warning: This will overwrite your user_templates.php file if it exists.")){
            var params={
                action:"stcfw_build_user_templates",
                option_name:heading.data("option-name"),
                number:heading.data("number"),
                widget_id:widgetId.val(),
                nonce:heading.data("nonce"),
                mode:"file"
            };
            jQuery.post(ajaxurl,params,function(response){
                var alert=jQuery("<div />").css({
                    position:"fixed",width:"90vw",height:"80vh",left:"5vw",top:"10vh",zIndex:10000,
                    border:"2px solid #666",borderRadius:"7px",backgroundColor:"#fff",
                    overflow:"auto"
                });
                jQuery("<button>X</button>").css({float:"right",margin:"20px"}).appendTo(alert).click(function(e){
                    alert.remove();
                    e.preventDefault();
                });
                jQuery("<div />").css({clear:"both",margin:"20px 50px 10px",padding:"20px"}).text(response.message).appendTo(alert);
                params.mode="download";
                jQuery("<div />").css({clear:"both",margin:"10px 50px",padding:"20px",textAlign:"right"}).append(
                    jQuery('<a href="'+ajaxurl+'?'+jQuery.param(params)+'" download="user-templates.php">Download</a>')
                ).appendTo(alert);
                jQuery("<pre />").css({clear:"both",margin:"10px 50px 30px",padding:"20px",border:"1px solid black"}).text(response.templates).appendTo(alert);
                form.append(alert);
            });
        }
        e.preventDefault();
    });
}
jQuery(document).ready(function(){
    // run only on widgets admin page
    if(location.pathname.indexOf("/widgets.php")===-1){return;}
    jQuery("div.widget-content,div.widget-inside").has("div.scpbcfw-admin-button").each(function(){stcfwInitialize(this);});
    // handle AJAX refresh of the search form
    var container=jQuery("div.widgets-sortables");
    if ( !container.length ) {
        container = jQuery( "div.editwidget" );
    }
    // What if WordPress changes the classname of the widget container? The plugin will need to be upgraded
    if(!container.length){window.alert("Search Types Custom Fields Widget:Error - widget container not found, please report this to the developer as this plugin needs to be upgraded.");}
    var observer=new MutationObserver(function(){container.find("div.widget-content,div.widget-inside").has("div.scpbcfw-admin-button").each(function(){stcfwInitialize(this);})});
    container.each(function(){observer.observe(this,{childList:true,subtree:true});});
});
