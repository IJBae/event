<style>
body{background-color:#18173F;}
.result_landing{width:100%; max-width:<?php echo $data['maxWidth']+10;?>px; margin:0 auto;}
.result_landing .img_box{width:100%; margin:0 auto;}
.result_landing .img_box .img:first-child{padding:0 5px;}
.result_landing .img_box .img-sizer,
.result_landing .img_box .img{box-sizing:border-box; display:block; width:100%; background-color:transparent; padding:40px 5px 0;}
.result_landing .img_box .img.grid{width:100%;}
.result_landing .img_box .img.grid2{width:50%;}
.result_landing .img_box .img.noPadding{padding:0 5px;}
.result_landing .img_box .img a{display:block; width:100%;}
.result_landing .img_box .img img{width:100%;}
@media screen and (max-width: 890px){
	.result_landing .img_box .img{padding:15px 5px 0;}
}
</style>
<script src="https://unpkg.com/masonry-layout@4/dist/masonry.pkgd.min.js"></script>
<div class="result_landing">
	<div class="img_box">
		<?php foreach($data['data'] as $row) { ?>
		<div id="landing-<?php echo $row['no'];?>" class="img<?php if($row['image_info']['width']<$data['maxWidth']) echo ' grid2';?><?php if(!$row['link']) echo ' noPadding';?>">
			<?php if($row['link']) {?><a href="../go/<?php echo $row['category'];?>/<?php echo $row['no'];?>"><?php }?><img src="<?php echo $row['image'];?>"><?php if($row['link']) {?></a><?php }?>
		</div>
		<?php } ?>
	</div>
</div>
