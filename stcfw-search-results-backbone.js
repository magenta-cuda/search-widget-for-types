(function(){
    var stcfw=window.stcfw=window.stcfw||{};
    stcfw.templateOptions={
        evaluate:    /<#([\s\S]+?)#>/g,
        interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
        escape:      /\{\{([^\}]+?)\}\}(?!\})/g,
        variable:    'data'
    };
    stcfw.Post=Backbone.Model.extend({idAttribute:"ID"});
    stcfw.Posts=Backbone.Collection.extend({model:stcfw.Post});
    stcfw.PostHoverView=Backbone.View.extend({
        className:"stcfw-generic-post_hover_view",
        // The version of _.template() used by WordPress seems to need a null argument before the settings argument. See .../wp-includes/js/wp-util.js
        template:_.template(jQuery("script#stcfw-template-generic-post_hover_view").html(),null,stcfw.templateOptions),
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
            var post=stcfw.posts.get(this.parentNode.dataset.post_id);
            console.log("post=",post.attributes);
            stcfw.postHoverView.model=post;
            jQuery("div.gallery").prepend(stcfw.postHoverView.render().$el);
            e.preventDefault();
            e.stopPropagation();
        },
        function(){
            jQuery("."+stcfw.postHoverView.className).detach();
        }
    );
}());
