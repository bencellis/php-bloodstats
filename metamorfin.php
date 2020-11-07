<?php
include_once ('includes/header.php');

include_once ('includes/navigation.php');

list($page, $perpage, $filter) = processPageParams();

$statistics = get_blood_stats($page, $perpage, $filter);
/*
 * $primaryy = bsstats | bpstats | medication | alcohol
 * $bsstats = true | false
 * $bpstats = true | bp3 | bp2
 * $medication = true | $medicine
 * $alcohol = true | false
 *
 */
// <a class="dropdown-item" href="/bloodstats/metamorfin.php?scope=bsstats">Metamorfin vs BS</a>
// <a class="dropdown-item" href="/bloodstats/metamorfin.php?scope=bsstats-al">Metamorfin vs BS vs Alcohol</a>
$alcohol = false;
$bsstats = null;
switch($_REQUEST['scope']) {
    case 'bsstats' :
        $bsstats = true;
        break;
    case 'bsstats-al' :
        $bsstats = true;
        $alcohol = true;
        break;
}

$activemedication = 'Metamorfin';
$graphimage = get_stats_graph($statistics, 'medication', $bsstats, false, $activemedication, $alcohol);

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
	if (empty($stats['medication'])) {
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
				<div class="col border border-secondary m-1">
					<?php include('includes/medication_snippet.php'); ?>
				</div>
				<div class="col border border-secondary m-1">
					<?php include('includes/alcohol_snippet.php'); ?>
				</div>
				<div class="col-7 border border-secondary m-1">
					<?php include('includes/bp_snippet.php'); ?>
				</div>
			</div>
		</div>
	</div>
<?php endforeach; ?>
    </div> <!-- /container -->

    <hr>

<?php include_once('includes/footer.php'); ?>