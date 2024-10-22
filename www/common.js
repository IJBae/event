//IE closest 패치
if (!Element.prototype.matches)
	Element.prototype.matches =
		Element.prototype.msMatchesSelector ||
		Element.prototype.webkitMatchesSelector;
if (!Element.prototype.closest) {
	Element.prototype.closest = function (s) {
		var el = this;
		do {
			if (Element.prototype.matches.call(el, s)) return el;
			el = el.parentElement || el.parentNode;
		} while (el !== null && el.nodeType === 1);
		return null;
	};
}
$(document).ready(function () {
	//back버튼
	$(".btn_back").bind("click", function (e) {
		e.preventDefault();
		history.back();
	});
	//개인정보 자세히보기
	$('#wrap').on("click", ".btn-agreement", function (e) {
		e.preventDefault();
		e.stopPropagation();
		$(".agreement").slideToggle();
	});
	$(".agreement").bind("click", function (e) {
		e.stopPropagation();
	});
	$("#wrap").bind("click", function (e) {
		e.stopPropagation();
		if ($(".agreement").is(":visible")) $(".agreement").slideUp();
	});
	// tel은 PC에서도 숫자만 입력
	$('form').on("keyup", '[type="tel"]', function (e) {
		this.value = this.value.replace(/[^0-9]+/, "");
	});

	//굿닥 랜딩 체크 후 푸터 변경
	let goodocInput = document.querySelector('input[name="event_id"]');
	let imgTag = document.querySelector('img[src*="footer.jpg"]');
	if (goodocInput !== null && imgTag) {
		imgTag.src = "//static.hotblood.co.kr/event/gooddoc/footer.jpg";
	}
});
function SubmitClass(frm, required, options) {
	this.options = {
		language: "ko", //언어설정
		abuse: false, //비속어 설정
		valid: true, //유효성체크 설정
		chkDigit: false, //010체크 설정
	};
	var abuse_words = ["개새끼", "개쌔끼", "개때끼", "개세끼", "개색끼", "개색히", "개쉑히", "개쉑", "개새꺄", "개쌔꺄", "개때꺄", "개색꺄", "개새", "개쉐", "십새", "십쉐", "씹새꺄", "씹쌔꺄", "씹때꺄", "씹색꺄", "씹새", "씹쉐", "병신새끼", "좆나게", "좃나게", "존나게", "젖나게", "젓나게", "전나게", "졸라게", "절라게", "좆나", "좃나", "존나", "젖나", "젓나", "전나", "졸라", "절라", "좆만한", "좃만한", "존만한", "젖만한", "젓만한", "좆마난", "좃마난", "존마난", "젖마난", "젓마난", "좆같은", "좃같은", "젖같은", "젓같은", "좆가튼", "좃가튼,", "젖가튼", "젓가튼", "조까튼", "저까튼", "좆빠지게", "좃빠지게", "젖빠지게", "젓빠지게", "조빠지게", "저빠지게", "좆밥", "좃밥", "좁밥", "젖밥", "젓밥", "접밥", "조빱", "저빱", "좆까", "좃까", "젖까", "젓까", "조까", "저까", "좆도", "좃도", "젖도", "조또", "씨댕", "씨뎅", "씹탱", "씹창", "씨발", "씨벌", "씨불", "씨이발", "씨이벌", "씨이불", "씹팔", "씹펄", "씹풀", "씹빡", "띠발", "띠벌", "띠불", "띠방", "띠바", "띠붕", "띠밸", "띠팔", "띠펄", "시벌", "시팔", "시이발", "시이벌", "시이불", "시이바", "시이붕", "시이부", "시이밸", "시이팔", "시이펄", "시이풀", "시이빡", "쒸발", "쒸벌", "쒸불", "쒸방", "쒸바", "쒸빡", "쒸이발", "쒸이벌", "쒸이불", "쒸이방", "쒸이바", "쉬발", "쉬벌", "쉬불", "쉬방", "쉬바", "쉬붕", "쉬부", "쉬밸", "쉬팔", "쉬풀", "쉬빡", "쓰발", "쓰벌", "쓰불", "쓰방", "쓰바", "쓰붕", "쓰부", "쓰밸", "쓰팔", "쓰펄", "쓰풀", "쓰빡", "쓰이발", "쓰이벌", "쓰이불", "까대", "까댄", "니미럴", "니미랄", "니미롤", "니미룰", "니미를", "제기럴", "제기랄", "제기롤", "제기룰", "니기미", "닝기미", "니주가리", "니미", "쌍년", "썅년", "쌍넌", "썅넌", "쌍뇬", "썅뇬", "개년", "개넌", "개뇬", "쌍놈", "썅놈", "쌍넘", "썅넘", "개놈", "개넘", "썅", "지랄", "지럴", "지롤", "쥐랄", "쥐럴", "쥐롤", "병신", "븅신", "빙신", "등신", "벼엉신", "빙시", "미친놈", "미친넘", "미췬놈", "미췬넘", "미친년", "미친뇬", "미친넌", "미췬년", "미췬뇬", "미췬넌", "개자식", "싸가지", "싹아지", "염병", "옘병", "니애미", "니에미", "c팔", "개같은년", "c8", "개같은놈", "씝할", "새끠", "세끠", "쉐끼", "새뤼", "쉐끼", "쇄끼", "쉐끼", "쇅끼", "씨키", "세키", "ㅅ ㅐ끼", "ㅅ ㅐㄲ ㅣ", "ㅅ ㅐ ㄲ ㅣ", "", "시발", "싀발", "스발", "스벌", "쉬이발", "쓰으방", "쉬빨", "쉬팍", "스벌", "씁알", "씝알", "씹알", "씝탱", "뵹신", "붕신", "븅싄", "뵹싄", "병싄", "존니", "죶나", "죨라", "죶니", "죤니", "죤나", "죶마난", "죶같은", "죶가튼", "죶빠지게", "즤랄", "질알", "미친", "애자", "ㅇㅐ자", "ㅇㅐㅈㅏ", "니엠", "엠창", "믜친", "뮈친", "ㅁl친", "섹히", "색기", "색히", "병쉰", "뱅신", "뱅싄", "뱅쉰", "븽신", "병쉰", "애쟈", "또라이", "떠라이", "ㅆl팔", "ㅆl발", "ㄴ ㅣㅁ ㅣ", "ㄴlㅁl", "늬미", "니믜", "쓰팍", "씌팔", "씌발", "싀팔", "ㅆ1팔", "ㅆ1발", "씹8", "샹년", "상년", "씹년", "씹뇬", "챵녀", "챵년", "창뇬", "챵뇬", "잡뇬", "잡년", "계같은뇬", "개같은뇬", "개섹", "죳나", "니앰", "앰창", "애미", "씹땡", "씹넘", "씝년", "ㄱH뇬", "샹놈", "샹늠", "개색", "개자슥", "게세끼", "씨입년", "씨입뇬", "씌벌", "싀방", "싀봉", "씌방", "ㅆI발", "ㅅ1발", "ㅆl발", "ㅆi발", "ㄴ1ㅇH미", "쥐랄", "ㅈl랄", "뷩신", "뱅신", "세꺄", "미칀", "호로자슥애", "젠장", "제길", "젱장", "우라질", "제긜", "젝일", "바부", "듕신", "새키", "씨벨", "시벵", "시펄", "씹쌔", "등쉬", "늬귀미", "조낸", "씨밝", "개세", "개샛끼", "개쉬끼", "개세끼", "개쓰렉기", "쉬키", "색갸", "세캬", "식키", "시키", "새캬", "듕시나", "똘아이", "똘추", "ㅁ1친", "ㅁ1친년", "뮈췬", "뮈친년", "미췐", "미췐년", "미튄", "및친", "벵신", "병시나", "병시", "찐따", "뷩시", "새뀌", "색귀", "셥새", "쉽년", "쉽새", "시바라", "쉽팔", "시밝", "시벨", "십샹", "ㅆ│발", "쒸뱅", "씌파", "씌빨", "씌밸", "씌바", "쒸박", "씨뱅", "c발", "g랄", "zl랄", "凸,미워", "뉘미", "뉘귀미", "^^ㅣ발", "ㄱ ㅐ", "ㅅ ㅐㄲ ㅣ", "ㅅ ㅐㄱㄱㅣ", "ㅅ ㅐ끼", "ㄱH", "ㅅ ㅔㄲ ㅣ", "ㅅHㄲI", "ㅅH끼", "ㅅ ㅔㄲ", "ㅅ1팔", "ㅆ ㅣ불", "ㅆㅂ", "ㅅㅂ", "씹발", "개`", "개쇄", "씨`발", "색끼", "섀캬", "샹뇬", "쇄키", "싯팔", "개씹", "섀끼", "쌔기", "ㅁ ㅣ친", "ㅁl친", "망할", "씨박", "씨벵", "씨빨", "씨파", "씨브랄", "씨팍", "씹빨", "씹세", "씹쌍", "ㅈ1랄", "ㅆ ㅣ발", "ㅆ ㅣ방", "ㅆ ㅣ팔", "ㅆ ㅣ벌", "ㅆ!발", "ㅆㅣ발", "쒸벨", "등쉰", "똘츄", "ㄴ ㅣ미", "ㄴ ㅣㄱ ㅣ", "ㄴ1ㄱ1", "ㄴ1ㅁ1", "ㄴ1미", "ㄴ1 ㅇ ㅐ", "ㄴ1 애", "니M창", "니OH미", "뷩신", "뷩쉰", "븅쉰", "빙쉰", "시팍", "시빨", "시파", "시밸", "쉽뇬", "개련", "ㅆ1밸", "ㅆ1벨", "호구", "씌벨", "애좌", "씻팔", "ㅆ1바", "개샛히애", "색휘", "싯끼", "섹키", "쓉년", "쓉창", "호로자식애", "씌뱅", "씨팰", "씌댕", "씌뎅", "씹쒜", "엠병", "앰병", "후레자식애", "후레자슥애", "호로년", "후레년", "씹빠", "뻑큐", "빠큐", "ㅃr큐", "개섀끼", "개샹련", "니뮈럴", "늬기미", "앰창", "엠창", "쓰박", "씨부랄", "씹샹", "연병", "시밟", "뒤져", "짜져", "꺼져", "잡놈", "뒤질래", "디질래", "아갈", "닥쳐", "허접", "흐접", "찌질이", "낙태아", "다방년", "호모", "엿먹어", "개자지", "개보지", "애비자지", "애미보지", "자지", "잠지", "보지", "섹스", "공알", "좆", "좇", "좃", "젖", "빨통", "딸딸이", "딸따리", "빠구리", "빠굴이", "빠굴", "빠순이", "빠수니", "사까시", "불알", "부랄", "번섹", "번쌕", "번쎅", "떡치기", "후장", "섹스", "색스", "쎅스", "쌕스", "쉑스", "섹쓰", "색쓰", "쎅쓰", "쌕쓰", "쉑쓰", "창녀", "창년", "갈보", "개싑창", "죷", "죳", "좆나", "좇나", "존나", "죤나", "욧나", "쟈지", "죠또", "죵나", "자쥣", "죠빠", "쫓까", "죡쳐", "보쥐", "쟘쥐", "꼬추", "꼬츄", "강간", "걸레년", "곧휴", "쟘지", "ㅈ ㅏㅈ ㅣ", "ㅈ ㅏ지", "자쥐", "잠쥐", "쟘쥐", "유두", "유방", "차앙년", "창ㄴ ㅕ", "창뇬", "촹년", "촹뇬", "폰섹", "ㅃㅏ구리", "ㅃr굴", "고츄", "붕알", "폰색", "죠까", "sex", "fuck", "fuk", "porno", "dildo", "pussy", "shit", "bitch", "anal", "fetish", "gay", "lesbien", "bastard", "cunt", "damn", "asshole", "귀두", "뽕알"];
	if (typeof options == "undefined") options = this.options;
	else {
		for (var i in this.options)
			if (typeof options[i] == "undefined") options[i] = this.options[i];
	}
	if (typeof required != undefined || typeof required != null)
		this.required = required;
	var no = document.location.pathname.split("/").pop();
	this.frm = frm;
	this.getType = function (name) {
		try {
			var tagName = $('[name="' + name + '"]', this.frm).prop("tagName").toLowerCase();
			if (tagName == "input")
				return $('[name="' + name + '"]', this.frm).attr("type");
			else return tagName;
		} catch (e) {
			console.error('필수값으로 설정한 "' +name + '"은(는) 존재하지 않는 필드 입니다.');
			return false;
		}
	};

	this.valid = function (name, desc) {
		if (options.valid === false) return true; //valid option 을 사용하지 않을 경우 무조건 통과
		switch (this.getType(name)) {
			case "radio":
			case "checkbox":
				if (!$('[name="' + name + '"]:checked', this.frm).val()) {
					alert(desc + " 항목을 선택해주세요.");
					$('[name="' + name + '"]', this.frm)[0].closest("div").scrollIntoView({behavior: "smooth", block: "start"});
					$('[name="' + name + '"]:eq(0)', this.frm).focus();
					return false;
				}
				break;
			case "hidden":
			case "text":
			case "textarea":
				if (!$('[name="' + name + '"]', this.frm).val()) {
					alert(desc + " 항목을 입력해주세요.");
					$('[name="' + name + '"]', this.frm).focus();
					return false;
				}
				break;
			case "file":
				if (!$('[name="' + name + '"]', this.frm).val()) {
					alert(desc + " 항목은 필수입니다.");
					$('[name="' + name + '"]', this.frm).trigger("click");
					return false;
				}
				break;
			case "select":
				if (!$('[name="' + name + '"] option:selected', this.frm).val()) {
					alert(desc + " 항목을 입력해주세요.");
					$('[name="' + name + '"]', this.frm).focus();
					return false;
				}
				break;
			case "tel":
				if (!$('[name="' + name + '"]', this.frm).val()) {
					alert(desc + " 항목을 입력해주세요.");
					$('[name="' + name + '"]', this.frm).focus();
					return false;
				} else if (!$('[name="' + name + '"]', this.frm).val().match(/[\d]+/)) {
					alert(desc + " 항목은 숫자만 입력할 수 있습니다.");
					$('[name="' + name + '"]', this.frm).focus();
					return false;
				}
				break;
			case false:
				return false;
				break;
		}
		switch (name) {
			case "name":
				if (options.language == "ko" && options.abuse == true) {
					//한국어 모드이고, abuse 기능이 켜져있을 때
					$('[name="name"]', this.frm).val($('[name="name"]', this.frm).val().replace(/\s/g, "")); //띄어쓰기 없애기
					if (
						!Hangul.isHangulAll($('[name="name"]', this.frm).val())
					) {
						//완성형 한글 체크
						alert("올바른 이름을 입력해주세요. : 101");
						$('[name="name"]', this.frm).val("").focus();
						return false;
					} else if ($('[name="name"]', this.frm).val().length >= 5) {
						alert("올바른 이름을 입력해주세요. : 102");
						$('[name="name"]', this.frm).focus();
						return false;
					} else if ($.inArray($('[name="name"]', this.frm).val(),abuse_words) >= 0) {
						alert("올바른 이름을 입력해주세요. : 103");
						$('[name="name"]', this.frm).val("").focus();
						return false;
					}
				}
				if ($('[name="name"]', this.frm).val().length < 2) {
					alert("올바른 이름을 입력해주세요.");
					$('[name="name"]', this.frm).focus();
					return false;
				}
				break;
			case "age":
				if ($('[name="age"]', this.frm).val().length < 1) {
					alert("올바른 나이를 입력해주세요.");
					$('[name="age"]', this.frm).focus();
					return false;
				} else if (
					$('[name="age"]', this.frm).val() &&
					$('[name="age"]', this.frm).data("min") &&
					$('[name="age"]', this.frm).data("min") >
						$('[name="age"]', this.frm).val()
				) {
					alert($('[name="age"]', this.frm).data("min") +"세 이하는 신청할 수 없습니다.");
					$('[name="age"]', this.frm).focus();
					return false;
				} else if (
					$('[name="age"]', this.frm).val() &&
					$('[name="age"]', this.frm).data("max") &&
					$('[name="age"]', this.frm).data("max") <
						$('[name="age"]', this.frm).val()
				) {
					alert($('[name="age"]', this.frm).data("max") +"세 이상은 신청할 수 없습니다.");
					$('[name="age"]', this.frm).focus();
					return false;
				}
				break;
			case "phone":
				if ($('[name="phone"]', this.frm).val().length < 11) {
					alert("올바른 연락처를 입력해주세요. : 101");
					$('[name="phone"]', this.frm).focus();
					return false;
				} else if (
					$('[name="phone"]').data("isauth") != "undefined" &&
					$('[name="phone"]').data("isauth") == false
				) {
					alert("휴대폰 인증을 진행해주세요.");
					return false;
				}
				if (options.chkDigit == true) {
					var regPhone = /^010[2-9]{1}[0-9]{3}[0-9]{4}$/;
					if (regPhone.test($('[name="phone"]', this.frm).val()) === false) {
						alert("올바른 연락처를 입력해주세요. : 102");
						$('[name="phone"]', this.frm).focus();
						return false;
					}
				}
				break;
		}
		return true;
	};
	this.submit = function (thanks, delay) {
		if (typeof thanks == "undefined") thanks = true;
		if (typeof delay == "undefined") delay = 0;
		var t = [];
		t = JSON.parse(localStorage.getItem("4119639092e62c55ea8be348e4d9260d")) || [];
		if (t.length >= 10) {
			t = [];
			localStorage.setItem("4119639092e62c55ea8be348e4d9260d", JSON.stringify(t));
		}
		$.ajax({
			type: "POST",
			url: "./" + no + "/proc",
			data: "ajax=true&" + $(frm).serialize() + '&local='+t,
			dataType: "json",
			cache: false,
			success: function (response) {
				if (!location.href.match(/^(local\.)?event\.hotblood\.co\.kr/))
					var url = "./" + no + "/thanks";
				else var url = "../" + no + "/thanks";
				if (!thanks){
					if (response.result) {
						localStorage.setItem("eid", response.data);
						return;
					}
				} 
				if (response.result) {
					t.push(response.data);
					localStorage.setItem("4119639092e62c55ea8be348e4d9260d", JSON.stringify(t));
					if (response.data) url += "/" + response.data;
					setTimeout(function() {
						location.href = url;
					}, delay);
				} else {
					if (response.data == "thanks") {
						setTimeout(function() {
							location.href = url;
						}, delay);
						return;
					}
					$('input[type="submit"], input[type="image"]').prop(
						"disabled",
						false
					);
					alert(response.msg);
				}
			},
		});
	};
}
