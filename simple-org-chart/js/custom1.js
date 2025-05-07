jQuery(document).ready(function() {

    jQuery("#org").jOrgChart({
        chartElement : '#chart',
        dragAndDrop  : false
    });

    var $links = jQuery( '.jOrgChart' ).find( 'a' );

    $links.on( 'click', function ( e ) {
        e.preventDefault();

        var group = jQuery( this ).attr( 'href' ).replace( '#', '' );

        jQuery( '.overlay1' ).hide();
        jQuery( '.overlay1[data-id="' + group + '"]' ).show();
        jQuery( '.overlay1[data-id="' + group + '"]' ).css('visibility','visible');
        jQuery( '.overlay1[data-id="' + group + '"]' ).css('opacity',1);
    });


});

