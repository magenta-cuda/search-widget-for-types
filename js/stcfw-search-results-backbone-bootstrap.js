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
            var html = this.template( this.attributes );
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
            attributes: {
                items: modelsHtml
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
            attributes: {
                id:      id,
                bullets: htmlBullets,
                items:   htmlItems
            }
        } );
        viewContainer.template = _.template( jQuery( "script#st_iv-bs-template_carousel" ).html( ), null, stcfw.templateOptions );
        container.empty( );
        //container.append( viewContainer.render( ).$el.find( "div.carousel.slide" ) );
        container.append( viewContainer.render( ).$el.find( "div.carousel.slide" ).css( { position:"fixed", left:"0px", top:"0px", zIndex:"1000000" } ) );
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
    
    stcfw.posts=new stcfw.Posts( );
    try {
        stcfw.posts.reset( JSON.parse( stcfw.collection ) );
    }catch( e ) {
        console.log( "e=", e );
    }
    
    var container = jQuery( "div#st_iv-container" );
    //stcfw.renderGallery( container, stcfw.posts );
    stcfw.renderCarousel( container, stcfw.posts, "st_iv-bootstrap_carousel_1" );
} );