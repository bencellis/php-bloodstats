<?php
include_once ('includes/header.php');

include_once ('includes/navigation.php');

$intervaldays = 6 * 7;     // 6 weeks

$statistics = get_blood_stats($intervaldays);

$graphimage = get_stats_graph($statistics, 'Lisinopril', true, true, false);

//die('<pre>' . print_r($statistics, true) . '</pre>');


?>
    <div class="container">
      <div class="row">
        <div class="col-12">
        	<img src="<?php echo $graphimage; ?>" />
        </div>
      </div>
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
					<div class="row">
						<div class="col-12">
							<b>Lisinopril</b>
						</div>
					</div>
					<div class="row">
						<div class="col-12">
							<div class="row">
								<div class="col">
									<b>AM</b>
								</div>
								<div class="col">
									<b>Mid.</b>
								</div>
								<div class="col">
									<b>PM</b>
								</div>
							</div>
							<?php foreach ($stats['medication'] as $medication) :?>
								<?php if ($medication['medication'] == 'Lisinopril'): ?>
    								<div class="row">
    									<?php
    									$totaltabs = $medication['AM'] + $medication['Midday'] + $medication['PM'];
    									?>
    									<?php if ($totaltabs): ?>
        									<div class="col">
        										<?php if (!empty($medication['AM'])): ?>
        											<?php echo $medication['AM']; ?>
        										<?php else:?>
        											0
        										<?php endif; ?>
        									</div>
        									<div cla/var/www/html/bloodstats/includes/ss="col">
        										<?php if (!empty($medication['Midday'])): ?>
        											<?php echo $medication['Midday']; ?>
        										<?php else:?>
        											0
        										<?php endif; ?>
        									</div>
        									<div class="col">
        										<?php if (!empty($medication['PM'])): ?>
        											<?php echo $medication['PM']; ?>
        										<?php else:?>
        											0
        										<?php endif; ?>
        									</div>
        								<?php else: ?>
    										<div class="col">
    											No medication taken.
    										</div>
            							<?php endif; ?>
    								</div>
    							<?php endif; ?>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
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
				<div class="col-7 border border-secondary m-1">
					<div class="row">
						<div class="col-12">
							<b>Bloods Stats (NOTE: Day After)</b>
						</div>
					</div>
					<div class="row">
						<div class="col-12">
							<div class="row">
								<!--  thedate bstime bs bptime BP3avg BP2avg -->
								<div class="col-3">
									<b>BP Time</b>
								</div>
								<div class="col">
									<b>BP 3 Avg</b>
								</div>
								<div class="col">
									<b>BP 2 Avg</b>
								</div>
							</div>
							<?php foreach ($stats['bloods'] as $bloodstats): ?>
								<div class="row">
									<!--  thedate bstime bs bptime BP3avg BP2avg -->
									<div class="col-3">
										<?php echo $bloodstats['bptime']; ?>
									</div>
									<div class="col">
										<?php echo $bloodstats['BP3avg']; ?>
									</div>
									<div class="col">
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
