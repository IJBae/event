class Chatbot {
    #currentSectionIdx;
    #selectSectionIdx;
    #form;
    complete = false;
    chat;
    audio;
    pre_audio;
    config = {
        'wrap' : '.chat_wrap', //wrap Selector
        'backgroundColor': '#f3f3f3', //bgColor
        'profileImg': '//static.hotblood.co.kr/event/v_8742/profile.png', //Profile Image
        'progressText': 'percent', //진행상황 표시
        'chatLoading': true, //로딩 표시
        'maxHeight': '50vh', //최대 높이
        'autoFocus': true,
        'scrollSpeed': 100,
    };
    constructor(chat, config) {
        var _this = this;
        this.#form = $('form', $(this.config.wrap).parents('.chatbot'));
        this.chat = chat;
        if(typeof config != 'undefined') {
            $.each(config, function(key, val) {
                _this.config[key] = val;
            })
        }
        if(this.config.maxHeight) $(this.config.wrap).css('max-height', this.config.maxHeight);
        if(this.config.height) $(this.config.wrap).css('height', this.config.height);
        if(this.config.backgroundColor) $(this.config.wrap).css('background-color', this.config.backgroundColor);
        $(this.config.wrap).css('overflow-y', 'auto');
        this.goToSection(0);
    }
    goToSection(idx) { //idx 기준 섹션 이동
        if(typeof idx == undefined) this.#nextSection();
        var selectSection = (typeof this.chat[idx] != "undefined") ? this.chat[idx] : null;
        if(selectSection == null) {
            console.warn('해당 섹션이 존재하지 않습니다.');
            return false;
        }
        this.#selectSectionIdx = idx;
        this.#appendSection(selectSection);
        this.#currentSectionIdx = idx;
    }
    #nextSection() { //다음페이지
        this.goToSection(++this.#currentSectionIdx);
    }
    #prevSection() { //이전페이지
        this.goToSection(--this.#currentSectionIdx);
    }
    #appendSection(selectSection) { //섹션 추가
        var $this = this;
        var $chat = this.#makeChat(selectSection);
        $chat = this.#setLoader($chat);
        var $chatbox = $(this.config.wrap).append($chat);
        this.scrollToBottom();
        $chatbox.find('.loader_box').delay(500).fadeOut(function() {
            $(this).remove();
            $chatbox.find('.chat_box').css({'display':'inline-block'});
            $chatbox.find('.chat_box.right').css({'display':'flex'});
            $this.scrollToBottom();
        });
        function chkAppend(t) {
            var append_chk = false;
            if($('section:last-child .chat_box', t.config.wrap).css('display') != 'none') {
                setTimeout(function() { t.scrollToBottom(); }, 100);
                append_chk = true;
            }
            if(append_chk == false) {
                setTimeout(function() { chkAppend(t); }, 500);
            }
        }
        chkAppend(this);
        if(this.config.autoFocus) 
            setTimeout(function() { $chatbox.find('input[type="text"], input[type="number"], input[type="tel"]').focus(); }, 1000);
        //audio 자동재생
        var audio_file = "";
        this.pre_audio = this.audio;
        selectSection.forEach((row, i) => {
            if(row.audio) audio_file = row.audio;
        });
        if(audio_file) {
            this.audio = new Audio(audio_file);
            if(typeof this.pre_audio == 'object') this.pre_audio.pause();
            if(this.pre_audio != this.audio) {
                this.audio.play();
            }
        }
        var chatbot = this;
        $('section:not(:last-child)').find('button, input').click(function(e) { return false; });
        $(this.config.wrap).find('input:radio').unbind('click');
        $('section:last-child', this.config.wrap).find('input:radio').bind('click', function(e) { //radio 버튼 자동 submit 처리
            e.stopPropagation();
            chatbot.chatbotSubmit();

            var selectedInputName = $(this).attr('name');
            $('.chat_box.right label input[name="' + selectedInputName + '"]:not(:checked)').closest('label').css('display', 'none');
            $('.chat_box.right label input[name="' + selectedInputName + '"]:checked').closest('label').addClass('on');
        
        });
        if(typeof PageOnLoad == 'function') {
            var pageOnLoad = new PageOnLoad(this); //각 페이지 유효성 함수 클래스 분리
            var method = `load_${this.#selectSectionIdx}`;
            if(typeof eval(`pageOnLoad.${method}`) == 'function') setTimeout(function() { eval(`pageOnLoad.${method}()`); }, 1500);
        }
    }
    #setLoader($chat) { //로딩 추가
        if(!this.config.chatLoading) return $chat;
        var loader = `<div class="loader_box"><span class="loader"><i class="blind">로딩중...</i></span></div>`;
        $chat.find('.chat_box').css({'display':'none'});
        $chat = $chat.append(loader);
        return $chat;
    }
    #makeChat(section) { //채팅 html 생성
        var chat = `<section>`; //질문, 답변을 한개의 컨테이너로 세팅
        if(this.config.progressText) { //진행상황 true or text일 경우
            var text = '';
            switch(this.config.progressText) {
                case 'step':
                    if(this.#currentSectionIdx) text = `${this.#currentSectionIdx}/${this.chat.length-1}`; break;
                case true:
                case 'percent':
                dafault:
                    if(this.#currentSectionIdx) text = (this.#currentSectionIdx / (this.chat.length-1)) * 100 +'%'; break;
            }
            if(this.config.progressTextLabel){
                if(text) chat += `<div class="progress_box"><span class="progress_txt">${this.config.progressTextLabel} <span class="percent">${text}</span></span></div>`;
            }else{
                if(text) chat += `<div class="progress_box"><span class="progress_txt">진행상황 <span class="percent">${text}</span></span></div>`;
            }
        }
        chat += `<div class="chat_container">`;
        section.forEach((row, idx) => { //한 섹션 내 메세지 세팅
            if(idx == 0 && this.config.profileImg)
                chat += `<div class="chat_profile"><img src="${this.config.profileImg}"></div>`;						
        });
        chat += `<div class="chat_set">`;
        section.forEach((row, idx) => { //한 섹션 내 메세지 세팅
            if(typeof row.class == 'undefined') row.class = '';
            chat += `<div class="chat_box ${row.type} ${row.class}">`;
            chat += this.#setContent(row);
            chat += `</div>`;
        });
        chat += `</div></div>`;
        chat += `</section>`;
        return $(chat);
    }
    #setContent(data) { //컨텐츠 세팅
        var content = '';
        switch(data.type) {
            case 'date' : content += this.#getDate();
            default : content += this.#getMessage(data);
        }
        return content;
    }
    #getMessage(data) { //섹션내 메세지 가져오기
        return data.message;
    }
    #getDate() { //날짜세팅
        var date = new Date();
        return (`${date.getFullYear()}년 ${date.getMonth()+1}월 ${date.getDate()}일${this.#getDay(date)}`);
    }
    #getDay(date) { //요일세팅
        if(typeof date != 'object') return;
        var day = date.getDay();
        var days = ['일','월','화','수','목','금','토'];
        return ` ${days[day]}요일`;
    }
    scrollToBottom() { //스크롤 하단으로 이동
        $(this.config.wrap).animate({ scrollTop: $(this.config.wrap).prop('scrollHeight') }, this.config.scrollSpeed, 'linear');
    }
    #validator() { //form 유효성 체크
        var result = true;
        if(typeof PageValidator == 'function') {
            var pageValidator = new PageValidator(this.#form); //각 페이지 유효성 함수 클래스 분리
            var method = `check_${this.#currentSectionIdx}`;
            // console.log(this.#currentSectionIdx, typeof eval(`pageValidator.${method}`))
            if(typeof eval(`pageValidator.${method}`) == 'function')
                result = eval(`pageValidator.${method}()`);
        }
        return result;
    }
    setComplete() { //전체 폼 체크가 완료됐을 경우 호출해야 할 함수
        this.complete = true;
    }
    chatbotSubmit() { //챗봇 폼 전송 캐치
        console.log('chatbot Submit');
        var valid = this.#validator();
        if(!valid) return false;
        if(this.complete === true) {
            this.#form.submit();
            return false;
        } else { //complete 되지 않았으면 자동으로 다음페이지 이동
            this.#nextSection();
        }
    }
}
class Validator {
    #form;
    #data;
    constructor(form) {
        this.#form = form;
    }
    check(data) {
        this.#data = {"name":data[0],"desc":data[1]};
        var result = false;
        switch(this.#data.name) {
            case 'name' : result = this.#checkName(); break;
            case 'age' : result = this.#checkAge(); break;
            case 'phone' : result = this.#checkPhone(); break;
            case 'agree' : result = this.#checkAgree(); break;
        }
        return result;
    }
    #checkName() {
        if (!$('[name="' + this.#data.name + '"]', this.#form).val()) {
            alert(this.#data.desc + " 항목을 입력해주세요.");
            $('[name="' + this.#data.name + '"]', this.#form).focus();
            return false;
        }
        return true;
    }
    #checkAge() {
        if (!$('[name="' + this.#data.name + '"]', this.#form).val()) {
            alert(this.#data.desc + " 항목을 입력해주세요.");
            $('[name="' + this.#data.name + '"]', this.#form).focus();
            return false;
        }
        return true;
    }
    #checkPhone() {
        if (!$('[name="' + this.#data.name + '"]', this.#form).val()) {
            alert(this.#data.desc + " 항목을 입력해주세요.");
            $('[name="' + this.#data.name + '"]', this.#form).focus();
            return false;
        } else if (!$('[name="' + this.#data.name + '"]', this.#form).val().match(/[\d]+/)) {
            alert(this.#data.desc + " 항목은 숫자만 입력할 수 있습니다.");
            $('[name="' + this.#data.name + '"]', this.#form).focus();
            return false;
        } else if (/^010[2-9]{1}[0-9]{3}[0-9]{4}$/.test($('[name="' + this.#data.name + '"]', this.#form).val()) == false) {
            alert("올바른 연락처가 아닙니다.");
            $('[name="' + this.#data.name + '"]', this.#form).focus();
            return false;
        }
        return true;
    }
    #checkAgree() {
        if(!$('[name="' + this.#data.name + '"]', this.#form).is(':checked')) {
            alert('개인정보 처리방침에 동의하지 않으실 경우 이벤트에 참여하실 수 없습니다.');
            $('[name="' + this.#data.name + '"]', this.#form).focus();
            return false;
        }
        return true;
    }
}