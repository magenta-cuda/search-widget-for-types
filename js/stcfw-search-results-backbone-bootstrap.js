jQuery( document ).ready( function( ) {
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
    
    stcfw.ModelView = Backbone.View.extend( {
        render: function( srcOnly ) {
            var html = this.template( this.model.attributes );
            console.log( "html=", html );
            if ( srcOnly ) {
                return html;
            }
            this.$el.html( html );
            return this;
        }    
    } );
    
    stcfw.ContainerView = Backbone.View.extend( {
        render: function( ) {
            var html = this.template( this.model.attributes );
            console.log( "html=", html );
            this.$el.html( html );
            return this;
        }
    } );
    
    stcfw.renderGallery = function( container, collection ) {
        var modelView = new stcfw.ModelView( );
        // attach template to imageView not ImageView.prototype since template is specific to imageView
        try {
            modelView.template = _.template( jQuery( "script#st_iv-bs-template_gallery_item" ).html( ), null, stcfw.templateOptions );
        } catch ( e ) {
            console.log( "e=", e );
        }
        var modelsHtml  = "";
        collection.forEach( function( model, index ) {
            modelView.model = model;
            modelsHtml += modelView.render( true );
            if ( index % 4 === 3 ) {
                modelsHtml += '<div class="clearfix visible-lg-block"></div>';
            }
            if ( index % 3 === 2 ) {
                modelsHtml += '<div class="clearfix visible-md-block"></div>';
            }
            if ( index % 2 === 1 ) {
                modelsHtml += '<div class="clearfix visible-sm-block"></div>';
            }
        } );
        var containerView = new stcfw.ContainerView( {
            model: {
                attributes: {
                    items: modelsHtml
                }
            }
        } );
        containerView.template = _.template( jQuery( "script#st_iv-bs-template_gallery" ).html( ), null, stcfw.templateOptions );
        container.empty( );
        container.append( containerView.render( ).$el.find( "div.container" ) );
    }
    
    stcfw.renderCarousel = function( container, collection, id ) {
        var modelView      = new stcfw.ModelView( );
        modelView.template = _.template( jQuery( "script#st_iv-bs-template_carousel_item" ).html( ), null, stcfw.templateOptions );
        var htmlBullets    = "";
        var htmlItems      = "";
        collection.forEach( function( model, index ) {
            model.attributes.index = index;
            modelView.model = model;
            var active      = index === 0 ? ' class="active"' : "";
            htmlBullets    += '<li data-target="#' + id + '" data-slide-to="' + index + '"' + active + '></li>';
            htmlItems      += modelView.render( true );
        } );
        var viewContainer = new stcfw.ContainerView( {
            model: {
                attributes: {
                    id:      id,
                    bullets: htmlBullets,
                    items:   htmlItems
                }
            }
        } );
        viewContainer.template = _.template( jQuery( "script#st_iv-bs-template_carousel" ).html( ), null, stcfw.templateOptions );
        container.empty( );
        if ( jQuery( "div#stcfw-inline_search_results" ).length ) {
            var cssClass = "st_iv-inline";
        } else {
            var cssClass = "st_iv-overlay";
        }
        container.append( viewContainer.render( ).$el.find( "div.carousel.slide" ).addClass( cssClass ) );
    }

    stcfw.renderTabs = function( container, collection ) {
        var tabView       = new stcfw.ModelView( );
        tabView.template  = _.template( stcfw.getTemplate( "st_iv-bs-template_tabs_tab",  stcfw.post_type ).html( ), null, stcfw.templateOptions );
        var itemView      = new stcfw.ModelView( );
        itemView.template = _.template( stcfw.getTemplate( "st_iv-bs-template_tabs_item", stcfw.post_type ).html( ), null, stcfw.templateOptions );
        var htmlTabs      = "";
        var htmlItems     = "";
        collection.forEach( function( model, index ) {
            model.attributes.index         = index;
            itemView.model = tabView.model = model;
            htmlTabs                      += tabView.render( true );
            htmlItems                     += itemView.render( true );
        } );
        var viewContainer = new stcfw.ContainerView( {
            model: {
                attributes: {
                    tabs:  htmlTabs,
                    items: htmlItems
                }
            }
        } );
        viewContainer.template = _.template( stcfw.getTemplate( "st_iv-bs-template_tabs", stcfw.post_type ).html( ), null, stcfw.templateOptions );
        container.empty( );
        container.append( viewContainer.render( ).$el.find( "div.st_iv-bs-template_tabs_container" ) );
    }

    stcfw.renderTable = function( container, collection ) {
        var itemView      = new stcfw.ModelView( );
        itemView.template = _.template( stcfw.getTemplate( "st_iv-bs-template_table_item", stcfw.post_type ).html( ), null, stcfw.templateOptions );
        var htmlItems     = "";
        collection.forEach( function( model, index ) {
            itemView.model = model;
            htmlItems     += itemView.render( true );
        } );
        var viewContainer = new stcfw.ContainerView( {
            model: {
                attributes: {
                    items: htmlItems
                }
            }
        } );
        viewContainer.template = _.template( stcfw.getTemplate( "st_iv-bs-template_table", stcfw.post_type ).html( ), null, stcfw.templateOptions );
        container.empty( );
        container.append( viewContainer.render( ).$el.find( "table.table" ) );
    }

    stcfw.getTemplate = function( name, postType ) {
        if ( postType ) {
            var script = jQuery( "script#" + name + "-" + postType );
            if ( script.length ) {
                return script;
            }
        }
        return jQuery( "script#" + name );
    };
    
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

    var container = jQuery( "div#st_iv-container" );

    if ( stcfw.collection ) {
        // the page was statically initialized with a Backbone.js collection so render it otherwise the collection will be dynamically loaded
        stcfw.posts=new stcfw.Posts( );
        try {
            stcfw.posts.reset( JSON.parse( stcfw.collection ) );
        } catch ( e ) {
            console.log( "e=", e );
        }
        
        stcfw.renderGallery( container, stcfw.posts );
        //stcfw.renderCarousel( container, stcfw.posts, "st_iv-bootstrap_carousel_1" );
        //stcfw.renderTabs( container, stcfw.posts );
        //stcfw.renderTable( container, stcfw.posts );
    }

    jQuery( "div#st_iv-nav_images li a" ).click( function( e ) {
        jQuery( "div#st_iv-nav_images li" ).removeClass( "active" );
        var li = jQuery( this.parentNode ).addClass( "active" )[0];
        if ( li.id === "st_iv-gallery" ) {
            stcfw.renderGallery( container, stcfw.posts );
        } else if ( li.id === "st_iv-carousel" ) {
            stcfw.renderCarousel( container, stcfw.posts, "st_iv-bootstrap_carousel_1" );
            jQuery( "button.st_iv-bs-carousel_close_btn" ).click( function( e ) {
                jQuery( "div#st_iv-nav_images li" ).removeClass( "active" ).first( ).addClass( "active" );
                stcfw.renderGallery( container, stcfw.posts );
            } );          
        } else if ( li.id === "st_iv-tabs" ) {
            stcfw.renderTabs( container, stcfw.posts );
        } else if ( li.id === "st_iv-table" ) {
            stcfw.renderTable( container, stcfw.posts );
        }
        e.preventDefault( );
    } );

    var searchResults = jQuery( "div#stcfw-inline_search_results" );
    if ( searchResults.length ) {
        var widget = jQuery( "form.scpbcfw-search-fields-form" );
        if ( widget.length ) {
            // Hide irrelevant HTML elements when in inline search results mode
            widget.find( "div.scpbcfw-search-fields-checkbox-box" ).hide( ).siblings( "hr" ).hide( );
        } else {
            // "Search Types Custom Fields" widget not activated
            searchResults.html(
                '<div style="border:3px solid red;padding:10px;">Error: '
                + 'The shortcode stcfw_inline_search_results requires that the "Search Types Custom Fields" widget be activated.</div>'
            ).show(); 
        }
    }

    jQuery("input#scpbcfw-search-fields-submit").click(function(e){
        var div=jQuery("div#stcfw-inline_search_results");
        if(div.length){
            div.find( "div#st_iv-container" ).html( '<div class="st_iv-search_results_loading">Loading...<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Please wait.</div>' );
            div.find( "nav.navbar" ).find( "div#st_iv-nav_images li" ).removeClass( "active" ).first( ).addClass( "active" );
            div.find("div.st_iv-inner_envelope").show();
            jQuery("div.st_iv-close_inner_envelope").text(stcfwSearchTranslations.close);
            var query="action=stcfw_get_posts&"+jQuery(this).parents("form.scpbcfw-search-fields-form").serialize();
            console.log("input#scpbcfw-search-fields-submit::click():query=",query);
            jQuery.get(ajaxurl,query,function(r){
                console.log("input#scpbcfw-search-fields-submit::post():r=",r);
                if(r.success){
                    stcfw.posts=new stcfw.Posts();
                    try{
                        stcfw.posts.reset(JSON.parse(r.data));
                    }catch(e){
                        console.log( "e=", e );
                    }
                    stcfw.renderGallery(div.find("div#st_iv-container"),stcfw.posts);
                    console.log("input#scpbcfw-search-fields-submit::post():stcfw.posts=",stcfw.posts);
                }else{
                    div.find( "div#st_iv-container" ).html( '<div class="st_iv-error">' + r.data + '</div>' );
                }
                div.show();
            });
            e.preventDefault();
        }
    });
    jQuery("div.st_iv-close_inner_envelope").click(function(e){
        if(jQuery(this).text()==stcfwSearchTranslations.open){
            jQuery(this).text(stcfwSearchTranslations.close);
            jQuery(this).parents("div.st_iv-outer_envelope").find("div.st_iv-inner_envelope").show();
        }else{
            jQuery(this).text(stcfwSearchTranslations.open);
            jQuery(this).parents("div.st_iv-outer_envelope").find("div.st_iv-inner_envelope").hide();
        }
    });
    // wireup mobile swipe events
    jQuery( window ).on( "swipe", function( e ) {
        var carousel = jQuery( "div#st_iv-container div.carousel.slide" );
        if ( carousel.length ) {
            if ( e.swipestop.coords[0] > e.swipestart.coords[0] ) {
                carousel.find( "a.left.carousel-control" ).click( );
            }else{
                carousel.find( "a.right.carousel-control" ).click( );
            }
        }
    } );
} );
