<?php
/*
    Plugin Name: AKR Customs
    Description: Custom Modification done  by ANUSHKA KR on 06/30/2021 , for more information to contact the developer  - https://www.upwork.com/fl/anushkakrajasingha
    Version: 1.0.0
    Author: Anushka K Rajasingha
    Author URI: https://www.upwork.com/fl/anushkakrajasingha
    Text Domain: akr-customs
 */
define('AKRPREFIX','akr');
define('AKRURL', plugin_dir_url( __FILE__ ));


class akrCustoms{

    private $working_taxonomy;
    private $healthdirectory_posttype;

    function __construct()
    {
        $this->working_taxonomy = 'location';
        $this->healthdirectory_posttype = 'healthdirectory';

        add_shortcode( AKRPREFIX.'ListTaxTerms', array($this,AKRPREFIX.'funcGeTaxTerms') );
        add_shortcode( AKRPREFIX.'ShowQueryVars', array($this,AKRPREFIX.'ShowQueryVars') );
        add_action( 'wp_enqueue_scripts', array($this,AKRPREFIX.'enqueuestyles')  );

        add_action( 'init',array($this, AKRPREFIX.'_create_location_taxonomies'), 0 );

        add_filter('request', array($this,AKRPREFIX.'change_term_request'),  1, 1 );
        add_filter( 'term_link', array($this,AKRPREFIX.'term_permalink'), 10, 3 );

        add_action('template_redirect',  array($this,AKRPREFIX.'old_term_redirect') );

        // add_filter( 'post_type_link', array($this,AKRPREFIX.'na_remove_slug'), 10, 3 );

        // add_action( 'pre_get_posts', array($this,AKRPREFIX.'na_parse_request') );

        add_action( 'init', array($this,AKRPREFIX.'cpt_rewrites') );

        add_filter( 'post_type_link', array($this,AKRPREFIX.'cpt_permalinks'), 10, 3 );

        add_filter( 'wp_unique_post_slug',  array($this,AKRPREFIX.'prevent_slug_duplicates'), 10, 6 );

        add_filter( 'query_vars', array($this,AKRPREFIX.'register_query_vars') );
    }

    function akrShowQueryVars($atts){
        $atts = shortcode_atts(array(
            'hide' => true
        ),$atts,AKRPREFIX.'ShowQueryVars');
        ;
        ob_start();
        global $wp_query;
        if(!$atts['hide']) echo '<!--'; else echo '<code>';
        var_dump($wp_query->query_vars);
        if(!$atts['hide']) echo '-->'; else echo '</code>';
        $output = ob_get_clean();
        return $output;
    }

    function akrregister_query_vars( $vars ) {
        $vars[] = 'state';
        $vars[] = 'city';
        $vars[] = 'issue';
        return $vars;
    }

    function akrcpt_rewrites() {
       add_rewrite_rule( '([^/]+)/embed/?$', 'index.php?'.$this->healthdirectory_posttype.'=$matches[1]&embed=true', 'bottom');
        add_rewrite_rule( '([^/]+)/trackback/?$', 'index.php?'.$this->healthdirectory_posttype.'=$matches[1]&tb=1', 'bottom');
        add_rewrite_rule( '([^/]+)/page/?([0-9]{1,})/?$', 'index.php?'.$this->healthdirectory_posttype.'=$matches[1]&paged=$matches[2]', 'bottom');
        add_rewrite_rule( '([^/]+)/comment-page-([0-9]{1,})/?$', 'index.php?'.$this->healthdirectory_posttype.'=$matches[1]&cpage=$matches[2]', 'bottom');
        add_rewrite_rule( '([^/]+)(?:/([0-9]+))?/?$', 'index.php?'.$this->healthdirectory_posttype.'=$matches[1]', 'bottom');
        add_rewrite_rule('([^/]*)/([^/]*)/([^/]*)', 'index.php?pagename=plugintest&state=$matches[1]&city=$matches[2]&issue=$matches[3]','top');
       // add_rewrite_rule('([^/]*)/([^/]*)/([^/]*)', 'index.php?post_type=healthdirectory&jsf=jet-engine&tax=location:$matches[1],$matches[2];issues:$matches[3]','top');
    }

    function akrcpt_permalinks( $post_link, $post, $leavename ) {
        if ( isset( $post->post_type ) && $this->healthdirectory_posttype == $post->post_type ) {
            $post_link = home_url( $post->post_name );
        }

        return $post_link;
    }

