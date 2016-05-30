<!-- Templates for stcfw-search-results-backbone.js - they will be compiled with _.template().    -->

<!-- Templates for "backbone" mode have two parts a container part and an item part.              -->
<!-- The id for a "container" is "stcfw-template-container" . $post_type . $view_name.            -->
<!-- The id for a "item" is "stcfw-template-item" . $post_type . $view_name.                      -->
<!-- The "generic" template is used if no post type specific template is found.                   -->
<!-- The fields in the Backbone model are the same fields as those displayed in the table format. -->
<!-- You can use the stcfw-template-...-generic-debug_view template to get a dump of all fields.  -->
<!-- The order of the templates here is also the order of the options in the select element.      -->
<!-- Please use WordPress templating syntax -- see stcfw.templateOptions from                     -->
<!-- stcfw-search-results-backbone.js which is copied from .../wp-includes/js/wp-util.js.         -->
<!-- You can also specify styles using the css file search-results-backbone.css.                  -->
<!-- You can also specify a function to run after a template is rendered.                         -->
<!-- The function should be defined as a property of window.stcfwTemplateFunctions with name like -->
<!-- "stcfw-template-function-" . $post_type . $view_name.                                        -->

<!-- To define templates styled by the Twitter Bootstrap stylesheet use the file                  -->
<!-- .../stcfw-search-results-bootstrap-template.php                                              -->

<!-- This is a starter table template.                                                            -->
<!-- You should create a post type specific template and add specific fields for that post type.  -->

<script type="text/javascript">
window.stcfwTemplateFunctions=window.stcfwTemplateFunctions||{};
</script>

<script type="text/html" id="stcfw-template-container-generic-table_view">
<table class="st_iv-table_generic">
<thead>
<tr>
<th>Post</th>
<th>Excerpt</th>
</tr>
</thead>
<tbody class="stcfw-results-item-container stcfw-template-container-generic-table_view">
</tbody>
</table>
</script> 

<script type="text/html" id="stcfw-template-item-generic-table_view">
<tr>
<td>{{{ data.post_title }}}</td>
<td>{{{ data.post_content }}}</td>
</tr>
</script> 

<script type="text/html" id="stcfw-template-container-generic-list_view">
<ol class="stcfw-results-item-container stcfw-template-container-generic-list_view st_iv-list_generic"></ol>
</script>

<script type="text/html" id="stcfw-template-item-generic-list_view">
<li><strong>{{{ data.post_title }}}</strong><br>&nbsp;&nbsp;&nbsp;&nbsp;{{{ data.post_content }}}</li>
</script>

<!-- This is a very simple gallery template.                                                      -->
<!-- Note the use of stcfw.extractHrefAndLabelFromLink() to extract the href from an <a> element. -->
<!-- This template only works if the posts have featured images.                                  -->

<script type="text/html" id="stcfw-template-container-generic-gallery_view">
<div class="stcfw-results-item-container stcfw-template-container-generic-gallery_view st_iv-responsive_container"></div>
</script>

<script type="text/html" id="stcfw-template-item-generic-gallery_view">
<a class="st_iv-responsive_item" href="<# print(stcfw.extractHrefAndLabelFromLink(data.post_title).href); #>">
    <img src="<# print(stcfw.extractHrefAndLabelFromLink(data.thumbnail).href); #>"
        alt="<# print(stcfw.extractHrefAndLabelFromLink(data.post_title).label); #>"
        title="<# print(stcfw.extractHrefAndLabelFromLink(data.post_title).label); #>"
        data-id="{{{ data.ID }}}">
</a>
</script>

<script type="text/javascript">
// This is an example of a template function. Note that since the function name has hyphens you must use the bracket form for property names.
// The argument of the function is the $el property of the Backbone.js view, i.e., the jQuery wrapper of the rendered element

window.stcfwTemplateFunctions["stcfw-template-function-generic-gallery_view"]=function($el){
    $el.find("img").mouseenter(window.stcfw.mouseEnterItemHandler);
};
</script>

<!-- You can use the stcfw-template-...-generic-debug_view template to get a dump of all fields.  -->
<!-- Remove or comment out this template for production mode.                                     -->

<script type="text/html" id="stcfw-template-container-generic-debug_view">
<div>
<div style="background-color:#e0e0e0;border-radius:7px;padding:15px;margin-bottom:15px;">
This is a dump of all fields in the selected posts. The field names are exactly as you would use them in Underscore.js templates.
The fields are essentially the same fields as those selected for the table format.
Note that links are embedded in HTML &lt;a&gt; elements. 
You can use stcfw.extractHrefAndLabelFromLink() to extract the href and label from the link.
Also post_content is really the post excerpt.
Edit &quot;stcfw-search-results-template.php&quot; to change these templates or add your own templates.
The documentation of Underscore.js templates is <a href="http://underscorejs.org/#template" target="_blank">here</a>.
However, we actually use the WordPress' version of the template settings.
</div>
<div style="overflow-x:auto;">
<table id="stcfw-dump-table" style="border-collapse:collapse;width:2048px;">
<thead>
<tr><# print(stcfw.dumpFieldNames()); #></tr>
</thead>
<tbody class="stcfw-results-item-container">
</tbody>
</table>
</div>
</script> 

<script type="text/html" id="stcfw-template-item-generic-debug_view">
<tr><# print(stcfw.dumpFieldValues(data.ID)); #></tr>
</div>
</script> 

<!-- ############################################################################################ -->

<!-- The following template is used by the gallery format in the old "classic" mode.              -->
<!-- It is also used in the new "backbone" mode as the mouse-over popup of the gallery format.    -->
<!-- The id for gallery mouseover popups is "stcfw-template-" . $post_type . "-post_hover_view".  -->
<!-- The "generic" template is used if no post type specific template is found.                   -->
<!-- The fields in the Backbone model are the same fields as those displayed in the table format. -->

<script type="text/html" id="stcfw-template-generic-post_hover_view">
<div class="st_iv-popup">
<h3><# print(stcfw.extractHrefAndLabelFromLink(data.post_title).label); #></h3>
<span style="font-size:x-small;">{{{ data.post_content }}}</span>
</div>
</script> 


