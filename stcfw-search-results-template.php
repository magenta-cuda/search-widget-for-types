<!-- Templates for stcfw-search-results-backbone.js - they will be compiled with _.template().    -->

<!-- The id for gallery mouseover popups is "stcfw-template-" . $post_type . "-post_hover_view".  -->
<!-- The "generic" template is used if no post type specific template is found.                   -->
<!-- The fields in the Backbone model are the same fields as those displayed in the table format. -->

<script type="text/html" id="stcfw-template-generic-post_hover_view">
<div>
<h3><# print(stcfw.extractHrefAndLabelFromLink(data.post_title).label); #></h3>
{{{ data.post_content }}}
</div>
</script> 
