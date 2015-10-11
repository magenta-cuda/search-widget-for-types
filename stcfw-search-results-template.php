<!-- Templates for stcfw-search-results-backbone.js - they will be compiled with _.template().    -->

<!-- The id for gallery mouseover popups is "stcfw-template-" . $post_type . "-post_hover_view".  -->
<!-- The "generic" template is used if no post type specific template is found.                   -->
<!-- The fields in the Backbone model are the same fields as those displayed in the table format. -->

<script type="text/html" id="stcfw-template-generic-post_hover_view">
<div style="background-color:white;opacity:0.90;border:2px solid black;padding:10px;">
<h3><# print(stcfw.extractHrefAndLabelFromLink(data.post_title).label); #></h3>
<span style="font-size:x-small;">{{{ data.post_content }}}</span>
</div>
</script> 

<!-- Templates for "backbone" mode have two parts a container part and an item part.              -->
<!-- The id for a "container" is "stcfw-template-container" . $post_type . $view_name.            -->
<!-- The id for a "item" is "stcfw-template-item" . $post_type . $view_name.                      -->
<!-- The "generic" template is used if no post type specific template is found.                   -->
<!-- The fields in the Backbone model are the same fields as those displayed in the table format. -->
<!-- You can use the stcfw-template-...-generic-debug_view template to get a dump of all fields.  -->
<!-- The order of the templates here is also the order of the options in the select element.      -->

<script type="text/html" id="stcfw-template-container-generic-table_view">
<table id="stcfw-table" style="border-collapse:collapse;">
<thead>
<tr>
<th style="border:2px solid black;padding:10px;">Post</th>
<th style="border:2px solid black;padding:10px;">Excerpt</th>
</tr>
</thead>
<tbody class="stcfw-results-item-container">
</tbody>
</table>
</script> 

<script type="text/html" id="stcfw-template-item-generic-table_view">
<tr>
<td style="border:2px solid black;padding:10px;">{{{ data.post_title }}}</td>
<td style="border:2px solid black;padding:10px;">{{{ data.post_content }}}</td>
</tr>
</script> 

<script type="text/html" id="stcfw-template-container-generic-list_view">
<ul class="stcfw-results-item-container"></ul>
</script>

<script type="text/html" id="stcfw-template-item-generic-list_view">
<li>{{{ data.post_title }}}<br>&nbsp;&nbsp;&nbsp;&nbsp;{{{ data.post_content }}}</li>
</script>

<script type="text/html" id="stcfw-template-container-generic-gallery_view">
<div class="stcfw-results-item-container"></div>
</script>

<script type="text/html" id="stcfw-template-item-generic-gallery_view">
<img src="<# print(stcfw.extractHrefAndLabelFromLink(data.thumbnail).href); #>" width="160">
</script>

<!-- You can use the stcfw-template-...-generic-debug_view template to get a dump of all fields.  -->
<!-- Remove or comment out this template for production mode.                                     -->

<script type="text/html" id="stcfw-template-container-generic-debug_view">
<div>
<h2>This is a dump of all fields in the selected posts. The field names are exactly as you would use them in Underscore.js templates. Note that links are embedded in HTML &lt;a&gt; elements. post_content is really post excerpt.</h2>
<table id="stcfw-table" style="border-collapse:collapse;">
<thead>
<tr><# print(stcfw.dumpFieldNames()); #></tr>
</thead>
<tbody class="stcfw-results-item-container">
</tbody>
</table>
</script> 

<script type="text/html" id="stcfw-template-item-generic-debug_view">
<tr><# print(stcfw.dumpFieldValues(data.ID)); #></tr>
</div>
</script> 


