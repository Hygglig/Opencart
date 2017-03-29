<?php echo $header; ?>
<div class="container">
	<ul class="breadcrumb">
	<?php foreach ($breadcrumbs as $breadcrumb) { ?>
		<li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
	<?php } ?>
	</ul>
	<div class="row">
		<div id="content"><?php echo $content_top; ?>
			<div class="col-sm-6">
				<?php echo $hyggligHtml; ?>
			</div>
			<div class="col-sm-6" style="margin-top:10%;">
				<?php echo $text_message; ?>
				<div class="buttons">
					<div class="pull-right">
						<a href="<?php echo $continue; ?>" class="btn btn-primary"><?php echo $button_continue; ?></a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php echo $footer; ?>