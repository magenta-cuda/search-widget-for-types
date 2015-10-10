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
