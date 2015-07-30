function stcfwInitialize(target){
    jQuery(target).find("div.scpbcfw-admin-display-button").click(function(event){
        if(jQuery(this).text()=="Open"){
            jQuery(this).text("Close");
            jQuery("div.scpbcfw-search-field-values",this.parentNode).css("display","block");
        }else{
            jQuery(this).text("Open");
            jQuery("div.scpbcfw-search-field-values",this.parentNode).css("display","none");
        }
        return false;
    });
    jQuery(target).find("input[type='checkbox'].scpbcfw-enable-table-view-option").change(function(event){
        jQuery("input[type='number'].scpbcfw-search-table-width").prop("disabled",!jQuery(this)
            .prop("checked"));
        jQuery("input[type='checkbox'].scpbcfw-select-content-macro-display-field").prop("disabled",!jQuery(this).prop("checked"));
    });
    // set background of div to indicate whether post type has been selected for searching or not
    jQuery(target).find("div.scpbcfw-search-field-values").each(function(){
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
    jQuery(target).find("input.scpbcfw-selectable-field[type='checkbox']").change(function(event){
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
    jQuery(target).find("div.scpbcfw-selectable-field").draggable({cursor:"crosshair",revert:true});
    jQuery(target).find("div.scpbcfw-selectable-field-after").droppable({accept:"div.scpbcfw-selectable-field",tolerance:"touch",
        hoverClass:"scpbcfw-hover",drop:function(e,u){
            jQuery(this.parentNode).after(u.draggable);
            var o="";
            jQuery("input.scpbcfw-selectable-field[type='checkbox']",this.parentNode.parentNode).each(function(i){
                o+=jQuery(this).val()+";";
            });
            jQuery("input.scpbcfw-selectable-field-order[type='hidden']",this.parentNode.parentNode).val(o);
    }});
}
jQuery(document).ready(function(){
    stcfwInitialize(jQuery("body"));
    // handle AJAX refresh of the search form
    var observer=new MutationObserver(function(m){m.forEach(function(n){if(jQuery(n.target).hasClass("widget-content")){stcfwInitialize(n.target);}});});
    var searchWidget=jQuery("div.scpbcfw-admin-button");
    if(searchWidget.size()){observer.observe(searchWidget.parents("div.widget")[0].parentNode,{childList:true,subtree:true});}
});
