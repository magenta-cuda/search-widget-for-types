(function(){
    var stcfw=window.stcfw=window.stcfw||{};
    // use WordPress templating syntax; see .../wp-includes/js/wp-util.js
    stcfw.templateOptions={
        evaluate:    /<#([\s\S]+?)#>/g,
        interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
        escape:      /\{\{([^\}]+?)\}\}(?!\})/g,
        variable:    'data'
    };
    stcfw.Post=Backbone.Model.extend({idAttribute:"ID"});
    stcfw.Posts=Backbone.Collection.extend({model:stcfw.Post});
    // Use a post type specific template if it exists otherwise use the generic template
    var template=jQuery("script#stcfw-template-"+stcfw.post_type+"-post_hover_view");
    if(!template.length){
        template=jQuery("script#stcfw-template-generic-post_hover_view");
    }
    stcfw.PostHoverView=Backbone.View.extend({
        className:"stcfw-generic-post_hover_view",
        // The version of _.template() used by WordPress seems to need a null argument before the settings argument. See .../wp-includes/js/wp-util.js
        template:_.template(template.html(),null,stcfw.templateOptions),
        render:function(){
            this.$el.empty();
            this.$el.html(this.template(this.model.attributes));
            return this;
        }
    });
    stcfw.posts=new stcfw.Posts();
    stcfw.postHoverView=new stcfw.PostHoverView();
    try{
        stcfw.posts.reset(JSON.parse(stcfw.collection));
        console.log("stcfw.posts=",stcfw.posts);
    }catch(e){
        console.log("e=",e);
    }
    jQuery("dl.gallery-item a[data-post_id] img,figure.gallery-item a[data-post_id] img").hover(
        function(e){
            var $this=jQuery(this);
            var position=$this.position();
            var parent=$this.offsetParent();
            var parentWidth=parent.width();
            var post=stcfw.posts.get(this.parentNode.dataset.post_id);
            console.log("post=",post.attributes);
            var view=stcfw.postHoverView;
            view.model=post;
            jQuery("div#stcfw-gallery-container").prepend(view.render().$el);
            e.preventDefault();
            e.stopPropagation();
        },
        function(){
            jQuery("."+stcfw.postHoverView.className).detach();
        }
    );
}());
