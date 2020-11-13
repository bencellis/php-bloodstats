<?php
include_once('includes/header.php');

include_once('includes/navigation.php');

if (!empty($_REQUEST['reprocess'])) {
    reprocess_input();
}

list($page, $pagedays) = processPageParams();

$statistics = get_blood_stats($page, $pagedays, 'DESC');

?>

    <div class="container-fluid">
      <div class="row">
        <div class="col-12">
        	<h1>Blood Statistics</h1>
        </div>
      </div>
<?php foreach ($statistics as $date => $stats): ?>
    	<div class="row border border-primary rounded m-3">
    		<div class="col-12">
    			<div class="row">
    				<div class="col-12 panel-heading">
    					<h4><?php echo $date?></h4>
    				</div>
    			</div>
    			<div class="row">
    				<div class="col-3 border border-secondary m-1">
    					<?php if (!empty($stats['medication'])): ?>
    						<?php include('includes/medication_snippet.php'); ?>
						<?php endif; ?>
    				</div>
    				<div class="col-2 border border-secondary m-1">
    					<?php include('includes/alcohol_snippet.php'); ?>
    				</div>
    				<div class="col border border-secondary m-1">
    					<?php include('includes/bs_snippet.php'); ?>
    				</div>
    				<div class="col-4 border border-secondary m-1">
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
