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
