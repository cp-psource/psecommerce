<?php
/*
Plugin Name: MarketPress-Statistiken
Plugin URI: https://n3rds.work
Description: Zeigt MarketPress-Statistiken mithilfe der GooGle-Diagrammbibliothek an (https://google-developers.appspot.com/chart/)
Version: 0.4.2
Author: DerN3rd
*/
load_plugin_textdomain('mp_st', false, basename( dirname( __FILE__ ) ) . '/languages' );

/* Runs when plugin is activated */
register_activation_hook(__FILE__, 'mp_st_install'); 

/* Runs on plugin deactivation*/
register_deactivation_hook( __FILE__, 'mp_st_remove' );

function mp_st_install() {
} 

function mp_st_remove() {
}

add_action('admin_menu', 'mp_st_admin_menu');

function mp_st_admin_menu() {
  add_dashboard_page( __('Verkaufsstatistik', 'mp_st'), __('Shopstatistik', 'mp_st'), 'administrator', 'mp_st', 'mp_st_page', 'dashicons-analytics' );
}

function mp_st_page() {

  if ( class_exists( 'MarketPress' ) ) {
    if ( function_exists('current_user_can') && !current_user_can('manage_options') )
  	  die(__('Cheatin&#8217; uh?'));

      global $wpdb, $mp;

      $totality = $wpdb->get_row("SELECT count(p.ID) as 'count', sum(m.meta_value) as 'total', avg(m.meta_value) as average FROM $wpdb->posts p JOIN $wpdb->postmeta m ON p.ID = m.post_id WHERE p.post_type = 'mp_order' AND m.meta_key = 'mp_order_total'");  
      if (!empty($totality->count)){$totalitycount = $totality->count;} else {$totalitycount = 0;}
      if (!empty($totality->total)){$totalitytotal = $totality->total;} else {$totalitytotal = 0;}
      if (!empty($totality->average)){$totalityaverage = $totality->average;} else {$totalityaverage = 0;}

      $totalityitems = $wpdb->get_row("SELECT count(p.ID) as 'count', sum(m.meta_value) as 'total', avg(m.meta_value) as average FROM $wpdb->posts p JOIN $wpdb->postmeta m ON p.ID = m.post_id WHERE p.post_type = 'mp_order' AND m.meta_key = 'mp_order_items'");  
      if (!empty($totalityitems->count)){$totalityitemscount = $totalityitems->count;} else {$totalityitemscount = 0;}
      if (!empty($totalityitems->total)){$totalityitemstotal = $totalityitems->total;} else {$totalityitemstotal = 0;}
      if (!empty($totalityitems->average)){$totalityitemsaverage = $totalityitems->average;} else {$totalityitemsaverage = 0;}


      function mp_st_stat( $time = '-0 days' , $stat = 'count', $echo = true ){
      global $wpdb, $mp;
      $year = date('Y', strtotime($time));
      $month = date('m', strtotime($time));

      $monthquery = $wpdb->get_row("SELECT count(p.ID) as 'count', sum(m.meta_value) as 'total', avg(m.meta_value) as average FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON p.ID = m.post_id WHERE p.post_type = 'mp_order' AND m.meta_key = 'mp_order_total' AND YEAR(p.post_date) = $year AND MONTH(p.post_date) = $month");
      $monthstat = 0;
      if (!empty($monthquery->$stat)) $monthstat = $monthquery->$stat;

      if ($echo) echo $monthstat; 
      else return $monthstat; 
    }

    function mp_st_stat_items( $time = '-0 days' , $stat = 'count', $echo = true ){
    global $wpdb, $mp;
    $year = date('Y', strtotime($time));
    $month = date('m', strtotime($time));

    $monthquery = $wpdb->get_row("SELECT count(p.ID) as 'count', sum(m.meta_value) as 'total', avg(m.meta_value) as average FROM $wpdb->posts p JOIN $wpdb->postmeta m ON p.ID = m.post_id WHERE p.post_type = 'mp_order' AND m.meta_key = 'mp_order_items' AND YEAR(p.post_date) = $year AND MONTH(p.post_date) = $month");  
    $monthstat = 0;
    if (!empty($monthquery->$stat)) $monthstat = $monthquery->$stat;

    if ($echo) echo $monthstat; 
    else return $monthstat; 
  }

  echo '<script type="text/javascript" src="' . plugins_url( 'bigtext.js' , __FILE__ ) . '" ></script>';

  ?>
  <div class="wrap" style="background: #fff;">
  <table style="width: 100%;">
    <tr>
      <td>
        <script type="text/javascript" src="https://www.google.com/jsapi"></script>
        <script type="text/javascript">
          google.load("visualization", "1", {packages:["corechart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
              ['<?php _e('Monat', 'mp_st'); ?>', '<?php _e('Gesamt', 'mp_st'); ?>'],
              ['<?php echo date("M",strtotime("-12 Months")) ?>', <?php mp_st_stat('-12 months', 'total'); ?>],
              ['<?php echo date("M",strtotime("-11 Months")) ?>', <?php mp_st_stat('-11 months', 'total'); ?>],
              ['<?php echo date("M",strtotime("-10 Months")) ?>', <?php mp_st_stat('-10 months', 'total'); ?>],
              ['<?php echo date("M",strtotime("-9 Months")) ?>', <?php mp_st_stat('-9 months', 'total'); ?>],
              ['<?php echo date("M",strtotime("-8 Months")) ?>', <?php mp_st_stat('-8 months', 'total'); ?>],
              ['<?php echo date("M",strtotime("-7 Months")) ?>', <?php mp_st_stat('-7 months', 'total'); ?>],
              ['<?php echo date("M",strtotime("-6 Months")) ?>', <?php mp_st_stat('-6 months', 'total'); ?>],
              ['<?php echo date("M",strtotime("-5 Months")) ?>', <?php mp_st_stat('-5 months', 'total'); ?>],
              ['<?php echo date("M",strtotime("-4 Months")) ?>', <?php mp_st_stat('-4 months', 'total'); ?>],
              ['<?php echo date("M",strtotime("-3 Months")) ?>', <?php mp_st_stat('-3 months', 'total'); ?>],
              ['<?php echo date("M",strtotime("-2 Months")) ?>', <?php mp_st_stat('-2 months', 'total'); ?>],
              ['<?php echo date("M",strtotime("-1 Months")) ?>', <?php mp_st_stat('-1 months', 'total'); ?>],
              ['<?php echo date("M",strtotime("-0 Months")) ?>', <?php mp_st_stat('-0 months', 'total'); ?>],
            ]);
            var options = {
              title: '<?php _e('Gesamtumsatz, 12 Monate', 'mp_st'); ?>',
              colors: ['#000000', '#D44413'],
              theme: {legend: {position: 'in'}, axisTitlesPosition: 'in'},
              hAxis: {title: '<?php _e('Jahr', 'mp_st'); ?>', titleTextStyle: {color: '#999999'}},
              seriesType: "bars",
              // curveType: "function",
              series: {1: {type: "line"}}
            };
            var chart = new google.visualization.ComboChart(document.getElementById('total_chart'));
            chart.draw(data, options);
          }
        </script>
        <div id="total_chart" style="width: 100%; height: 350px;"></div>

        <script type="text/javascript">
          google.load("visualization", "1", {packages:["corechart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
              ['<?php _e('Monat', 'mp_st'); ?>', '<?php _e('Durchschnittlich', 'mp_st'); ?>'],
              ['<?php echo date("M",strtotime("-12 Months")) ?>', <?php mp_st_stat('-12 months', 'average'); ?>],
              ['<?php echo date("M",strtotime("-11 Months")) ?>', <?php mp_st_stat('-11 months', 'average'); ?>],
              ['<?php echo date("M",strtotime("-10 Months")) ?>', <?php mp_st_stat('-10 months', 'average'); ?>],
              ['<?php echo date("M",strtotime("-9 Months")) ?>', <?php mp_st_stat('-9 months', 'average'); ?>],
              ['<?php echo date("M",strtotime("-8 Months")) ?>', <?php mp_st_stat('-8 months', 'average'); ?>],
              ['<?php echo date("M",strtotime("-7 Months")) ?>', <?php mp_st_stat('-7 months', 'average'); ?>],
              ['<?php echo date("M",strtotime("-6 Months")) ?>', <?php mp_st_stat('-6 months', 'average'); ?>],
              ['<?php echo date("M",strtotime("-5 Months")) ?>', <?php mp_st_stat('-5 months', 'average'); ?>],
              ['<?php echo date("M",strtotime("-4 Months")) ?>', <?php mp_st_stat('-4 months', 'average'); ?>],
              ['<?php echo date("M",strtotime("-3 Months")) ?>', <?php mp_st_stat('-3 months', 'average'); ?>],
              ['<?php echo date("M",strtotime("-2 Months")) ?>', <?php mp_st_stat('-2 months', 'average'); ?>],
              ['<?php echo date("M",strtotime("-1 Months")) ?>', <?php mp_st_stat('-1 months', 'average'); ?>],
              ['<?php echo date("M",strtotime("-0 Months")) ?>', <?php mp_st_stat('-0 months', 'average'); ?>],
            ]);
            var options = {
              title: '<?php _e('Durchschnitt pro Verkauf, 12 Monate', 'mp_st'); ?>',
              colors: ['#000000', '#D44413'],
              theme: {legend: {position: 'in'}, axisTitlesPosition: 'in'},
              hAxis: {title: 'Jahr', titleTextStyle: {color: '#999999'}},
              seriesType: "bars",
              // curveType: "function",
              series: {1: {type: "line"}}
            };
            var chart = new google.visualization.ComboChart(document.getElementById('average_chart'));
            chart.draw(data, options);
          }
        </script>
        <div id="average_chart" style="width: 100%; height: 250px;"></div>

        <script type="text/javascript" src="https://www.google.com/jsapi"></script>
        <script type="text/javascript">
          google.load("visualization", "1", {packages:["corechart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
              ['<?php _e('Monat', 'mp_st'); ?>', '<?php _e('Gesamt', 'mp_st'); ?>'],
              ['<?php echo date("M",strtotime("-12 Months")) ?>', <?php mp_st_stat_items('-12 months', 'total'); ?>],
              ['<?php echo date("M",strtotime("-11 Months")) ?>', <?php mp_st_stat_items('-11 months', 'total'); ?>],
              ['<?php echo date("M",strtotime("-10 Months")) ?>', <?php mp_st_stat_items('-10 months', 'total'); ?>],
              ['<?php echo date("M",strtotime("-9 Months")) ?>', <?php mp_st_stat_items('-9 months', 'total'); ?>],
              ['<?php echo date("M",strtotime("-8 Months")) ?>', <?php mp_st_stat_items('-8 months', 'total'); ?>],
              ['<?php echo date("M",strtotime("-7 Months")) ?>', <?php mp_st_stat_items('-7 months', 'total'); ?>],
              ['<?php echo date("M",strtotime("-6 Months")) ?>', <?php mp_st_stat_items('-6 months', 'total'); ?>],
              ['<?php echo date("M",strtotime("-5 Months")) ?>', <?php mp_st_stat_items('-5 months', 'total'); ?>],
              ['<?php echo date("M",strtotime("-4 Months")) ?>', <?php mp_st_stat_items('-4 months', 'total'); ?>],
              ['<?php echo date("M",strtotime("-3 Months")) ?>', <?php mp_st_stat_items('-3 months', 'total'); ?>],
              ['<?php echo date("M",strtotime("-2 Months")) ?>', <?php mp_st_stat_items('-2 months', 'total'); ?>],
              ['<?php echo date("M",strtotime("-1 Months")) ?>', <?php mp_st_stat_items('-1 months', 'total'); ?>],
              ['<?php echo date("M",strtotime("-0 Months")) ?>', <?php mp_st_stat_items('-0 months', 'total'); ?>],
            ]);
            var options = {
              title: '<?php _e('Anzahl der Produktverkäufe, 12 Monate', 'mp_st'); ?>',
              colors: ['#000000', '#D44413'],
              theme: {legend: {position: 'in'}, axisTitlesPosition: 'in'},
              hAxis: {title: '<?php _e('Jahr', 'mp_st'); ?>', titleTextStyle: {color: '#999999'}},
              seriesType: "line",
              // curveType: "function",
              series: {1: {type: "line"}}
            };
            var chart = new google.visualization.ComboChart(document.getElementById('total_chart_items'));
            chart.draw(data, options);
          }
        </script>
        <div id="total_chart_items" style="width: 48%; height: 350px; display: inline-block;"></div>

        <script type="text/javascript">
          google.load("visualization", "1", {packages:["corechart"]});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = google.visualization.arrayToDataTable([
              ['<?php _e('Monat', 'mp_st'); ?>', '<?php _e('Durchschnittlich', 'mp_st'); ?>'],
              ['<?php echo date("M",strtotime("-12 Months")) ?>', <?php mp_st_stat_items('-12 months', 'average'); ?>],
              ['<?php echo date("M",strtotime("-11 Months")) ?>', <?php mp_st_stat_items('-11 months', 'average'); ?>],
              ['<?php echo date("M",strtotime("-10 Months")) ?>', <?php mp_st_stat_items('-10 months', 'average'); ?>],
              ['<?php echo date("M",strtotime("-9 Months")) ?>', <?php mp_st_stat_items('-9 months', 'average'); ?>],
              ['<?php echo date("M",strtotime("-8 Months")) ?>', <?php mp_st_stat_items('-8 months', 'average'); ?>],
              ['<?php echo date("M",strtotime("-7 Months")) ?>', <?php mp_st_stat_items('-7 months', 'average'); ?>],
              ['<?php echo date("M",strtotime("-6 Months")) ?>', <?php mp_st_stat_items('-6 months', 'average'); ?>],
              ['<?php echo date("M",strtotime("-5 Months")) ?>', <?php mp_st_stat_items('-5 months', 'average'); ?>],
              ['<?php echo date("M",strtotime("-4 Months")) ?>', <?php mp_st_stat_items('-4 months', 'average'); ?>],
              ['<?php echo date("M",strtotime("-3 Months")) ?>', <?php mp_st_stat_items('-3 months', 'average'); ?>],
              ['<?php echo date("M",strtotime("-2 Months")) ?>', <?php mp_st_stat_items('-2 months', 'average'); ?>],
              ['<?php echo date("M",strtotime("-1 Months")) ?>', <?php mp_st_stat_items('-1 months', 'average'); ?>],
              ['<?php echo date("M",strtotime("-0 Months")) ?>', <?php mp_st_stat_items('-0 months', 'average'); ?>],
            ]);
            var options = {
              title: '<?php _e('Anzahl der Produktverkäufe, 12 Months', 'mp_st'); ?>',
              colors: ['#000000', '#D44413'],
              theme: {legend: {position: 'in'}, axisTitlesPosition: 'in'},
              hAxis: {title: '<?php _e('Jahr', 'mp_st'); ?>', titleTextStyle: {color: '#999999'}},
              seriesType: "line",
              // curveType: "function",
              series: {1: {type: "line"}}
            };
            var chart = new google.visualization.ComboChart(document.getElementById('average_chart_items'));
            chart.draw(data, options);
          }
        </script>
        <div id="average_chart_items" style="width: 48%; height: 350px; display: inline-block;"></div>

  <?php
  function mp_st_popular_products_sales_price_all( $echo = true ) {
    global $mp;
    //The Query
    $custom_query = new WP_Query('post_type=product&post_status=publish&meta_key=mp_sales_count&meta_compare=>&meta_value=0&orderby=meta_value&order=DESC');
    if (count($custom_query->posts)) {
      foreach ($custom_query->posts as $post) {
        echo "[" . mp_st_sales_by_price(false, $post->ID) . "], ";
      ;}
    }
  }

  function mp_st_sales_by_price( $echo = true, $post_id = NULL, $label = true ) {
    global $id, $mp;
    $post_id = ( NULL === $post_id ) ? $id : $post_id;

	  $meta = get_post_custom($post_id);
    //unserialize
    foreach ($meta as $key => $val) {
	  $meta[$key] = maybe_unserialize($val[0]);
	  if (!is_array($meta[$key]) && $key != "mp_is_sale" && $key != "mp_track_inventory" && $key != "mp_product_link" && $key != "mp_file" && $key != "mp_price_sort")
	    $meta[$key] = array($meta[$key]);
	}
    if ((is_array($meta["mp_price"]) && count($meta["mp_price"]) >= 1) || !empty($meta["mp_file"])) {
      if ($meta["mp_is_sale"]) {
	    $price .= $meta["mp_sale_price"][0];
	  } else {
	    $price = $meta["mp_price"][0];
	  }
	} else {
		return '';
	}

    $sales = $meta["mp_sales_count"][0];
    $stats = $price . ', ' . $sales;
    if ($echo)
      echo $stats;
    else
      return $stats;
  } ?>

    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['<?php _e('Preis', 'mp_st'); ?>', '<?php _e('Umsatz', 'mp_st'); ?>'],
          <?php mp_st_popular_products_sales_price_all(); ?>
          ]);

        var options = {
          title: '<?php _e('Umsatz nach Produkt Preis', 'mp_st'); ?>',
          hAxis: {title: '<?php _e('Preis', 'mp_st'); ?>'},
          vAxis: {title: '<?php _e('Umsatz', 'mp_st'); ?>'},
          pointSize: '9',
          colors: ['#000000'],
          legend: 'none'
        };

        var chart = new google.visualization.ScatterChart(document.getElementById('sales_per_price'));
        chart.draw(data, options);
      }
    </script>
    <div id="sales_per_price" style="width: 47%; height: 300px; display: inline-block;"></div>

  <?php
  function mp_st_products_income_price_all( $echo = true ) {
    global $mp;
    //The Query
    $custom_query = new WP_Query('post_type=product&post_status=publish&meta_key=mp_sales_count&meta_compare=>&meta_value=0&orderby=meta_value&order=DESC');
    if (count($custom_query->posts)) {
      foreach ($custom_query->posts as $post) {
        echo "[" . mp_st_income_by_price(false, $post->ID) . "], ";
      ;}
    }
  }

  function mp_st_income_by_price( $echo = true, $post_id = NULL, $label = true ) {
    global $id, $mp;
    $post_id = ( NULL === $post_id ) ? $id : $post_id;

	$meta = get_post_custom($post_id);
    //unserialize
    foreach ($meta as $key => $val) {
	  $meta[$key] = maybe_unserialize($val[0]);
	  if (!is_array($meta[$key]) && $key != "mp_is_sale" && $key != "mp_track_inventory" && $key != "mp_product_link" && $key != "mp_file" && $key != "mp_price_sort")
	    $meta[$key] = array($meta[$key]);
	}
    if ((is_array($meta["mp_price"]) && count($meta["mp_price"]) >= 1) || !empty($meta["mp_file"])) {
      if ($meta["mp_is_sale"]) {
	    $price .= $meta["mp_sale_price"][0];
	  } else {
	    $price = $meta["mp_price"][0];
	  }
	  } else {
		return '';
	  }

    $sales = $meta["mp_sales_count"][0];
    $revenue = $sales*$price;
    $stats = $price . ', ' . $revenue;
    if ($echo)
      echo $stats;
    else
      return $stats;
  } ?>

    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['<?php _e('Preis', 'mp_st'); ?>', '<?php _e('Umsatz', 'mp_st'); ?>'],
          <?php mp_st_products_income_price_all(); ?>
          ]);

        var options = {
          title: '<?php _e('Umsatz nach Produktpreis', 'mp_st'); ?>',
          hAxis: {title: '<?php _e('Preis', 'mp_st'); ?>'},
          vAxis: {title: '<?php _e('Einnahmen', 'mp_st'); ?>'},
          colors: ['#D44413'],
          pointSize: '9',
          legend: 'none'
        };

        var chart = new google.visualization.ScatterChart(document.getElementById('income_price'));
        chart.draw(data, options);
      }
    </script>
  </head>
  <body>
    <div id="income_price" style="width: 48%; height: 300px; display: inline-block;"></div>

        <script type="text/javascript">
        google.load("visualization", "1", {packages:["corechart"]});
        google.setOnLoadCallback(drawChart);
        function drawChart() {
          var data = google.visualization.arrayToDataTable([
            ['<?php _e('Monat', 'mp_st'); ?>', '<?php _e('Umsatz', 'mp_st'); ?>'],
            ['<?php echo date("M",strtotime("-12 Months")) ?>', <?php mp_st_stat('-12 months', 'count'); ?>],
            ['<?php echo date("M",strtotime("-11 Months")) ?>', <?php mp_st_stat('-11 months', 'count'); ?>],
            ['<?php echo date("M",strtotime("-10 Months")) ?>', <?php mp_st_stat('-10 months', 'count'); ?>],
            ['<?php echo date("M",strtotime("-9 Months")) ?>', <?php mp_st_stat('-9 months', 'count'); ?>],
            ['<?php echo date("M",strtotime("-8 Months")) ?>', <?php mp_st_stat('-8 months', 'count'); ?>],
            ['<?php echo date("M",strtotime("-7 Months")) ?>', <?php mp_st_stat('-7 months', 'count'); ?>],
            ['<?php echo date("M",strtotime("-6 Months")) ?>', <?php mp_st_stat('-6 months', 'count'); ?>],
            ['<?php echo date("M",strtotime("-5 Months")) ?>', <?php mp_st_stat('-5 months', 'count'); ?>],
            ['<?php echo date("M",strtotime("-4 Months")) ?>', <?php mp_st_stat('-4 months', 'count'); ?>],
            ['<?php echo date("M",strtotime("-3 Months")) ?>', <?php mp_st_stat('-3 months', 'count'); ?>],
            ['<?php echo date("M",strtotime("-2 Months")) ?>', <?php mp_st_stat('-2 months', 'count'); ?>],
            ['<?php echo date("M",strtotime("-1 Months")) ?>', <?php mp_st_stat('-1 months', 'count'); ?>],
            ['<?php echo date("M",strtotime("-0 Months")) ?>', <?php mp_st_stat('-0 months', 'count'); ?>],
          ]);
          var options = {
            title: '<?php _e('Anzahl der Verkäufe, 12 Monate', 'mp_st'); ?>',
            colors: ['#000000'],
            theme: {legend: {position: 'in'}, titlePosition: 'in', axisTitlesPosition: 'in'},
            hAxis: {title: '<?php _e('Jahr', 'mp_st'); ?>', titleTextStyle: {color: '#999999'}}
          };
          var chart = new google.visualization.LineChart(document.getElementById('count_chart'));
          chart.draw(data, options);
        }
        </script>
        <div id="count_chart" style="width: 100%; height: 200px;"></div>
        </div>
      </td>
      <td style="width: 300px; vertical-align: top; text-align: center; color: #222;">
      	<div id="BigText" style="width: 300px; padding: 20px;">
      		<p><?php _e("Einnahmen dieses Monats:", "mp_st"); ?></p>
      		<p><strong><?php echo mp_format_currency('', mp_st_stat('-0 months', 'total', false)); ?></strong></p>
      		<p style="border-top: 1px solid #dedede;"><?php _e("Die Verkäufe dieses Monats:", "mp_st"); ?></p>
      		<p><strong><?php echo mp_st_stat('-0 months', 'count', false); ?> <?php _e('Verkäufe', 'mp_st'); ?>, <?php echo mp_st_stat_items('-0 months', 'total', false); ?> <?php _e('Artikel', 'mp_st'); ?></strong></p>
      		<p>(<?php _e('Durchschnitt von', 'mp_st'); ?> <?php echo number_format(mp_st_stat_items('-0 months', 'average', false), 2, '.', ''); ?> <?php _e('Artikel pro Verkauf', 'mp_st'); ?>)</p>
            <p style="border-top: 1px solid #dedede;"><?php _e("Durchschnitt dieses Monats:", "mp_st"); ?></p>
            <p><strong><?php echo mp_format_currency('', mp_st_stat('-0 months', 'average', false)); ?>/<?php _e('Umsatz', 'mp_st'); ?></strong></p>

      		<p style="border-top: 2px solid #333;"><?php _e('Gesamtumsatz:', 'mp_st'); ?></p>
      		<p><strong><?php echo mp_format_currency('', $totalitytotal); ?></strong></p>
      		<p style="border-top: 1px solid #dedede;"><?php _e('Gesamtumsatz:', 'mp_st'); ?></p>
      		<p><strong><?php echo $totalitycount; ?> <?php _e('Verkäufe', 'mp_st'); ?>, <?php echo $totalityitemstotal; ?> <?php _e('Artikel', 'mp_st'); ?></strong></p>
      		<p>(<?php _e('Durchschnitt von', 'mp_st'); ?> <?php echo number_format($totalityitemsaverage, 2, '.', ''); ?> <?php _e('Artikel pro Bestellung', 'mp_st'); ?>)</p>
            <p style="border-top: 1px solid #dedede;"><?php _e('Gesamtdurchschnitt/Verkauf:', 'mp_st'); ?></p>
            <p><strong><?php echo mp_format_currency('', $totalityaverage); ?></strong></p>      	</div>
      </td>
    </tr>
  </table>
  <?php
  function mp_st_popular_products_sales( $echo = true, $num = 10 ) {
    global $mp;
    //The Query
    $custom_query = new WP_Query('post_type=product&post_status=publish&posts_per_page='.intval($num).'&meta_key=mp_sales_count&meta_compare=>&meta_value=0&orderby=meta_value&order=DESC');
    if (count($custom_query->posts)) {
      foreach ($custom_query->posts as $post) {
        echo "['" . $post->post_title . "', " . mp_st_product_sales(false, $post->ID) . "], ";
      ;}
    }
  }

  function mp_st_popular_products_revenue( $echo = true) {
    global $mp;
    //The Query
    $custom_query = new WP_Query('post_type=product&post_status=publish&meta_key=mp_sales_count&meta_compare=>&meta_value=0&orderby=meta_value&order=DESC');
    if (count($custom_query->posts)) {
      foreach ($custom_query->posts as $post) {
        echo "['" . $post->post_title . "', " . mp_st_product_revenue(false, $post->ID) . "], ";
      ;}
    }
  }

  function mp_st_popular_products_revenue_table( $echo = true, $num = 10 ) {
    global $mp;
    //The Query
    $custom_query = new WP_Query('post_type=product&post_status=publish&posts_per_page='.intval($num).'&meta_key=mp_sales_count&meta_compare=>&meta_value=0&orderby=meta_value&order=DESC');
    if (count($custom_query->posts)) {
      foreach ($custom_query->posts as $post) {
        echo "['" . $post->post_title . "', {v:" . mp_st_product_revenue(false, $post->ID) . ", f:'" . mp_st_product_revenue(false, $post->ID) . "'}, {v:" . mp_st_product_sales(false, $post->ID) . ", f:'" . mp_st_product_sales(false, $post->ID) . "'}], ";
      ;}
    }
  }

  function mp_st_product_revenue( $echo = true, $post_id = NULL, $label = true ) {
    global $id, $mp;
    $post_id = ( NULL === $post_id ) ? $id : $post_id;

	$meta = get_post_custom($post_id);
    //unserialize
    foreach ($meta as $key => $val) {
	  $meta[$key] = maybe_unserialize($val[0]);
	  if (!is_array($meta[$key]) && $key != "mp_is_sale" && $key != "mp_track_inventory" && $key != "mp_product_link" && $key != "mp_file" && $key != "mp_price_sort")
	    $meta[$key] = array($meta[$key]);
	}
    if ((is_array($meta["mp_price"]) && count($meta["mp_price"]) >= 1) || !empty($meta["mp_file"])) {
      if ($meta["mp_is_sale"]) {
	    $price .= $meta["mp_sale_price"][0];
	  } else {
	    $price = $meta["mp_price"][0];
	  }
	} else {
	  return '';
	}

    $sales = $meta["mp_sales_count"][0];
    $revenue = $sales*$price;
    if ($echo)
      echo $revenue;
    else
      return $revenue;
  }

  function mp_st_product_sales( $echo = true, $post_id = NULL ) {
    global $id, $mp;
    $post_id = ( NULL === $post_id ) ? $id : $post_id;
    $meta = get_post_custom($post_id);
    $sales = $meta["mp_sales_count"][0];

    if ($echo)
      echo $sales;
    else
      return $sales;
  }
  function mp_st_users() {
  	global $wpdb;
  	$order = 'postcount';
  	$limit = '10';
  	$usersinfo = $wpdb->get_results("SELECT $wpdb->users.ID as ID, COUNT(post_author) as postcount FROM $wpdb->users LEFT JOIN $wpdb->posts ON $wpdb->users.ID = $wpdb->posts.post_author WHERE post_type = 'mp_order' GROUP BY post_author ORDER BY $order DESC LIMIT $limit");
  	foreach($usersinfo as $userinfo){
  	  $user = get_userdata($userinfo->ID);
  	  $user->postcount = $userinfo->postcount;
      echo "['";
      echo $user->mp_shipping_info['name'];
	  echo "', '";
	  echo $user->mp_shipping_info['city'];
	  echo "', '";
	  echo $user->mp_shipping_info['country'];
	  echo "', '";
	  echo $user->mp_shipping_info['phone'];
	  echo "', '";
	  echo $user->mp_shipping_info['email'];
      echo "', {v:";
      echo $user->postcount;
      echo ", f:'";
      echo $user->postcount;
      echo "'}], ";
	}
  }

  ?>
             <script type="text/javascript">
              google.load("visualization", "1", {packages:["corechart"]});
              google.setOnLoadCallback(drawChart);
              function drawChart() {
                var data = google.visualization.arrayToDataTable([
                  ['<?php _e('Produkt', 'mp_st'); ?>', '<?php _e('Verkäufe', 'mp_st'); ?>'],

                  <?php mp_st_popular_products_sales(); ?>
                ]);
                var options = {
                  title: '<?php _e('Top-Produkte nach Anzahl der Verkäufe', 'mp_st'); ?>',
                  is3D: 'true',
                };
                var chart = new google.visualization.PieChart(document.getElementById('top_products_pie'));
                chart.draw(data, options);
              }
            </script>
            <div id="top_products_pie" style="width: 45%; height: 500px; display: inline-block;"></div>

             <script type="text/javascript">
              google.load("visualization", "1", {packages:["corechart"]});
              google.setOnLoadCallback(drawChart);
              function drawChart() {
                var data = google.visualization.arrayToDataTable([
                  ['<?php _e('Produkt', 'mp_st'); ?>', '<?php _e('Einnahmen', 'mp_st'); ?>'],

                  <?php mp_st_popular_products_revenue(); ?>
                ]);
                var options = {
                  title: '<?php _e('Top-Produkte Umsatz', 'mp_st'); ?>',
                };
                var chart = new google.visualization.PieChart(document.getElementById('top_products_revenue'));
                chart.draw(data, options);
              }
            </script>
            <div id="top_products_revenue" style="width: 50%; height: 500px; display: inline-block;"></div>

             <script type="text/javascript">
              google.load('visualization', '1', {packages:['table']});
              google.setOnLoadCallback(drawTable);
              function drawTable() {
                var data = new google.visualization.DataTable();
                data.addColumn('string', '<?php _e('Produktname', 'mp_st'); ?>');
                data.addColumn('number', '<?php _e('Gesamtumsatz', 'mp_st'); ?>');
                data.addColumn('number', '<?php _e('Produktverkäufe', 'mp_st'); ?>');
                data.addRows([

                  <?php mp_st_popular_products_revenue_table(); ?>
                ]);
                var options = {
                };
                var table = new google.visualization.Table(document.getElementById('top_products_table'));
                table.draw(data, {showRowNumber: true});
              }
            </script>
            <div id="top_products_table" style="width: 100%; height: 500px; display: block;"></div>

             <script type="text/javascript">
              google.load('visualization', '1', {packages:['table']});
              google.setOnLoadCallback(drawTable);
              function drawTable() {
                var data = new google.visualization.DataTable();
                data.addColumn('string', '<?php _e('Kundenname', 'mp_st'); ?>');
                data.addColumn('string', '<?php _e('Stadt', 'mp_st'); ?>');
                data.addColumn('string', '<?php _e('Land', 'mp_st'); ?>');
                data.addColumn('string', '<?php _e('Telefon', 'mp_st'); ?>');
                data.addColumn('string', '<?php _e('Email', 'mp_st'); ?>');
                data.addColumn('number', '<?php _e('Bestellungen insgesamt', 'mp_st'); ?>');
                data.addRows([

                  <?php mp_st_users(); ?>
                ]);
                var options = {
                };
                var table = new google.visualization.Table(document.getElementById('top_users_table'));
                table.draw(data, {showRowNumber: true});
              }
            </script>
            <div id="top_users_table" style="width: 100%; height: 500px; display: block;"></div>

  </div>
  <script>
    (function ($) {
      $('#BigText').bigtext({
  	    childSelector: '> p',
  	    maxfontsize: 110
      });
    })(jQuery);
  </script>
  <style>
  	#BigText p strong{text-shadow: 2px 2px 2px #ccc; filter: dropshadow(color=#ccc, offx=2, offy=2);}
  </style>
  <?php
  }
}
  

