					<div class="row">
						<div class="col-12">
							<b>Bloods Glucose (NOTE: Day After)</b>
						</div>
					</div>
					<div class="row">
						<div class="col-12">
							<div class="row">
								<div class="col">
									<b>BS Time</b>
								</div>
								<div class="col">
									<b>BS mmo/L</b>
								</div>
							</div>
							<?php foreach ($stats['bs'] as $bloodstats): ?>
								<div class="row">
									<!--  thedate bstime bs bptime BP3avg BP2avg -->
									<div class="col">
										<?php echo $bloodstats['bstime']; ?>
									</div>
									<div class="col">
										<?php echo $bloodstats['bsreading']; ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
