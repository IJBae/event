$("<link/>", {
	rel: "stylesheet",
	type: "text/css",
	href: "https://static.hotblood.co.kr/libs/jquery-nice-select/1.1.0/css/nice-select.css"
}).appendTo("head");
!function(e){ //niceSelect Plugin
	e.fn.niceSelect=function(t){function s(t){t.after(e("<div></div>").addClass("nice-select wide").addClass(t.attr("class")||"").addClass(t.attr("disabled")?"disabled":"").attr("tabindex",t.attr("disabled")?null:"0").html('<span class="current"></span><ul class="list"></ul>'));var s=t.next(),n=t.find("option"),i=t.find("option:selected");s.find(".current").html(i.data("display")||i.text()),n.each(function(t){var n=e(this),i=n.data("display");s.find("ul").append(e("<li></li>").attr("data-value",n.val()).attr("data-display",i||null).addClass("option"+(n.is(":selected")?" selected":"")+(n.is(":disabled")?" disabled":"")).html(n.text()))})}if("string"==typeof t)return"update"==t?this.each(function(){var t=e(this),n=e(this).next(".nice-select"),i=n.hasClass("open");n.length&&(n.remove(),s(t),i&&t.next().trigger("click"))}):"destroy"==t?(this.each(function(){var t=e(this),s=e(this).next(".nice-select");s.length&&(s.remove(),t.css("display",""))}),0==e(".nice-select").length&&e('form').off(".nice_select")):console.log('Method "'+t+'" does not exist.'),this;this.hide(),this.each(function(){var t=e(this);t.next().hasClass("nice-select")||s(t)}),e('form').off(".nice_select"),e('form').on("click.nice_select",".nice-select",function(t){var s=e(this);e(".nice-select").not(s).removeClass("open"),s.toggleClass("open"),s.hasClass("open")?(s.find(".option"),s.find(".focus").removeClass("focus"),s.find(".selected").addClass("focus")):s.focus()}),e('form').on("click.nice_select",function(t){0===e(t.target).closest(".nice-select").length&&e(".nice-select").removeClass("open").find(".option")}),e('form').on("click.nice_select",".nice-select .option:not(.disabled)",function(t){var s=e(this),n=s.closest(".nice-select");n.find(".selected").removeClass("selected"),s.addClass("selected");var i=s.data("display")||s.html();n.find(".current").html(i),n.prev("select").val(s.data("value")).trigger("change")}),e('form').on("keydown.nice_select",".nice-select",function(t){var s=e(this),n=e(s.find(".focus")||s.find(".list .option.selected"));if(32==t.keyCode||13==t.keyCode)return s.hasClass("open")?n.trigger("click"):s.trigger("click"),!1;if(40==t.keyCode){if(s.hasClass("open")){var i=n.nextAll(".option:not(.disabled)").first();i.length>0&&(s.find(".focus").removeClass("focus"),i.addClass("focus"))}else s.trigger("click");return!1}if(38==t.keyCode){if(s.hasClass("open")){var l=n.prevAll(".option:not(.disabled)").first();l.length>0&&(s.find(".focus").removeClass("focus"),l.addClass("focus"))}else s.trigger("click");return!1}if(27==t.keyCode)s.hasClass("open")&&s.trigger("click");else if(9==t.keyCode&&s.hasClass("open"))return!1});var n=document.createElement("a").style;return n.cssText="pointer-events:auto","auto"!==n.pointerEvents&&e("html").addClass("no-csspointerevents"),this}
}(jQuery);

var sojaeji = function(sido, gugun, dong, use_list) {
	if(typeof use_list == 'undefined')
		var use_list = [];
	var obj = this;
	//window.onload = function() {
		obj.sido = document.getElementById(sido);
		obj.gugun = document.getElementById(gugun);
		obj.dong = document.getElementById(dong);
		obj.update_sido(use_list);
		$('select[name="sido"],select[name="sido2"],select[name="sido3"],select[name="gugun"],select[name="gugun2"],select[name="gugun3"],select[name="dong"],select[name="dong2"],select[name="dong3"]').niceSelect();
		obj.sido.onchange = function() {
			obj.update_gugun.apply(obj);
			obj.update_dong.apply(obj);
			$('select[name="sido"],select[name="sido2"],select[name="sido3"],select[name="gugun"],select[name="gugun2"],select[name="gugun3"],select[name="dong"],select[name="dong2"],select[name="dong3"]').niceSelect('update');
		}
		obj.gugun.onchange = function() {
			obj.update_dong.apply(obj);
		}
	//}
}