    function akrprevent_slug_duplicates( $slug, $post_ID, $post_status, $post_type, $post_parent, $original_slug ) {
        $check_post_types = array(
            'post',
            'page',
            $this->healthdirectory_posttype
        );

        if ( ! in_array( $post_type, $check_post_types ) ) {
            return $slug;
        }

        if ( $this->healthdirectory_posttype == $post_type ) {
            // Saving a therapist post, check for duplicates in POST or PAGE post types
            $post_match = get_page_by_path( $slug, 'OBJECT', 'post' );
            $page_match = get_page_by_path( $slug, 'OBJECT', 'page' );

            if ( $post_match || $page_match ) {
                $slug .= '-duplicate';
            }
        } else {
            // Saving a POST or PAGE, check for duplicates in therapist post type
            $therapist_match = get_page_by_path( $slug, 'OBJECT', $this->healthdirectory_posttype );

            if ( $therapist_match ) {
                $slug .= '-duplicate';
            }
        }

        return $slug;
    }



    public function akrenqueuestyles(){
        wp_enqueue_style( AKRPREFIX.'style', AKRURL.'css/mod06302021.css' );
    }

    private function akrGetChildTerms($parent,$atts){
        $atts['parent'] = $parent;
        $terms = get_terms($atts );$output='';

        if($terms) {
            ob_start();
            //echo '<div id="' . AKRPREFIX . 'listtaxterms" class="listtaxterms-' . $atts['taxonomy'] . '">';
            foreach ($terms as $term) {
                if($term->count > $atts['morethan']) {
                    echo '<div class="tax-item term-' . $term->slug . '" style="flex-basis: ' . (100 / $atts['columns']) . '%;">';
                    echo '<a';
                    echo ' href="' . get_term_link($term) . '"';
                    echo '>';
                    echo $term->name;
                    if($atts['showcount']) echo '(' . $term->count . ')';
                    echo '</a>';
                    echo '</div>';
                }
            }
            //echo '</div>';
            $output = ob_get_clean();
        }
        return $output;
    }

    public function akrfuncGeTaxTerms( $atts ) {
        $atts = shortcode_atts(array(
            'taxonomy' => 'category',
            'hide_empty' => true,
            'columns' => 4,
            'morethan' => 0 ,
            'showcount' => false,
            'parent' => 0,
            'inline' => 1,
            'childonly' => 0,
            'parentonly' => 0
        ), $atts, 'ListTaxTerms' );

        $terms = get_terms($atts );$output='';$childrens = '';
        if($terms) {
            ob_start();
            echo '<div id="' . AKRPREFIX . 'listtaxterms" class="listtaxterms-' . $atts['taxonomy'] . '">';
            foreach ($terms as $term) {
                if($term->count > $atts['morethan']) {
                    if(!$atts['childonly']) {
                        echo '<div class="tax-item term-' . $term->slug . '" style="flex-basis: ' . (100 / $atts['columns']) . '%;">';
                        echo '<a';
                        echo ' href="' . get_term_link($term) . '"';
                        echo '>';
                        echo $term->name;
                        if ($atts['showcount']) echo '(' . $term->count . ')';
                        echo '</a>';
                    }
                    if(!$atts['parentonly']){
                        $childrens = $this->akrGetChildTerms($term->term_id,$atts);
                        if(!$atts['childonly']) {
                            if ($atts['inline']) {
                                echo $childrens . '</div>';
                            } else {
                                echo '</div>' . $childrens;
                            }
                        }
                        else{
                            echo $childrens ;
                        }} else echo  '</div>';
                }
                unset($childrens);
            }
            echo '</div>';
            $output = ob_get_clean();
        }
        return $output;
    }




    /* remove Taxonomy */


