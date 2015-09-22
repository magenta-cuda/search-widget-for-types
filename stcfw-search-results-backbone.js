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
        events:{
            click:"onclick"
        },
        // The version of _.template() used by WordPress seems to need a null argument before the settings argument. See .../wp-includes/js/wp-util.js
        template:_.template(template.html(),null,stcfw.templateOptions),
        render:function(){
            this.$el.remove();
            //this.$el.html(this.template(this.model.attributes));
            var overlay=jQuery(this.template(this.model.attributes));
            overlay.css({position:"absolute",backgroundColor:"white",opacity:0.75,border:"2px solid black",padding:"10px"});
            this.$el=overlay;
            this.el=overlay[0];
            return this;
        },
        onclick:function(){
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
    jQuery("dl.gallery-item a[data-post_id] img,figure.gallery-item a[data-post_id] img").mouseenter(function(e){
        var $this=jQuery(this);
        var view=stcfw.postHoverView;
        var offset=$this.offset();
        view.targetLeft=offset.left;
        view.targetTop=offset.top;
        view.targetRight=offset.left+$this.outerWidth();
        view.targetBottom=offset.top+$this.outerHeight();
        var position=$this.position();
        var parent=$this.offsetParent();
        var parentWidth=parent.width();
        var x=11;
        var y=11;
        var post=stcfw.posts.get(this.parentNode.dataset.post_id);
        console.log("post=",post.attributes);
        view.model=post;
        var $el=view.render().$el;
        var width=$el.outerWidth();
        $el.css({left:x,top:y});
        var container=jQuery("div#stcfw-gallery-container");
        container.prepend(view.$el);
        container.on("mousemove.stcfw",function(e){
            console.log("mousemove.stcfw");
            if(e.pageX<view.targetLeft||e.pageX>=view.targetRight||e.pageY<view.targetTop||e.pageY>=view.targetBottom){
                stcfw.postHoverView.remove();
                container.off("mousemove.stcfw");
            }
        });
        var width=$el.outerWidth();
        e.preventDefault();
        e.stopPropagation();
    });
}());
