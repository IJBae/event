<!DOCTYPE html>
<html lang="ko">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<?php if ($this->landing['device_width']) { ?>
		<meta name="viewport" content="width=<?= $this->landing['device_width'] ?>, user-scalable=no, target-densitydpi=device-dpi" />
	<?php } else { ?>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, minimal-ui, viewport-fit=cover" />
	<?php } ?>
	<meta http-equiv="X-UA-Compatible" content="IE=Edge" /><!-- IE최신 -->
	<?php if($this->landing['hide_meta'] != 1) { ?>
	<!-- Facebook OpenGraph -->
	<meta property="og:title" content="<?php echo $this->landing['title']; ?>" />
	<meta property="og:type" content="website" />
	<meta property="og:url" content="https://<?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>" />
	<?php /* ?><meta property="og:site_name" content="<?php echo $this->landing['name']; ?>" /><?php 야호맨 때문에 숨김 */ ?>
	<meta property="og:description" content="<?php echo $this->landing['subtitle']; ?>" />
	<meta property="og:locale:alternate" content="ko_KR" />

	<meta name="subject" content="<?php echo $this->landing['title']; ?>" />
	<meta name="description" content="<?php echo $this->landing['subtitle']; ?>">
	<meta name="content-language" content="kr" />
	<?php } ?>
	<title></title>
	<script src="//static.hotblood.co.kr/libs/jquery/1.12.4/jquery.min.js"></script>
	<script src="//static.hotblood.co.kr/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
	<script src="<?php echo EVENT_URL; ?>/common.js?ver=<?php echo date('YmdHi'); ?>"></script>
	<script src="<?php echo EVENT_URL; ?>/static/js/hangul.js"></script>
	<link type="text/css" href="//static.hotblood.co.kr/libs/jqueryui/1.12.1/jquery-ui.min.css" rel="stylesheet" />
	<link type="text/css" href="//static.hotblood.co.kr/libs/animate/4.1.0/animate.min.css" rel="stylesheet" />
	<link type="text/css" href="<?php echo EVENT_URL; ?>/common.css?ver=<?php echo date('YmdHi'); ?>" rel="stylesheet" />
	<script>
	window.hbEvent = window.hbEvent || [];
	$(function() {
		var information = {
			<?php /* ?>
			'advertiser' : '<?php echo addslashes($this->landing['name'] ?? '');?>',
			'agent' : '<?php echo addslashes($this->landing['agent'] ?? '');?>',
			<?php 야호맨 때문에 숨김 */?>
			'media' : '<?php echo addslashes($this->landing['media'] ?? '');?>',
			'no' : '<?php echo addslashes($this->landing['seq'] ?? '');?>',
			'site' : '<?php echo addslashes($_GET['site'] ?? '');?>',
			'title' : '<?php echo addslashes($this->landing['title'] ?? '');?>',
			'action' : '<?php echo addslashes($this->method ?? '');?>',
		}
		hbEvent.push(information);
	});
	</script>
	<!-- Google Tag Manager -->
	<script>
		(function(w, d, s, l, i) {
			w[l] = w[l] || [];
			w[l].push({
				'gtm.start': new Date().getTime(),
				event: 'gtm.js'
			});
			var f = d.getElementsByTagName(s)[0],
				j = d.createElement(s),
				dl = l != 'dataLayer' ? '&l=' + l : '';
			j.async = true;
			j.src =
				'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
			f.parentNode.insertBefore(j, f);
		})(window, document, 'script', 'dataLayer', 'GTM-NM28J73');
	</script>
	<!-- End Google Tag Manager -->
	<!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=G-7V2Y5GXTTR"></script>
	<script>
		window.dataLayer = window.dataLayer || [];

		function gtag() {
			dataLayer.push(arguments);
		}
		gtag('js', new Date());
		gtag('config', 'G-7V2Y5GXTTR', {
			<?php /* ?>
			'advertiser' : '<?php echo addslashes($this->landing['name'] ?? '');?>',
			'agent' : '<?php echo addslashes($this->landing['agent'] ?? '');?>',
			<?php 야호맨 때문에 숨김 */?>
			'media' : '<?php echo addslashes($this->landing['media'] ?? '');?>',
			'event_no' : '<?php echo addslashes($this->landing['seq'] ?? '');?>',
			'site' : '<?php echo addslashes($_GET['site'] ?? '');?>',
			'title' : '<?php echo addslashes($this->landing['title'] ?? '');?>',
			'action' : '<?php echo addslashes($this->method ?? '');?>',
			'linker': {
				'accept_incoming': true
			}
		});
	</script>
</head>

<body>
	<!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-NM28J73" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<!-- End Google Tag Manager (noscript) -->
	<h1 class="alignCenter blind"><?php echo $this->landing['subtitle'] ?? ''; ?></h1>