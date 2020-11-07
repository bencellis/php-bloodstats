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
							<?php foreach ($stats['bp'] as $bloodstats): ?>
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