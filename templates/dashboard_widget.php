<?php
/**
 * Dashboard Widget Template
 */
 
if ( ! defined( 'ABSPATH' ) ) exit; /* don't allow direct access to the template! */ ?>

<div class="table table_content">
	<p class="sub">Lifetime</p>
	<table>
		<tbody>
			<?php if($lifetime_metrics ==false): ?>
			<p>Please Enter Metrics in the <i><b>Lifetime</b></i> metrics field in <a href="<?php echo get_admin_url(); ?>options-general.php?page=logmycalls-settings">LogMyCalls Settings</a></p>
			<?php else: ?>
				<?php foreach($lifetime_metrics as $metric): ?>
				<tr class="first">
					<td class="first b b-posts">
						<span>
							<?php echo $metric->value(); ?>
						</span>
					</td>
					<td class="t posts">
						<span>
							<?php echo $metric->label(); ?>
						</span>
					</td>
				</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
<div class="table table_discussion">
	<p class="sub">Today</p>
	<table>
		<tbody>
			<?php if($today_metrics ==false): ?>
			<p>Please Enter Metrics in the <i><b>Today</b></i> metrics field in <a href="<?php echo get_admin_url(); ?>options-general.php?page=logmycalls-settings">LogMyCalls Settings</a></p>
			<?php else: ?>
				<?php foreach($today_metrics as $metric): ?>
				<tr class="first">
					<td class="first b b-posts">
						<span>
							<?php echo $metric->value(); ?>
						</span>
					</td>
					<td class="t posts">
						<span>
							<?php echo $metric->label(); ?>
						</span>
					</td>
				</tr>
				<?php endforeach; ?>
			<?php endif; ?>

		</tbody>

	</table>
</div>

<br class="clear">
