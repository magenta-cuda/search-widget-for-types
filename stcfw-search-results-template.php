<!-- Templates for stcfw-search-results-backbone.js - they will be compiled with _.template() -->

<script type="text/html" id="stcfw-template-generic-post_hover_view">
<div>
<h3><# print(stcfw.extractHrefAndLabelFromLink(data.post_title).label); #></h3>
{{{ data.post_content }}}
</div>
</script> 
