// Allow translations
var __                  = wp.i18n.__;

// Set up basic block info
var el                  = wp.element.createElement;
var ic                  = wp.blockEditor.InspectorControls;
var sc                  = wp.components.SelectControl;
var tc                  = wp.components.TextControl;
var ssr                 = wp.serverSideRender;
var pb                  = wp.components.PanelBody;
var registerBlockType   = wp.blocks.registerBlockType;

// Set up block
registerBlockType( 'sitemap/block', {
    
    // Block info
    title: __( 'HTML Sitemap', 'companion-sitemap-generator' ), 
    description: __( 'Display an HTML sitemap.', 'companion-sitemap-generator' ),
    icon: 'editor-ul',
    category: 'widgets',

    // Attributes
    attributes:  {
        columns : {
            default: '1',
        },
        orderby: {
            default: 'date'
        },
        sort: {
            default: 'asc'
        },
        limit: {
            default: '-1'
        }
    },

    // Back-end
    edit( props ){

        const attributes        =  props.attributes;
        const setAttributes     =  props.setAttributes;

        // Functions to update attributes
        function changeColumns( columns ) {
            setAttributes( {columns} );
        }
        function changeOrderby( orderby ) {
            setAttributes( {orderby} );
        }
        function changeSort( sort ) {
            setAttributes( {sort} );
        }
        function changeLimit( limit ) {
            setAttributes( {limit} );
        }

        // Display block preview and UI
        return el( 'div', {}, [

            // Preview the block with a PHP render callback
            el( ssr, {
                block: 'sitemap/block',
                attributes: attributes
            } ),

            // Settings
            el( ic, {},

                el( pb, { title: __( 'Sitemap settings', 'companion-sitemap-generator' ), initialOpen: true },
                    [
                        el( sc, {
                            value: attributes.columns,
                            label: __( 'Number of columns', 'companion-sitemap-generator' ),
                            onChange: changeColumns,
                            options: [
                                {value: '1', label: __( 'Single column', 'companion-sitemap-generator' ) },
                                {value: '2', label: __( 'Two columns', 'companion-sitemap-generator' ) },
                                {value: '3', label: __( 'Three columns', 'companion-sitemap-generator' ) },
                                {value: '4', label: __( 'Four columns', 'companion-sitemap-generator' ) },
                            ]
                        }),

                        el( sc, {
                            value: attributes.orderby,
                            label: __( 'Order by', 'companion-sitemap-generator' ),
                            onChange: changeOrderby,
                            options: [
                                {value: 'date', label: __( 'Date', 'companion-sitemap-generator' ) },
                                {value: 'title', label: __( 'Post title', 'companion-sitemap-generator' ) },
                                {value: 'id', label: __( 'Post ID', 'companion-sitemap-generator' ) },
                            ]
                        }),

                        el( sc, {
                            value: attributes.sort,
                            label: __( 'Sorting', 'companion-sitemap-generator' ),
                            onChange: changeSort,
                            options: [
                                {value: 'ASC', label: __( 'Ascending', 'companion-sitemap-generator' )+' (A-Z / 1-9)' },
                                {value: 'DESC', label: __( 'Descending', 'companion-sitemap-generator' )+' (Z-A / 9-1)' },
                            ]
                        }),

                        el( tc, {
                            value: attributes.limit,
                            label: __( 'Post limit', 'companion-sitemap-generator' ),
                            onChange: changeLimit,
                            help: __( 'How many posts to show per block? -1 to show all.', 'companion-sitemap-generator' ),
                        })
                    ]
                )
            )

        ] )
    },
    save() {
        return null;
    }
});