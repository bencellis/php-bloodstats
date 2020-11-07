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
// <a class="dropdown-item" href="#">Alcohol vs BP</a>
// <a class="dropdown-item" href="#">Alcohol vs BS</a>
// <div class="dropdown-divider"></div>
// <a class="dropdown-item" href="#">Alcohol vs BP (3 Avg)</a>
// <a class="dropdown-item" href="#">Alcohol vs BP (2 Avg)</a>
$bsstats = false;
$bpstats = null;
switch($_REQUEST['scope']) {
    case 'bsstats' :
        $bsstats = true;
        break;
    case 'bpstats' :
        $bpstats = true;
        break;
    case 'bp3' :
        $bpstats = 'bp3';
        break;
    case 'bp2' :
        $bpstats = 'bp2';
}

$graphimage = get_stats_graph($statistics, 'alcohol', $bsstats, $bpstats, null, true);

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
					<?php include('includes/alcohol_snippet.php'); ?>
				</div>
				<div class="col border border-secondary m-1">
					<?php
    					if ($bsstats) {
    					   include('includes/bs_snippet.php');
    					} else {
    					    include('includes/bp_snippet.php');
    					}
					?>
				</div>
			</div>
		</div>
	</div>
<?php endforeach; ?>
    </div> <!-- /container -->

    <hr>

<?php include_once('includes/footer.php'); ?>
