<?php
include_once ('includes/header.php');

include_once ('includes/navigation.php');

list($page, $pagedays) = processPageParams();

$statistics = get_blood_stats($page, $pagedays);

$bpstats = null;
switch($_REQUEST['scope']) {
    case 'bpstats' :
        $bpstats = true;
        break;
    case 'bp3' :
        $bpstats = 'bp3';
        break;
    case 'bp2' :
        $bpstats = 'bp2';
}


$graphimage = get_stats_graph($statistics, 'bsstats', true, $bpstats, null, false);
// switch stats order
$statistics = array_reverse($statistics, true);

?>
    <div class="container">
      <div class="row">
        <div class="col-12">
        	<h1>Statistics Graph</h1>
        </div>
      </div>
    </div>
    <div class="container-fluid">
      <div class="row">
        <div class="col-12 text-center">
        	<img src="<?php echo $graphimage; ?>" />
        </div>
      </div>
    </div>
    <div class="container">
    <?php include_once 'includes/filter_snippet.php';?>
      <div class="row">
        <div class="col-12">
        	<h2>Source Statistics</h2>
        </div>
      </div>
<?php foreach ($statistics as $date => $stats): ?>
	<?php
    	if (empty($stats['bs'])) {
    	    continue;
    	}
	?>
	<div class="row border border-primary rounded m-3">
		<div class="col-12">
			<div class="row">
				<div class="col-12 panel-heading">
					<h4><?php echo $date?></h4>
				</div>
			</div>
			<div class="row">
				<div class="col-3 border border-secondary m-1">
					<?php include('includes/bs_snippet.php'); ?>
				</div>
				<div class="col border border-secondary m-1">
					<?php include('includes/bp_snippet.php'); ?>
				</div>
			</div>
		</div>
	</div>
<?php endforeach; ?>
	<?php include_once('includes/pagingbar_snippet.php'); ?>
    </div> <!-- /container -->

    <hr>

<?php include_once('includes/footer.php'); ?>
