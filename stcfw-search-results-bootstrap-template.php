<!--
These Backbone.js templates should be instantiated in a Twitter Bootstrap environment i.e. where bootstrap.css and bootstrap.js are loaded.
This is done by instantiating these templates in .../js/stcfw-search-results-backbone-bootstrap.js which is always loaded in a Bootstrap environment.
-->

<!-- Bootstrap Backbone Gallery Template -->
<script type="text/html" id="st_iv-bs-template_gallery">
<div class="container st_iv-gallery">
    <div class="row">
        {{{ data.items }}}
    </div>
</div>
</script>
<!-- Bootstrap Backbone Gallery Item Template -->
<script type="text/html" id="st_iv-bs-template_gallery_item">
        <div class="col-sm-6 col-md-4 col-lg-3">
            <figure class="img-rounded st_iv-gallery_item">
                <a href="<# print(stcfw.extractHrefAndLabelFromLink(data.post_title).href); #>" target="_blank">
                    <figcaption><# print(stcfw.extractHrefAndLabelFromLink(data.post_title).label); #></figcaption>
                </a>
                <a href="<# print(stcfw.extractHrefAndLabelFromLink(data.post_title).href); #>" target="_blank">
                    <img src="<# print(stcfw.extractHrefAndLabelFromLink(data.thumbnail).href); #>">
                </a>
            </figure>
        </div>
</script>
<!-- Bootstrap Backbone Carousel Template -->
<script type="text/html" id="st_iv-bs-template_carousel">
<div id="{{{ data.id }}}" class="carousel slide" data-ride="carousel">
  <button type="button" class="st_iv-bs-carousel_close_btn btn btn-default"><span class="glyphicon glyphicon-remove"></span></button>
  <!-- Indicators -->
  <ol class="carousel-indicators">
    {{{ data.bullets }}}
  </ol>
  <!-- Wrapper for slides -->
  <div class="carousel-inner" role="listbox">
    {{{ data.items }}}
  </div>
  <!-- Left and right controls -->
  <a class="left carousel-control" href="#{{{ data.id }}}" role="button" data-slide="prev">
    <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
    <span class="sr-only">Previous</span>
    <span class="glyphicon glyphicon-pause st_iv-pause_play" aria-hidden="true"></span>
    <span class="sr-only">Pause</span>
  </a>
  <a class="right carousel-control" href="#{{{ data.id }}}" role="button" data-slide="next">
    <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
    <span class="sr-only">Next</span>
  </a>
</div>
</script>
<!-- Bootstrap Backbone Carousel Item Template -->
<script type="text/html" id="st_iv-bs-template_carousel_item">
<figure class="item ems_xii-item<# if ( data.index === 0 ) { #> active<# } #>">
  <a href="<# print(stcfw.extractHrefAndLabelFromLink(data.post_title).href); #>" target="blank">
    <img src="<# print(stcfw.extractHrefAndLabelFromLink(data.thumbnail).href); #>">
  </a>
  <figcaption><# print(stcfw.extractHrefAndLabelFromLink(data.post_title).label); #></figcaption>
</figure>
</script>
<!-- Bootstrap Tabs Backbone Container Template -->
<script type="text/html" id="st_iv-bs-template_tabs">
<div class="st_iv-bs-template_tabs_container">
  <!-- Tabs -->
  <nav role="navigation" class="navbar navbar-default">
    <div class="navbar-header">
      <div class="st_iv-navbar_label">Tabs Menu</div>
      <button type="button" data-target="#tabbarCollapse" data-toggle="collapse" class="navbar-toggle">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
    </div>
    <div id="tabbarCollapse" class="collapse navbar-collapse">
      <ul class="nav navbar-nav nav-tabs" role="tablist">
        {{{ data.tabs }}}
      </ul>
    </div>
  </nav>
  <!-- Panes -->
  <div class="tab-content">
    {{{ data.items }}}
  </div>
</div>
</script>
<!-- Bootstrap Tabs Backbone Tabs Template -->
<script type="text/html" id="st_iv-bs-template_tabs_tab">
<li role="presentation"<# if ( data.index === 0 ) { #> class=" active"<# } #>>
  <a href="#st_iv-tab_pane{{{ data.index }}}" aria-controls="st_iv-tab_pane{{{ data.index }}}" role="tab" data-toggle="tab">
    <# print(stcfw.extractHrefAndLabelFromLink(data.post_title).label); #>
  </a>
</li>
</script>
<!-- Bootstrap Tabs Backbone Item Template -->
<script type="text/html" id="st_iv-bs-template_tabs_item">
<figure id="st_iv-tab_pane{{{ data.index }}}" role="tabpanel" class="tab-pane<# if ( data.index === 0 ) { #> active<# } #>">
  <a href="<# print(stcfw.extractHrefAndLabelFromLink(data.post_title).href); #>" target="blank">
    <img class="img-rounded" src="<# print(stcfw.extractHrefAndLabelFromLink(data.thumbnail).href); #>">
  </a>
  <figcaption>{{{ data.post_content }}}</figcaption>
</figure>
</script>
<!-- Bootstrap Table Backbone Container Template -->
<!-- These are just a starter templates - you can use them to create post type specific templates with post type specific fields. --> 
<!-- Your templates should have ids like "st_iv-bs-template_table-{$post_type}" and "st_iv-bs-template_table_item-{$post_type"}.  -->
<!-- See the function stcfw.getTemplate() in ../js/stcfw-search-results-backbone-bootstrap.js.                                    -->
<!-- You can get the field names using the "debug_view" which is available when no bootstrap mode is selected.                    -->
<script type="text/html" id="st_iv-bs-template_table">
<table class="table">
  <thead>
    <tr>
      <th>Post</th>
      <th>Excerpt</th>
    </tr>
  </thead>
  <tbody>
    {{{ data.items }}}
  </tbody>
</table>
</script>
<!-- Bootstrap Table Backbone Item Template -->
<script type="text/html" id="st_iv-bs-template_table_item">
    <tr>
      <td>{{{ data.post_title }}}</td>
      <td>{{{ data.post_content }}}</td>
    </tr>
</script>
