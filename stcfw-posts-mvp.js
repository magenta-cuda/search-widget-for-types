/*
 * a model view presenter for the selected posts using Backbone.js
 */
 
stcfw={};

// use WordPress templating syntax; see ../wp-includes/js/wp-util.js

stcfw.templateOptions={
    evaluate:    /<#([\s\S]+?)#>/g,
    interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
    escape:      /\{\{([^\}]+?)\}\}(?!\})/g,
    variable:    'data'
};

/* The HTML for a view of posts of post type "postType" and format "format" must have a outer HTML <div> element with
 * id "stcfw-view-{{postType}}-{{format}}" and class "stcfw-view" where "format" is the name you have chosen to identify
 * this HTML view of the posts. This outer HTML element must have an inner container HTML element with class
 * "stcfw-item-container". E.g.
 *
 * <div class="stcfw-view" id="stcfw-view-example-list" style="display:none;">
 *     <h1>List of Posts</h1>
 *     <ul class="stcfw-item-container"></ul>
 * </div>
 *
 * The posts will be rendered as children of the inner container HTML element with class "stcfw-item-container" using an 
 * Underscore.js template. This template must be given as the source of a HTML script element of type "text/html" and with
 * id "stcfw-template-{{postType}}-{{format}}. E.g.
 *
 * <script type="text/html" id="stcfw-template-example-list">
 * <li><a href="{{ data.guid }}">{{ data.post_title }}</a></li>
 * </script>
 *
 * The field names of the post are either a column name from the MySQL table wp_posts or a Types custom field slug. The 
 * field names must have the prefix "data." and have been selected for display in the administrator's interface.
 *
 * If a view requires two renderings (e.g. jQuery UI Tabs which requires that the titles and the contents be rendered
 * separately) the additional view and template must have ids with prefix "stcfw-view2-" and "stcfw-template2-" respectively.
 */

stcfw.Post=Backbone.Model.extend();
stcfw.Posts=Backbone.Collection.extend({model:stcfw.Post});
stcfw.View=Backbone.View.extend({
    initialize:function(){
        this.listenTo(this.collection,"reset",this.render);
        // there are two views - the main view and possibly an additional view; the additional view must be disabled except when needed 
        this.enabled=false;
    },
    render:function(){
        if(!this.enabled){
            // this is the disabled additional view
            return;
        }
        this.$el.empty();
        var view=this;
        this.collection.each(function(post){
            console.log("post=",post.attributes);
            view.$el.append(view.template(post.attributes));
        });
    },
    changeView:function(el,template){
        // el is an HTML container element (e.g. <ul>, <tbody>, <div>, ...) where the posts will be rendered as children using the template
        // template is an Underscore.js template string that will be compiled with _.template()
        if(typeof el==="string"){
            this.$el=jQuery(el);
            this.el=this.$el[0];
        }else if(el instanceof jQuery){
            this.$el=el;
            this.el=el[0];
        }else if(el instanceof HTMLElement){
            this.el=el;
            this.$el=jQuery(el);
        }
        this.template=_.template(template,stcfw.templateOptions);
        this.enabled=true;
    }
});

jQuery(document).ready(function(){
    stcfw.posts=new stcfw.Posts();
    stcfw.view=new stcfw.View({collection:stcfw.posts});
    // some views may require an additional rendering of the data, e.g. jQuery UI Tabs - titles and contents need to be rendered separately
    stcfw.view2=new stcfw.View({collection:stcfw.posts});
    var select=jQuery("select#stcfw-select-view");
    var views=jQuery("div.stcfw-view");
    var templates=jQuery("script[type='text/html'][id^='stcfw-template']");
    // selectOptions will hold arrays of views by post type
    var selectOptions={};
    // find views for post types
    views.each(function(){
        var id=this.id;
        if(id.substr(0,11)!=="stcfw-view-"){
            return;
        }
        id=id.substr(11);
        if(templates.filter("#stcfw-template-"+id).length!==1){
            // this view has no matching template so ignore it
            return;
        }
        var typeFormat=id.split("-");
        var type=typeFormat[0];
        var format=typeFormat[1];
        // organize views into arrays by post type
        if(!selectOptions.hasOwnProperty(type)){
            selectOptions[type]=[];
        }
        selectOptions[type].push(format);
    });
    console.log("selectOptions=",selectOptions);
    stcfw.reload=function(format,postType,data){
        views.css("display","none");
        if(typeof postType!=="undefined"){
            stcfw.posts.postType=postType;
            // construct the views that can be selected for this post type
            select.empty();
            selectOptions[postType].forEach(function(option){
                select.append("<option value=\""+option+"\">"+option+"</option>");
            });
        }else{
            postType=stcfw.posts.postType;
        }
        if(typeof format==="undefined"){
            format=select.val();
        }
        // the HTML element of the view has id "stcfw-view-{{postType}}-{{format}}"
        // the posts are rendered as children of the child element of the HTML element of the view with class "stcfw-item-container"
        var view=views.filter("#stcfw-view-"+postType+"-"+format);
        stcfw.view.changeView(view.find(".stcfw-item-container"),jQuery("script#stcfw-template-"+postType+"-"+format).html());
        // the HTML element of the additional view has id "stcfw-view2-{{postType}}-{{format}}"
        var view2=views.filter("#stcfw-view2-"+postType+"-"+format);
        if(view2.length){
            // the additional view exists for this postType and format 
            stcfw.view2.changeView(view2.find(".stcfw-item-container"),jQuery("script#stcfw-template2-"+postType+"-"+format).html());
        }else{
            // disable the additional view
            stcfw.view2.enabled=false;
        }
        if(typeof data!=="undefined"){
            stcfw.posts.reset(data);
        }else{
            stcfw.view.render();
            if(view2.length){
                stcfw.view2.render();
            }
        }
        view.css("display","block");
        if(view2.length){
            view2.css("display","block");
        }
    }
    select.change(function(){
        stcfw.reload();
    });
});