    function akrchange_term_request($query){

        $tax_name = $this->working_taxonomy;// specify you taxonomy name here, it can be also 'category' or 'post_tag'

        // Request for child terms differs, we should make an additional check
        if( $query['attachment'] ) :
            $include_children = true;
            $name = $query['attachment'];
        else:
            $include_children = false;
            $name = $query['name'];
        endif;


        $term = get_term_by('slug', $name, $tax_name); // get the current term to make sure it exists

        if (isset($name) && $term && !is_wp_error($term)): // check it here

            if( $include_children ) {
                unset($query['attachment']);
                $parent = $term->parent;
                while( $parent ) {
                    $parent_term = get_term( $parent, $tax_name);
                    $name = $parent_term->slug . '/' . $name;
                    $parent = $parent_term->parent;
                }
            } else {
                unset($query['name']);
            }

            switch( $tax_name ):
                case 'category':{
                    $query['category_name'] = $name; // for categories
                    break;
                }
                case 'post_tag':{
                    $query['tag'] = $name; // for post tags
                    break;
                }
                default:{
                    $query[$tax_name] = $name; // for another taxonomies
                    break;
                }
            endswitch;

        endif;

        return $query;

    }




    function akrterm_permalink( $url, $term, $taxonomy ){

        $taxonomy_name = $this->working_taxonomy; // your taxonomy name here
        $taxonomy_slug = $this->working_taxonomy; // the taxonomy slug can be different with the taxonomy name (like 'post_tag' and 'tag' )

        // exit the function if taxonomy slug is not in URL
        if ( strpos($url, $taxonomy_slug) === FALSE || $taxonomy != $taxonomy_name ) return $url;

        $url = str_replace('/' . $taxonomy_slug, '', $url);

        return $url;
    }



    function akrold_term_redirect() {

        $taxonomy_name = $this->working_taxonomy;
        $taxonomy_slug = $this->working_taxonomy;

        // exit the redirect function if taxonomy slug is not in URL
        if( strpos( $_SERVER['REQUEST_URI'], $taxonomy_slug ) === FALSE)
            return;

        if( ( is_category() && $taxonomy_name=='category' ) || ( is_tag() && $taxonomy_name=='post_tag' ) || is_tax( $taxonomy_name ) ) :

            wp_redirect( site_url( str_replace($taxonomy_slug, '', $_SERVER['REQUEST_URI']) ), 301 );
            exit();

        endif;

    }

    /* Custom Taxonomy */
    /**
     * Create two taxonomies, genres and writers for the post type "book".
     *
     * @see register_post_type() for registering custom post types.
     */
    function akr_create_location_taxonomies() {

        // Add new taxonomy, NOT hierarchical (like tags)
        $labels = array(
            'name'                       => _x( 'Locations', 'taxonomy general name', 'textdomain' ),
            'singular_name'              => _x( 'Location', 'taxonomy singular name', 'textdomain' ),
            'search_items'               => __( 'Search Locations', 'textdomain' ),
            'popular_items'              => __( 'Popular Locations', 'textdomain' ),
            'all_items'                  => __( 'All Locations', 'textdomain' ),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __( 'Edit Location', 'textdomain' ),
            'update_item'                => __( 'Update Location', 'textdomain' ),
            'add_new_item'               => __( 'Add New Location', 'textdomain' ),
            'new_item_name'              => __( 'New Location Name', 'textdomain' ),
            'separate_items_with_commas' => __( 'Separate Locations with commas', 'textdomain' ),
            'add_or_remove_items'        => __( 'Add or remove Locations', 'textdomain' ),
            'choose_from_most_used'      => __( 'Choose from the most used Locations', 'textdomain' ),
            'not_found'                  => __( 'No Locations found.', 'textdomain' ),
            'menu_name'                  => __( 'Locations', 'textdomain' ),
        );

        $args = array(
            'hierarchical'          => true,
            'labels'                => $labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            // 'update_count_callback' => '_update_post_term_count',
            'query_var'             => true,
            'rewrite'               => array( 'slug' => $this->working_taxonomy, 'hierarchical'          => true ),
        );

        register_taxonomy( $this->working_taxonomy, array($this->healthdirectory_posttype), $args );
    }

    function akrna_remove_slug( $post_link, $post, $leavename ) {

        if ( $this->healthdirectory_posttype != $post->post_type || 'publish' != $post->post_status ) {
            return $post_link;
        }

        $post_link = str_replace( '/' . $post->post_type . '/', '/', $post_link );

        return $post_link;
    }

    function akrna_parse_request( $query ) {

        if ( ! $query->is_main_query() || 2 != count( $query->query ) || ! isset( $query->query['page'] ) ) {
            return;
        }

        if ( ! empty( $query->query['name'] ) ) {
            $query->set( 'post_type', array( 'post', $this->healthdirectory_posttype, 'page' ) );
        }
    }





}

$akr = new akrCustoms();
	