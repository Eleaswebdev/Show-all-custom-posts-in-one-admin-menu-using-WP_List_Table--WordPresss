
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

    class FT_WP_Table extends WP_List_Table
    {

        private $order;
        private $orderby;
        private $posts_per_page = 25;

        public function __construct()
        {
            parent :: __construct(array(
                'singular' => "allcustompost",
                'plural' => "allcustompost",
                'ajax' => true
            ));

            $this->set_order();
            $this->set_orderby();
            $this->prepare_items();
            $this->display();
        }

        private function get_sql_results()
        {
            global $wpdb;
			$args = array('ID', 'post_title', 'post_date', 'post_type' );
			$sql_select = implode(', ', $args);

			#$sql_results = $wpdb->get_results("SELECT " . $sql_select . " FROM " . $wpdb->posts);
            

			$sql_results =$wpdb->get_results ("
			SELECT $sql_select
			FROM $wpdb->posts
			WHERE $wpdb->posts.post_status = 'publish' 
			AND $wpdb->posts.post_type IN ('news_opinion_list_pc', 'feature', 'feature_square_left', 'feature_photo_galler')
			
			ORDER BY $wpdb->posts.post_date DESC
		 ");
		 return $sql_results;
        }

        public function set_order()
        {
            $order = 'DESC';
            if (isset($_GET['order']) AND $_GET['order'])
                    $order = $_GET['order'];
            $this->order = esc_sql($order);
        }

        public function set_orderby()
        {
            $orderby = 'create_date';
            if (isset($_GET['orderby']) AND $_GET['orderby'])
                    $orderby = $_GET['orderby'];
            $this->orderby = esc_sql($orderby);
        }

        /**
         * @see WP_List_Table::ajax_user_can()
         */
        public function ajax_user_can()
        {
            return current_user_can('edit_posts');
        }

        /**
         * @see WP_List_Table::no_items()
         */
        public function no_items()
        {
            _e('No frequent traveler found.');
        }

        /**
         * @see WP_List_Table::get_views()
         */
        public function get_views()
        {
            return array();
        }

		public function get_columns()
		{
			$columns = array(
				'ID' => __('ID'),
				'post_title' => __('Title'),
				'post_type' => __('Post Type'),
				'post_date' => __('Date')
			);
			return $columns;
		}
		
		public function get_sortable_columns()
		{
			$sortable = array(
				'ID' => array('ID', true),
				'post_title' => array('post_title', true),
				'post_type' => __('post_type', true),
				'post_date' => array('post_date', true)
			);
			return $sortable;
		}

        /**
         * Prepare data for display
         * @see WP_List_Table::prepare_items()
         */
        public function prepare_items()
        {
            $columns = $this->get_columns();
            $hidden = array();
            $sortable = $this->get_sortable_columns();
            $this->_column_headers = array(
                $columns,
                $hidden,
                $sortable
            );

            // SQL results
            $posts = $this->get_sql_results();
            empty($posts) AND $posts = array();

            # >>>> Pagination
            $per_page = $this->posts_per_page;
            $current_page = $this->get_pagenum();
            $total_items = count($posts);
            $this->set_pagination_args(array(
                'total_items' => $total_items,
                'per_page' => $per_page,
                'total_pages' => ceil($total_items / $per_page)
            ));
            $last_post = $current_page * $per_page;
            $first_post = $last_post - $per_page + 1;
            $last_post > $total_items AND $last_post = $total_items;

            // Setup the range of keys/indizes that contain 
            // the posts on the currently displayed page(d).
            // Flip keys with values as the range outputs the range in the values.
            $range = array_flip(range($first_post - 1, $last_post - 1, 1));

            // Filter out the posts we're not displaying on the current page.
            $posts_array = array_intersect_key($posts, $range);
            # <<<< Pagination
            // Prepare the data
            $permalink = __('Edit:');
            foreach ($posts_array as $key => $post) {
                $link = get_edit_post_link($post->ID);
                $no_title = __('No title set');
                $title = !$post->post_title ? "<em>{$no_title}</em>" : $post->post_title;
                $posts[$key]->post_title = "<a title='{$permalink} {$title}' href='{$link}'>{$title}</a>";
            }
            $this->items = $posts_array;
        }

        /**
         * A single column
         */
        public function column_default($item, $column_name)
        {
            return $item->$column_name;
        }

        /**
         * Override of table nav to avoid breaking with bulk actions & according nonce field
         */
        public function display_tablenav($which)
        {

            ?>
            <div class="tablenav <?php echo esc_attr($which); ?>">
                <!-- 
                <div class="alignleft actions">
                <?php # $this->bulk_actions( $which );    ?>
                </div>
                -->
                <?php
                $this->extra_tablenav($which);
                $this->pagination($which);

                ?>
                <br class="clear" />
            </div>
            <?php
        }

        /**
         * Disables the views for 'side' context as there's not enough free space in the UI
         * Only displays them on screen/browser refresh. Else we'd have to do this via an AJAX DB update.
         * 
         * @see WP_List_Table::extra_tablenav()
         */
        public function extra_tablenav($which)
        {
            global $wp_meta_boxes;
            $views = $this->get_views();
            if (empty($views)) return;

            $this->views();
        }

    }



function ht_custom_post_listing_admin_actions()
{
    add_menu_page('All Custom Post Listing', 'All Custom Post Listing', 'activate_plugins', 'custom_post_listing', 'ft_list', '', 4);
}

add_action('admin_menu', 'ht_custom_post_listing_admin_actions');

function ft_list()
{
    echo '<div class="wrap"><h3>'. __('All Posts(Standard Layout, Feature - Full Width Photo, Feature - Square left Photo, Feature - Photo Gallery )') .'</h3>';


// Display filter HTML
// echo "<select name='{$taxonomy_slug}' id='{$taxonomy_slug}' class='postform'>";
// echo '<option value="">' . sprintf( esc_html__( 'Show All %s', 'text_domain' ), $taxonomy_name ) . '</option>';

// 	printf(
// 		'<option value="news_opinion_list_pc">news_opinion_list_pc</option>
// 		<option value="feature">Feature</option>',
// 	);

// echo '</select>';

    $ftList = new FT_WP_Table();
    echo '</div>';
}
