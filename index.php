<?php
include_once('includes/header.php');

include_once('includes/navigation.php');

if (!empty($_REQUEST['reprocess'])) {
    reprocess_input();
}

$intervaldays = 40;

$statistics = get_blood_stats($intervaldays);

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
				<div class="col border border-secondary m-1">
					<div class="row">
						<div class="col-12">
							<b>Alcohol</b>
						</div>
					</div>
					<div class="row">
						<?php if (empty($stats['alcohol'])): ?>
    						<div class="col-12">
    							No Statistics!!!
    						</div>
    					<?php else :?>
    						<div class="col-12">
    							<?php if ($stats['alcohol']['unknown']): ?>
    								Unknown
    							<?php else: ?>
    								<?php echo $stats['alcohol']['units'] . 'u'; ?>
        							<?php if ($stats['alcohol']['estimate']): ?>
        								&nbsp;Estimated.
									<?php endif; ?>
    							<?php endif; ?>
    						</div>
    					<?php endif; ?>
					</div>
				</div>
				<div class="col border border-secondary m-1">
					<div class="row">
						<div class="col-12">
							<b>Medication</b>
						</div>
					</div>
					<div class="row">
						<div class="col-12">
							<div class="row">
								<div class="col-6">
									<b>Name</b>
								</div>
								<div class="col-2">
									<b>AM</b>
								</div>
								<div class="col-2">
									<b>Mid.</b>
								</div>
								<div class="col-2">
									<b>PM</b>
								</div>
							</div>
							<?php if (!empty($stats['medication'])): ?>
    							<?php foreach ($stats['medication'] as $medication) :?>
    								<div class="row">
    									<div class="col-6">
    										<?php echo $medication['medication']; ?>
    									</div>
    									<div class="col-2">
    										<?php if (!empty($medication['AM'])): ?>
    											<?php echo $medication['AM']; ?>
    										<?php else:?>
    											&nbsp;
    										<?php endif; ?>
    									</div>
    									<div class="col-2">
    										<?php if (!empty($medication['Midday'])): ?>
    											<?php echo $medication['Midday']; ?>
    										<?php else:?>
    											&nbsp;
    										<?php endif; ?>
    									</div>
    									<div class="col-2">
    										<?php if (!empty($medication['PM'])): ?>
    											<?php echo $medication['PM']; ?>
    										<?php else:?>
    											&nbsp;
    										<?php endif; ?>
    									</div>
    								</div>
    							<?php endforeach;?>
    						<?php else: ?>
								<div class="row">
									<div class="col-12">
										&nbsp;
									</div>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<div class="col-6 border border-secondary m-1">
					<div class="row">
						<div class="col-12">
							<b>Bloods Stats (NOTE: Day After)</b>
						</div>
					</div>
					<div class="row">
						<div class="col-12">
							<div class="row">
								<!--  thedate bstime bs bptime BP3avg BP2avg -->
								<div class="col-2">
									<b>BS Time</b>
								</div>
								<div class="col-2">
									<b>BS mmo/L</b>
								</div>
								<div class="col-2">
									<b>BP Time</b>
								</div>
								<div class="col-3">
									<b>BP 3 Avg</b>
								</div>
								<div class="col-3">
									<b>BP 2 Avg</b>
								</div>
							</div>
							<?php foreach ($stats['bloods'] as $bloodstats): ?>
								<div class="row">
									<!--  thedate bstime bs bptime BP3avg BP2avg -->
									<div class="col-2">
										<?php echo $bloodstats['bstime']; ?>
									</div>
									<div class="col-2">
										<?php echo $bloodstats['bsreading']; ?>
									</div>
									<div class="col-2">
										<?php echo $bloodstats['bptime']; ?>
									</div>
									<div class="col-3">
										<?php echo $bloodstats['BP3avg']; ?>
									</div>
									<div class="col-3">
										<?php echo $bloodstats['BP2avg']; ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php endforeach; ?>
    </div> <!-- /container -->

    <hr>

<?php include_once('includes/footer.php'); ?>
