/**
 * WLS,We-Like-Study,An online examination application
 * The multichoice question type.
 * 
 * @author wei1224hf
 * */
wls.question.multichoice = Ext.extend(wls.question, {
	
	/**
	 * Add this question's Html-Dom-stuff into the web page
	 * One question contains :
	 *    Question Title , in [class=w_qw_title]
	 *    Question Tools , like listenning-icon ,description-icon , in [class=w_qw_tool]
	 *    Question Options , like A:blablabla, B:blablabla , in [class=w_qw_options]
	 *    Question Navigater , it's on the left of the main quiz tab panel , in [id=w_q_subQuesNav_]
	 * */
	initDom : function() {
		$("#wls_quiz_main").append("<div id='w_qs_" + this.id + "'></div>");
		
		$("#w_qs_" + this.id).append("<div class='w_qw_title'>"
				+ this.index + "&nbsp;<span class='w_qw_tool'></span>"
				+ this.questionData.title + "</div>");
		$("#w_qs_" + this.id)
				.append("<span class='w_qw_options'></span>");
		this.optionLength = parseInt(this.questionData.optionlength);
		for (var i = 0; i < parseInt(this.questionData.optionlength); i++) {
			eval("var title = this.questionData.option" + (i + 1));
			var str = "<div>" + String.fromCharCode(i + 65)
					+ ":&nbsp;<input type='checkbox' onclick='wls_question_done("+this.id+")' name='w_qs_"
					+ this.id + "_" + i + "' value='"
					+ String.fromCharCode(i + 65) + "' />&nbsp;"
					+ title;
			if (i != parseInt(this.questionData.optionlength) - 1) {
				str += "</div>";
			}
			$(".w_qw_options", "#w_qs_" + this.id).append(str);
		}
		this.cent = this.questionData.cent;
		this.questionData = null;
	},
	marking:function(){
		$(".w_qw_tool", $('#w_qs_' + this.id))
		.append("<a href='#' >批改</a>");
	},
	showDescription : function() {
		var obj = this.answerData;
		this.quiz.cent += parseFloat(obj.cent);
		this.quiz.count.total++;
		$(".w_qw_tool", $('#w_qs_' + this.id))
				.append("<a href='#' class='w_q_d_t' title='"
						+ il8n.question + il8n.description
						+ "' id='w_qs_d_t_" + this.id
						+ "' onclick='wls_question_toogle(" + this.id
						+ ")'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a>");
		$('#w_qs_' + this.id).append("<div class='w_q_d'>"
				+ obj.description + "</div>");
		$(".w_q_d_t", $('#w_qs_' + this.id)).addClass("w_q_d_t_2");

		if (obj.myAnswer == 'I_DONT_KNOW') {
			this.quiz.count.giveup++;
			var answerList = obj.answer.split(',');
			for (var i = 0; i < answerList.length; i++) {
				$(":checkbox[value='" + answerList[i] + "']",
						$("#w_qs_" + this.id)).parent()
						.addClass('w_qs_q_w');
			}

			$('#w_q_subQuesNav_' + this.id).addClass('w_q_sn_g');
			$('#w_q_subQuesNav_' + this.id).attr('title',
					il8n.giveup + ',' +il8n.cent + ':' + (this.cent));
			wls_question_toogle(this.id);
		} else if (obj.answer == obj.myAnswer) {
			this.quiz.count.right++;
			this.quiz.mycent += parseFloat(obj.cent);
			$('#w_q_subQuesNav_' + this.id).addClass('w_q_sn_r');
			$('#w_q_subQuesNav_' + this.id).attr('title',
				il8n.right + ',' + il8n.cent + ':' + (this.cent));
			wls_question_toogle(this.id);
		} else {
			this.quiz.count.wrong++;

			var answerList = obj.answer.split(',');
			for (var i = 0; i < answerList.length; i++) {
				$(":checkbox[value='" + answerList[i] + "']",
						$("#w_qs_" + this.id)).parent()
						.addClass('w_qs_q_w');
			}

			$('#w_q_subQuesNav_' + this.id).addClass('w_q_sn_w');
			$('#w_q_subQuesNav_' + this.id).attr('title',
					il8n.wrong + ',' + il8n.cent + ':' + (this.cent));
			if (this.quiz.type == 'paper'){
				this.addWhyImWrong();
			}
		}

		obj = null;
	},
	getMyAnswer : function() {
		var answer = '';

		var valueList = [];
		for (var i = 0; i < this.optionLength; i++) {
			var value = $('input[name=w_qs_' + this.id + "_" + i
					+ ']:checked').val();
			if (typeof(value) == 'undefined') {

			} else {
				valueList.push(value);
			}
		}

		if (valueList.length == 0) {
			answer = 'I_DONT_KNOW';
			this.quiz.count.giveup++;
		} else {
			answer = valueList.join(',');
		}
		return answer;
	},
	setMyAnswer : function() {
		var myAnswer = this.answerData.myAnswer;
		if (myAnswer != 'I_DONT_KNOW') {
			var answerList = this.answerData.myAnswer.split(',');
			for (var i = 0; i < answerList.length; i++) {
				$(":checkbox[value='" + answerList[i] + "']",
						$("#w_qs_" + this.id)).attr('checked',
						'checked');;
			}
		}
	}
});
