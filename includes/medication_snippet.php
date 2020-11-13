					<div class="row">
						<div class="col-12">
							<?php if (empty($activemedication)) :?>
								<b>Medication</b>
							<?php else: ?>
								<b><?php echo $activemedication; ?></b>
							<?php endif; ?>
						</div>
					</div>
					<div class="row">
						<div class="col-12">
							<div class="row">
							<?php if (empty($activemedication)) :?>
								<div class="col-4">
									<b>Name</b>
								</div>
							<?php endif; ?>
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
							<?php if (!empty($stats['medication'])): ?>
    							<?php foreach ($stats['medication'] as $medication) :?>
    								<?php
    								if (!empty($activemedication) && $medication['medication'] != $activemedication) {
    								    continue;
    								}
    								?>
    								<div class="row">
    									<?php
    									$totaltabs = $medication['AM'] + $medication['Midday'] + $medication['PM'];
    									?>
    									<?php if (empty($activemedication)) :?>
        									<div class="col-4">
    											<?php echo $medication['medication']; ?>
        									</div>
        								<?php endif; ?>
    									<?php if ($totaltabs): ?>
        									<div class="col">
        										<?php if (!empty($medication['AM'])): ?>
        											<?php echo $medication['AM']; ?>
        										<?php else:?>
        											0
        										<?php endif; ?>
        									</div>
        									<div class="col">
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
    							<?php endforeach; ?>
							<?php endif; ?>
						</div>
					</div>