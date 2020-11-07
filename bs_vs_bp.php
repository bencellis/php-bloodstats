<?php
include_once ('includes/header.php');

include_once ('includes/navigation.php');

list($page, $perpage, $filter) = processPageParams();

$statistics = get_blood_stats($page, $perpage, $filter);

/*
 * $primaryy = bsstats | bpstats | medication | alcohol
 * $bsstats = true | false
 * $bpstats = true | bp3 | bp2
 * $medication = true | $medicine |
 * $alcohol = true | false
 *
 */

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

?>
    <div class="container-fluid">
      <div class="row">
        <div class="col-12 text-center">
        	<img src="<?php echo $graphimage; ?>" />
        </div>
      </div>
    </div>
    <div class="container">
      <div class="row">
        <div class="col-12">
        	<h1>Blood Statistics</h1>
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
    </div> <!-- /container -->

    <hr>

<?php include_once('includes/footer.php'); ?>
