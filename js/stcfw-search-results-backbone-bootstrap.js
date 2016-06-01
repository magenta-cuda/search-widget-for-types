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
        modelView.template = _.template( jQuery( "script#st_iv-bs-template_gallery_item" ).html( ), null, stcfw.templateOptions );
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
            var cssClass = "";
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

    stcfw.posts=new stcfw.Posts( );
    try {
        stcfw.posts.reset( JSON.parse( stcfw.collection ) );
    }catch( e ) {
        console.log( "e=", e );
    }
    
    var container = jQuery( "div#st_iv-container" );
    stcfw.renderGallery( container, stcfw.posts );
    //stcfw.renderCarousel( container, stcfw.posts, "st_iv-bootstrap_carousel_1" );
    //stcfw.renderTabs( container, stcfw.posts );
    //stcfw.renderTable( container, stcfw.posts );
    
    jQuery( "div#st_iv-nav_images li a" ).click( function( e ) {
        jQuery( "div#st_iv-nav_images li" ).removeClass( "active" );
        var li = jQuery( this.parentNode ).addClass( "active" )[0];
        if ( li.id === "st_iv-gallery" ) {
            stcfw.renderGallery( container, stcfw.posts );
        } else if ( li.id === "st_iv-carousel" ) {
            stcfw.renderCarousel( container, stcfw.posts, "st_iv-bootstrap_carousel_1" );
            jQuery( "button.st_iv-bs-carousel_close_btn" ).click( function( e ) {
                jQuery( "div#st_iv-nav_images li" ).removeClass( "active" );
                jQuery( "div#st_iv-nav_images li" ).first( ).addClass( "active" );
                stcfw.renderGallery( container, stcfw.posts );
            } );          
        } else if ( li.id === "st_iv-tabs" ) {
            stcfw.renderTabs( container, stcfw.posts );
        } else if ( li.id === "st_iv-table" ) {
            stcfw.renderTable( container, stcfw.posts );
        }
        e.preventDefault( );
    } );

} );