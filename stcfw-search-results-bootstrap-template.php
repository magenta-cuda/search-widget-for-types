<!-- Bootstrap Backbone Gallery Template -->
<script type="text/html" id="st_iv-bs-template_gallery">
<div class="container">
    <div class="row">
        {{{ data.items }}}
    </div>
</div>
</script>
<!-- Bootstrap Backbone Gallery Item Template -->
<script type="text/html" id="st_iv-bs-template_gallery_item">
        <div class="col-sm-6 col-md-4 col-lg-3">
            <figure class="item ems_xii-item">
                <figcaption><# print(stcfw.extractHrefAndLabelFromLink(data.post_title).label); #></figcaption>
                <a href="<# print(stcfw.extractHrefAndLabelFromLink(data.post_title).href); #>" target="blank">
                    <img src="<# print(stcfw.extractHrefAndLabelFromLink(data.thumbnail).href); #>">
                </a>
            </figure>
        </div>
</script>