sojaeji.prototype = {
	update_gugun : function() {
		if (this.gugun == null) return;
		var gugun = this[this.sido.value];
		this.gugun.innerHTML = "";
		for(var i=0; i<gugun.length; i++) {
			var addText = '';
			if(i && ((this.sido.value == '서울' && $.inArray(gugun[i], ['관악구', '도봉구', '성북구', '중랑구'])<0)
				|| (this.sido.value == '경기' && $.inArray(gugun[i], ['고양시','부천시','안산시','성남시','용인시','평택시','안양시','화성시'])<0))) {
				addText = '<u style="text-decoration:none; color:#f00; font-size:90%; margin-left:8px;">*마감임박</u>';
			}
			this.gugun.options.add(new Option(gugun[i]+addText, gugun[i]));
		}
	},
	update_dong : function() {
		if (this.dong == null) return;
		var dong = this[this.sido.value+"->"+this.gugun.value];
		this.dong.innerHTML = "";
		for(var i=0; i<dong.length; i++)
//			console.log(dong);
//			console.log(this.dong);
			this.dong.value = dong ;
	},
	update_sido : function(use_list) { 
		if (this.sido == null) return;
		var sido = this['시도'];
		for(var i=0; i<sido.length; i++) {
			if(i != 0 && use_list.length > 0 && $.inArray(sido[i], use_list) < 0) continue;
			this.sido.options.add(new Option(sido[i], sido[i]));
		}
		this.update_gugun();
		this.update_dong();
	}, 
'시도' : ['선택하세요','서울','경기','인천','강원','충남','대전','충북', '세종','울산','대구','경북','경남','전남','광주','전북','부산','제주'],
'선택하세요' : ['선택하세요'],
'서울' : ['선택하세요','강남구','강동구','강북구','강서구','관악구','광진구','구로구','금천구','노원구','도봉구','동대문구','동작구','마포구','서대문구','서초구','성동구','성북구','송파구','양천구','영등포구','용산구','은평구','종로구','중구','중랑구'],
'경기' : ['선택하세요','가평군','고양시','과천시','광명시','광주시','구리시','군포시','김포시'/*,'남양주시'*/,'남양주시 북부','남양주시 중부','남양주시 남부','동두천시', '동탄신도시', '부천시','성남시','수원시영통구','수원시장안구','수원시팔달구','수원시권선구','시흥시','안산시'/*,'안성시'*/,'안양시','양주시'/*,'양평군'*/,'여주시','연천군','오산시','용인시','의왕시','의정부시','이천시','파주시'/*,'평택시'*/,'포천시','하남시','화성시'], 
'인천' : ['선택하세요','강화군','계양구','미추홀구','남동구','동구','부평구','서구','연수구','옹진군','중구'],
'강원' : ['선택하세요',/*'강릉시','고성군','동해시','삼척시','속초시',*/'양구군',/*'양양군','영월군',*/'원주시','인제군',/*'정선군','철원군',*/'춘천시',/*'태백시','평창군',*/'홍천군','화천군',/*'횡성군'*/],		
'충남' : ['선택하세요','계룡시','공주시','금산군','논산시'/*,'당진시','보령시'*/,'부여군'/*,'서산시'*/,'서천군','아산시','연기군'/*,'예산군'*/,'천안시'/*,'청양군','홍성군','태안군'*/],
'대전' : ['선택하세요','대덕구','동구','서구','유성구','중구'],
'충북' : ['선택하세요','괴산군',/*'단양군',*/'보은군'/*,'영동군'*/,'옥천군','음성군'/*,'제천시'*/,'증평군','진천군'/*,'청원군'*/,'청주시','충주시'],
// '세종' : ['선택하세요','한솔동','도담동','조치원읍','연기면','연동면','부강면','금남면','장군면','연서면','전의면','전동면','소정면','소담동','보람동','반곡동','대평동','가람동','나성동','새롬동','다정동','어진동','종촌동','고운동','아름동','집현동'],
'세종' : ['선택하세요','세종시'],
'울산' : ['선택하세요','남구','동구','북구','울주군','중구'],
'대구' : ['선택하세요','남구','달서구','달성군','동구','북구','서구','수성구','중구'],
'경북' : ['선택하세요','경산시','경주시',/*'고령군',*/'구미시'/*,'군위군','김천시','문경시','봉화군','상주시','성주군'*/,'안동시','영덕군',/*'영양군','영주시','영천시','예천군','울진군','의성군','청도군',*/'청송군','칠곡군','포항시'],
'경남' : ['선택하세요',/*'거제시',*/'거창군','고성군','김해시','남해군'/*,'밀양시'*/,'사천시','산청군','양산시','의령군','진주시','창녕군','창원시',/*'통영시',*/'하동군','함안군','함양군','합천군'],
'전남' : ['선택하세요','강진군','고흥군','곡성군','광양시','구례군','나주시','담양군','목포시','무안군','보성군','순천시','신안군','여수시','영광군','영암군','완도군','장성군','장흥군','진도군','함평군','해남군','화순군'], 
'광주' : ['선택하세요','광산구','남구','동구','북구','서구'],
'전북' : ['선택하세요','고창군','군산시','김제시','남원시','무주군','부안군'/*,'순창군'*/,'완주군','익산시','임실군','장수군','전주시','정읍시','진안군'],
'부산' : ['선택하세요','강서구','금정구','기장군','남구','동구','동래구','진구','북구','사상구','사하구','서구','수영구','연제구','영도구','중구','해운대구'],
'제주' : ['선택하세요','제주시','서귀포시'],


'선택하세요->선택하세요' : '00000',
'서울->선택하세요' : '00000',
'경기->선택하세요' : '00000',
'인천->선택하세요' : '00000',
'강원->선택하세요' : '00000',
'충남->선택하세요' : '00000',
'대전->선택하세요' : '00000',
'충북->선택하세요' : '00000',
'세종->선택하세요' : '00000',
'울산->선택하세요' : '00000',
'대구->선택하세요' : '00000',
'경북->선택하세요' : '00000',
'경남->선택하세요' : '00000',
'전남->선택하세요' : '00000',
'광주->선택하세요' : '00000',
'전북->선택하세요' : '00000',
'부산->선택하세요' : '00000',
'제주->선택하세요' : '00000',

'서울->강남구' : '02001',
'서울->강동구' : '02002',
'서울->강북구' : '02003',
'서울->강서구' : '02004',
'서울->관악구' : '02005',
'서울->광진구' : '02006',
'서울->구로구' : '02007',
'서울->금천구' : '02008',
'서울->노원구' : '02009',
'서울->도봉구' : '02010',
'서울->동대문구' : '02011',
'서울->동작구' : '02012',
'서울->마포구' : '02013',
'서울->서대문구' : '02014',
'서울->서초구' : '02015',
'서울->성동구' : '02016',
'서울->성북구' : '02017',
'서울->송파구' : '02018',
'서울->양천구' : '02019',
'서울->영등포구' : '02020',
'서울->용산구' : '02021',
'서울->은평구' : '02022',
'서울->종로구' : '02023',
'서울->중구' : '02024',
'서울->중랑구' : '02025',

'경기->가평군' : '03101',
'경기->고양시' : '03102',
'경기->과천시' : '03103',
'경기->광명시' : '03104',
'경기->광주시' : '03105',
'경기->구리시' : '03106',
'경기->군포시' : '03107',
'경기->김포시' : '03108',
'경기->남양주시' : '03109',
'경기->남양주시 북부' : '03135',
'경기->남양주시 중부' : '03136',
'경기->남양주시 남부' : '03137',
'경기->동두천시' : '03110',
'경기->동탄신도시' : '03138',
'경기->부천시' : '03111',
'경기->성남시' : '03112',
'경기->수원시영통구' : '03113',
'경기->수원시장안구' : '03114',
'경기->수원시팔달구' : '03115',
'경기->시흥시' : '03116',
'경기->안산시' : '03117',
'경기->안성시' : '03118',
'경기->안양시' : '03119',
'경기->양주시' : '03120',
'경기->양평군' : '03121',
'경기->여주시' : '03122',
'경기->연천군' : '03123',
'경기->오산시' : '03124',
'경기->용인시' : '03125',
'경기->의왕시' : '03126',
'경기->의정부시' : '03127',
'경기->이천시' : '03128',
'경기->파주시' : '03129',
'경기->평택시' : '03130',
'경기->포천시' : '03131',
'경기->하남시' : '03132',
'경기->화성시' : '03133',
'경기->수원시권선구' : '03134',

'인천->강화군'	: '03201',
'인천->계양구'	: '03202',
'인천->미추홀구': '03203',
'인천->남동구'	: '03204',
'인천->동구'	: '03205',
'인천->부평구'	: '03206',
'인천->서구'	: '03207',
'인천->연수구'	: '03208',
'인천->옹진군'	: '03209',
'인천->중구'	: '03210',

'강원->강릉시' : '03301',
'강원->고성군' : '03302',
'강원->동해시' : '03303',
'강원->삼척시' : '03304',
'강원->속초시' : '03305',
'강원->양구군' : '03306',
'강원->양양군' : '03307',
'강원->영월군' : '03308',
'강원->원주시' : '03309',
'강원->인제군' : '03310',
'강원->정선군' : '03311',
'강원->철원군' : '03312',
'강원->춘천시' : '03313',
'강원->태백시' : '03314',
'강원->평창군' : '03315',
'강원->홍천군' : '03316',
'강원->화천군' : '03317',
'강원->횡성군' : '03318',

'충남->계룡시':	'04101',
'충남->공주시':	'04102',
'충남->금산군':	'04103',
'충남->논산시':	'04104',
'충남->당진시':	'04105',
'충남->보령시':	'04106',
'충남->부여군':	'04107',
'충남->서산시':	'04108',
'충남->서천군':	'04109',
'충남->아산시':	'04110',
'충남->연기군':	'04111',
'충남->예산군':	'04112',
'충남->천안시':	'04113',
'충남->청양군':	'04114',
'충남->태안군':	'04115',
'충남->홍성군':	'04116',

'대전->대덕구':	'04201',
'대전->동구':	'04202',
'대전->서구':	'04203',
'대전->유성구':	'04204',
'대전->중구':	'04205',

'충북->괴산군':	'04301',
'충북->단양군':	'04302',
'충북->보은군':	'04303',
'충북->영동군':	'04304',
'충북->옥천군':	'04305',
'충북->음성군':	'04306',
'충북->제천시':	'04307',
'충북->증평군':	'04308',
'충북->진천군':	'04309',
'충북->청원군':	'04310',
'충북->청주시':	'04311',
'충북->충주시':	'04312',

'세종->한솔동':'04401',
'세종->도담동':'04402',
'세종->조치원읍':'04403',
'세종->연기면':'04404',
'세종->연동면':'04405',
'세종->부강면':'04406',
'세종->금남면':'04407',
'세종->장군면':'04408',
'세종->연서면':'04409',
'세종->전의면':'04410',
'세종->전동면':'04411',
'세종->소정면':'04412',
'세종->소담동':'04413',
'세종->보람동':'04414',
'세종->반곡동':'04415',
'세종->대평동':'04416',
'세종->가람동':'04417',
'세종->나성동':'04418',
'세종->새롬동':'04419',
'세종->다정동':'04420',
'세종->어진동':'04421',
'세종->종촌동':'04422',
'세종->고운동':'04423',
'세종->아름동':'04424',
'세종->집현동':'04426',
'세종->세종시':'04427',

'부산->강서구'	: '05101',
'부산->금정구'	: '05102',
'부산->기장군'	: '05103',
'부산->남구'	: '05104',
'부산->동구'	: '05105',
'부산->동래구'	: '05106',
'부산->진구'	: '05107',
'부산->북구'	: '05108',
'부산->사상구'	: '05109',
'부산->사하구'	: '05110',
'부산->서구'	: '05111',
'부산->수영구'	: '05112',
'부산->연제구'	: '05113',
'부산->영도구'	: '05114',
'부산->중구'	: '05115',
'부산->해운대구': '05116',

'울산->남구'	: '05201',
'울산->동구'	: '05202',
'울산->북구'	: '05203',
'울산->울주군'	: '05204',
'울산->중구'	: '05205',

'대구->남구'	:'05301',
'대구->달서구'	:'05302',
'대구->달성군'	:'05303',
'대구->동구'	:'05304',
'대구->북구'	:'05305',
'대구->서구'	:'05306',
'대구->수성구'	:'05307',
'대구->중구'	:'05308',

'경북->경산시' :'05401',
'경북->경주시' :'05402',
'경북->고령군' :'05403',
'경북->구미시' :'05404',
'경북->군위군' :'05405',
'경북->김천시' :'05406',
'경북->문경시' :'05407',
'경북->봉화군' :'05408',
'경북->상주시' :'05409',
'경북->성주군' :'05410',
'경북->안동시' :'05411',
'경북->영덕군' :'05412',
'경북->영양군' :'05413',
'경북->영주시' :'05414',
'경북->영천시' :'05415',
'경북->예천군' :'05416',
'경북->울릉군' :'05417',
'경북->울진군' :'05418',
'경북->의성군' :'05419',
'경북->청도군' :'05420',
'경북->청송군' :'05421',
'경북->칠곡군' :'05422',
'경북->포항시' :'05423',

'경남->거제시'	:'05501',
'경남->거창군'	:'05502',
'경남->고성군'	:'05503',
'경남->김해시'	:'05504',
'경남->남해군'	:'05505',
'경남->밀양시'	:'05506',
'경남->사천시'	:'05507',
'경남->산청군'	:'05508',
'경남->양산시'	:'05509',
'경남->의령군'	:'05510',
'경남->진주시'	:'05511',
'경남->창녕군'	:'05512',
'경남->창원시'	:'05513',
'경남->통영시'	:'05514',
'경남->하동군'	:'05515',
'경남->함안군'	:'05516',
'경남->함양군'	:'05517',
'경남->합천군'	:'05518',

'전남->강진군'	:'06101',
'전남->고흥군'	:'06102',
'전남->곡성군'	:'06103',
'전남->광양시'	:'06104',
'전남->구례군'	:'06105',
'전남->나주시'	:'06106',
'전남->담양군'	:'06107',
'전남->목포시'	:'06108',
'전남->무안군'	:'06109',
'전남->보성군'	:'06110',
'전남->순천시'	:'06111',
'전남->신안군'	:'06112',
'전남->여수시'	:'06113',
'전남->영광군'	:'06114',
'전남->영암군'	:'06115',
'전남->완도군'	:'06116',
'전남->장성군'	:'06117',
'전남->장흥군'	:'06118',
'전남->진도군'	:'06119',
'전남->함평군'	:'06120',
'전남->해남군'	:'06121',
'전남->화순군'	:'06122',

'광주->광산구'	:'06201',
'광주->남구'	:'06202',
'광주->동구'	:'06203',
'광주->북구'	:'06204',
'광주->서구'	:'06205',

'전북->고창군'	:'06301',
'전북->군산시'	:'06302',
'전북->김제시'	:'06303',
'전북->남원시'	:'06304',
'전북->무주군'	:'06305',
'전북->부안군'	:'06306',
'전북->순창군'	:'06307',
'전북->완주군'	:'06308',
'전북->익산시'	:'06309',
'전북->임실군'	:'06310',
'전북->장수군'	:'06311',
'전북->전주시'	:'06312',
'전북->정읍시'	:'06313',
'전북->진안군'	:'06314',

'제주->서귀포시' :'06401',
'제주->제주시' : '06402'




}