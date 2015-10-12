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
    stcfw.posts=new stcfw.Posts();
    try{
        stcfw.posts.reset(JSON.parse(stcfw.collection));
    }catch(e){
        console.log("e=",e);
    }
    if(stcfw.mode==='classic'){
        // Use a post type specific template if it exists otherwise use the generic template
        var hoverTemplate=jQuery("script#stcfw-template-"+stcfw.post_type+"-post_hover_view");
        if(!hoverTemplate.length){
            hoverTemplate=jQuery("script#stcfw-template-generic-post_hover_view");
        }
        stcfw.PostHoverView=Backbone.View.extend({
            events:{
                click:"onclick"
            },
            // The version of _.template() used by WordPress seems to need a null argument before the settings argument. See .../wp-includes/js/wp-util.js
            template:_.template(hoverTemplate.html(),null,stcfw.templateOptions),
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
        stcfw.postHoverView=new stcfw.PostHoverView();
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
    }else if(stcfw.mode==='backbone'){
        stcfw.createView=function(containerTemplate,itemTemplate){
            if(!containerTemplate instanceof jQuery || !itemTemplate instanceof jQuery || !containerTemplate.length || !itemTemplate.length){
                return null;
            }
            return Backbone.View.extend({
                // The version of _.template() used by WordPress seems to need a null argument before the settings argument. See .../wp-includes/js/wp-util.js
                template:_.template(containerTemplate.html(),null,stcfw.templateOptions),
                container:".stcfw-results-item-container",
                ItemView:Backbone.View.extend({
                    template:_.template(itemTemplate.html(),null,stcfw.templateOptions),
                    render:function(){
                        this.setElement(jQuery(this.template(this.model.attributes)));
                        return this;
                    }
                }),
                render:function(){
                    this.$el.html(this.template({}));
                    this.$container=this.$el.find(this.container);
                    var itemView=new this.ItemView();
                    this.collection.each(function(item){
                        console.log("post=",item.attributes);
                        itemView.model=item;
                        this.$container.append(itemView.render().$el);
                        // unbind rendered element of view from view
                        itemView.setElement(document.createElement("div"));
                    },this);
                    return this;
                }
            });
        };
        stcfw.findTemplates=function(postType){
            var postTypeLen=postType.length;
            var templates={};
            // Use a post type specific template if it exists otherwise use the generic template
            jQuery("script[id^='stcfw-template-container-"+postType+"-']").each(function(){
                templates[this.id.substr(26+postTypeLen)]={container:jQuery(this)};
            });
            jQuery("script[id^='stcfw-template-container-generic-']").each(function(){
                if(!templates.hasOwnProperty(this.id.substr(33))){
                    templates[this.id.substr(33)]={container:jQuery(this),generic:true};
                }
            });
            jQuery("script[id^='stcfw-template-item-"+postType+"-']").each(function(){
                var template=templates[this.id.substr(21+postTypeLen)];
                if(!template.generic){
                    template.item=jQuery(this);
                }
            });
            jQuery("script[id^='stcfw-template-item-generic-']").each(function(){
                var template=templates[this.id.substr(28)];
                if(template.generic){
                    template.item=jQuery(this);
                }
            });
            return templates;
        };
        // create Backbone views for each template found and add option for that view to select element
        stcfw.Views={};
        var templates=stcfw.findTemplates(stcfw.post_type);
        var select=jQuery("select#stcfw-select-views");
        var selected=false;
        Object.keys(templates).forEach(function(key){
            var template=templates[key];
            if(template.hasOwnProperty("container")&&template.hasOwnProperty("item")){
                var View=stcfw.createView(template.container,template.item);
                if(View){
                    stcfw.Views[key]=View;
                    var option=jQuery("<option></option>");
                    option.val(key);
                    option.text(key.replace(/_|-/," "));
                    if(!selected){
                        // select first template found as default
                        option.attr("selected",true);
                        option.prop("selected",true);
                        selected=true;
                    }
                    select.append(option);
                }
            }
        });
        stcfw.views={};
        stcfw.doSelectedView=function(){
            var previousSelection=stcfw.currentSelection;
            stcfw.currentSelection=select.find("option:selected").val();
            // if the selected view hasn't changed skip to render step 
            if(stcfw.currentSelection!==previousSelection){
                // find previuosly cached view if it exists
                var view=stcfw.views[stcfw.currentSelection];
                if(!view){
                    // nothing in cache so create a new view
                    view=stcfw.views[stcfw.currentSelection]=new stcfw.Views[stcfw.currentSelection]({collection:stcfw.posts});
                }
            }
            // render the selected view
            var div=jQuery("div#stcfw-view");
            if(div.length){
                div.empty();
                div.append(view.render().$el);
            };
        };
        select.change(stcfw.doSelectedView);
        stcfw.doSelectedView();
        // debugging utilities
        // dumpFieldNames() dumps field names as <th> elements for use in <tr> element of the debug_view template
        var fieldNames=[];
        stcfw.dumpFieldNames=function(){
            stcfw.posts.forEach(function(model){
                Object.keys(model.attributes).forEach(function(key){
                    if(fieldNames.indexOf(key)===-1){
                        fieldNames.push(key);
                    }
                });
            });
            var buffer="";
            fieldNames.forEach(function(name){
                buffer+="<th style=\"border:2px solid black;padding:10px;\">"+name+"</th>";
            });
            return buffer;
        };
        // dumpFieldValues() dumps field values as <td> elements for use in <tr> element of the debug_view template
        stcfw.dumpFieldValues=function(id){
            var model=stcfw.posts.get(id);
            var buffer="";
            fieldNames.forEach(function(name){
                buffer+="<td style=\"border:2px solid black;padding:10px;\">"+model.attributes[name]+"</td>";
            });
            return buffer;        
        };
    }
    // URL values of post fields are HTML <a> elements, e.g. '<a href="http://alpha.beta.com/delta.jpg">Gamma</a>'
    // extractHrefAndLabelFromLink() returns an object with properties href and label 
    // The main application of extractHrefAndLabelFromLink() is in evaluate expressions in templates,
    // e.g. '<# print(extractHrefAndLabelFromLink(data.alpha).label); #>'
    stcfw.extractHrefAndLabelFromLink=function(link){
        var ret={};
        if(!link){
            ret.label=ret.href="";
            return ret;
        }
        var matches=link.match(/^<a\s.*?("|')(.*?)\1.*?>(.*?)<\/a>$/i);
        if(matches){
            ret.href=matches[2];
            ret.label=matches[3];
        }else{
            ret.label=ret.href="";
        }
        return ret;
    };
}());
