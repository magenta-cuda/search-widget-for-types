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
            overlay.css("position","absolute");
            this.$el=overlay;
            this.el=overlay[0];
            this.delegateEvents();
            return this;
        },
        onclick:function(){
            // propagate click to target element
            this.target.click();
        }
    });
    // URL values of post fields are HTML <a> elements, e.g. '<a href="http://alpha.beta.com/delta.jpg">Gamma</a>'
    // extractHrefAndLabelFromLink() returns an object with properties href and label 
    // The main application of extractHrefAndLabelFromLink() is in evaluate expressions in templates,
    // e.g. '<# print(extractHrefAndLabelFromLink(data.alpha).label); #>'
    stcfw.extractHrefAndLabelFromLink=function(link){
        var ret={};
        var matches=link.match(/^<a\s.*?("|')(.*?)\1.*?>(.*?)<\/a>$/i);
        ret.href=matches[2];
        ret.label=matches[3];
        return ret;
    }
    stcfw.posts=new stcfw.Posts();
    stcfw.postHoverView=new stcfw.PostHoverView();
    try{
        stcfw.posts.reset(JSON.parse(stcfw.collection));
    }catch(e){
        console.log("e=",e);
    }
    // show overlay when mouse is over the target image element
    jQuery("dl.gallery-item a[data-post_id] img,figure.gallery-item a[data-post_id] img").mouseenter(function(e){
        var $this=jQuery(this);
        var view=stcfw.postHoverView;
        view.target=$this;
        // save target geometry for mousemove handler
        var offset=$this.offset();
        view.targetLeft=offset.left;
        view.targetTop=offset.top;
        view.targetRight=offset.left+$this.outerWidth();
        view.targetBottom=offset.top+$this.outerHeight();
        // get the center of the target element to use to center the overlay
        var position=$this.position();
        var x=position.left+$this.outerWidth()/2;
        var y=position.top+$this.outerHeight()/2;
        // render the post of the target element
        view.model=stcfw.posts.get(this.parentNode.dataset.post_id);
        var $el=view.render().$el;
        var container=jQuery("div#stcfw-gallery-container").prepend($el);
        // track mouse moves to find out when mouse moves outside of target element
        container.on("mousemove.stcfw",function(e){
            if(e.pageX<view.targetLeft||e.pageX>=view.targetRight||e.pageY<view.targetTop||e.pageY>=view.targetBottom){
                // moved outside of target element so hide overlay and stop tracking mouse moves
                stcfw.postHoverView.remove();
                container.off("mousemove.stcfw");
            }
        });
        // center over target element if possible
        x-=$el.outerWidth()/2;
        x=x>=0?x:0;
        y-=$el.outerHeight()/2;
        y=y>=0?y:0;
        $el.css({left:x,top:y});
        e.preventDefault();
        e.stopPropagation();
    });
}());
