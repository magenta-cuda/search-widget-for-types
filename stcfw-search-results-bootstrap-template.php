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
            <figure class="st_iv-gallery_item">
                <figcaption><# print(stcfw.extractHrefAndLabelFromLink(data.post_title).label); #></figcaption>
                <a href="<# print(stcfw.extractHrefAndLabelFromLink(data.post_title).href); #>" target="blank">
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
  <figcaption><# print(stcfw.extractHrefAndLabelFromLink(data.post_title).label); #></figcaption>
  <a href="<# print(stcfw.extractHrefAndLabelFromLink(data.post_title).href); #>" target="blank">
    <img src="<# print(stcfw.extractHrefAndLabelFromLink(data.thumbnail).href); #>">
  </a>
</figure>
</script>
<!-- Bootstrap Tabs Backbone Container Template -->
<script type="text/html" id="st_iv-bs-template_tabs">
<div class="st_iv-bs-template_tabs_container">
  <!-- Tabs -->
  <nav role="navigation" class="navbar navbar-default">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <button type="button" data-target="#tabbarCollapse" data-toggle="collapse" class="navbar-toggle">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <!-- <a href="#" class="navbar-brand">Brand</a> -->
    </div>
    <!-- Collection of nav links and other content for toggling -->
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
    <img src="<# print(stcfw.extractHrefAndLabelFromLink(data.thumbnail).href); #>">
  </a>
</figure>
</script>
<!-- Bootstrap Table Backbone Container Template -->
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
