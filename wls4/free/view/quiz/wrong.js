wls.quiz.wrong = Ext.extend(wls.quiz, {
	
	 type:'wrong'
	,id:null
	,wrongData:null
	,time:{
		 start:null
		,stop:null
		,used:0
	}
	,ajaxIds:function(nextFunction){
		var thisObj = this;
		$.blockUI({
			message: '<h1>'+il8n.Loading+'</h1><br/>'+il8n.Wait+'......' 
		}); 
		$.ajax({
			url: thisObj.config.AJAXPATH+"?controller=quiz_wrong&action=getOne",
			data: {id:thisObj.id},					
			type: "POST",
			success: function(msg){
				var obj = jQuery.parseJSON(msg);
				thisObj.wrongData = obj.data[0];
				thisObj.questionsIds = obj.data[0].questions;	
				thisObj.state = 1;
				thisObj.addQuizBrief();
				thisObj.addNavigation();
				$.unblockUI();				

				eval(nextFunction);				
			}
		});
	}
	,addQuizBrief:function(){
		var str = "<table width='90%'>" +
				"<tr>" +
				"<td>"+il8n.Name+"</td>" +
				"<td>aaa</td>" +	
				"</tr>" +

				"</table>";
		$("#wrongBrief").append(str);
		var thisObj = this;
		Ext.getCmp('ext_Operations').layout.setActiveItem('ext_Brief');
		wls_quiz_wrong_clock = setInterval(function() {
			thisObj.time.used ++;
		   $('#clock').text(thisObj.get_elapsed_time_string(thisObj.time.used));
		}, 1000);
	}	
	,submit:function(nextFunction){
		$.blockUI({
			message: '<h1>'+il8n.wrong.wrong+il8n.Submit+'</h1><br/>'+il8n.Wait+'......' 
		}); 
		
		this.answersData = [];
		for(var i=0;i<this.questions.length;i++){
			this.answersData.push({
				 id:this.questions[i].id
				,answer:this.questions[i].getMyAnswer()
			});
		}
		var thisObj = this;
	
		$.ajax({
			url: thisObj.config.AJAXPATH+"?controller=quiz_wrong&action=getAnswers",
			data: {answersData:thisObj.answersData},
			type: "POST",
			success: function(msg){
				$.unblockUI();
				var obj = thisObj.answersData = jQuery.parseJSON(msg);
				for(var i=0;i<obj.length;i++){
					thisObj.questions[i].answerData = obj[i];
				}
				
				//console.debug(nextFunction);
				eval(nextFunction);
				thisObj.showResult();
			}
		});
	}
	
	,showResult:function(){

		var str = "<table width='90%'>" +
				"<tr>" +
				"<td>"+il8n.Score.Score+"</td>" +
				"<td>"+this.mycent+"</td>" +	
				"</tr>" +				
				"<tr>" +
				"<td>"+il8n.Score.Total+"</td>" +
				"<td>"+this.cent+"</td>" +	
				"</tr>" +				
				"<tr>" +
				"<td>"+il8n.Count.Right+"</td>" +
				"<td>"+this.count.right+"</td>" +	
				"</tr>" +				
				"<tr>" +
				"<td>"+il8n.Count.Wrong+"</td>" +
				"<td>"+this.count.wrong+"</td>" +	
				"</tr>" +				
				"<tr>" +
				"<td>"+il8n.Count.GiveUp+"</td>" +
				"<td>"+this.count.giveup+"</td>" +	
				"</tr>" +
				"<tr>" +
				"<td>"+il8n.Count.Total+"</td>" +
				"<td>"+this.count.total+"</td>" +	
				"</tr>" +
				"</table>";
		var ac = Ext.getCmp('ext_Operations');
		
		ac.layout.activeItem.collapse(false);
		
		ac.add({
	    	id:'ext_wrongResult',
	        title: il8n.wrong.Result,
	        html: '<div id="wrongresult">aaa</div>'			
		});
		ac.doLayout();
		
		$("#wrongresult").empty();
		$("#wrongresult").append(str);
		
		$.blockUI({
			message: str 
		}); 
		 $('.blockOverlay').attr('title','Click to unblock').click($.unblockUI); 
	}
	,getList:function(domid){
		var thisObj = this;
		var store = new Ext.data.JsonStore({
		    autoDestroy: true,
		    url: thisObj.config.AJAXPATH+'?controller=quiz_wrong&action=jsonList',
		    root: 'data',
		    idProperty: 'id',
		    fields: ['id','id_level_subject','id_quiz_paper','date_created','timedif','subject_name','count']
		});
		
		var cm = new Ext.grid.ColumnModel({
		    defaults: {
		        sortable: true   
		    },
		    columns: [{
		             header: il8n.ID
		            ,dataIndex: 'id'
		        },{
		             header: il8n.Subject
		            ,dataIndex: 'id_level_subject'
		            ,hidden:true
		        },{
		             header: il8n.Paper.Paper
		            ,dataIndex: 'id_quiz_paper'
		            ,hidden:true
		        },{
		             header: il8n.DateCreated
			        ,dataIndex: 'date_created'
			        ,hidden:true
			    },{
		             header: il8n.DateCreated
			        ,dataIndex: 'timedif'
			    },{
		             header: il8n.Subject
			        ,dataIndex: 'subject_name'
			    },{
		             header: il8n.Count.Wrong
			        ,dataIndex: 'count'
			    }
		    ]
		});
		var grid = new Ext.grid.GridPanel({
			title:il8n.Wrongs,
		    store:store,
		    cm: cm,        
		    id: domid,
		    width: 600,
		    height: 300,
		    tbar: [{
		        text: il8n.Import,
		        handler : function(){   
					var win = new Ext.Window({
						id:'w_q_p_l_i',
						layout:'fit',
						width:500,
						height:300,	
						html: "<iframe src ='"+thisObj.config.AJAXPATH+"?controller=quiz_wrong&action=viewUpload&temp="+Math.random()+"' width='100%' height='250' />"
					});
					win.show(this);
				}
		    },{
		        text: il8n.Export,
		        handler : function(){   
					var win = new Ext.Window({
						id:'w_u_l_e',
						layout:'fit',
						width:500,
						height:300,	
						html: "<iframe src ='"+thisObj.config.AJAXPATH+"?controller=quiz_wrong&action=viewExport&id="+Ext.getCmp(domid).getSelectionModel().selection.record.id+"&temp="+Math.random()+"' width='100%' height='250' />"
					});
					win.show(this);
				}
		    },{
		        text: il8n.Delete,
		        handler : function(){
			        Ext.Ajax.request({				
						method:'POST',				
						url:thisObj.config.AJAXPATH+"?controller=quiz_wrong&action=delete",				
						success:function(response){				
						    store.load();
						},				
						failure:function(response){				
						    Ext.Msg.alert('failure',response.responseText);
						},				
						params:{id:Ext.getCmp(domid).getSelectionModel().selection.record.id}				
					});					
				}
		    },{
		        text: il8n.DoQuiz,
		        handler : function(){
					window.open(thisObj.config.AJAXPATH+"?controller=quiz_wrong&action=viewOne&id="+Ext.getCmp(domid).getSelectionModel().selection.record.id);
				}
		    }],
		    bbar : new Ext.PagingToolbar({
				store : store,
				pageSize : 8,
				displayInfo : true
			})
		});
		grid.on("afteredit", afteredit, grid);
		function afteredit(e){    
	        Ext.Ajax.request({				
				method:'POST',				
				url:thisObj.config.AJAXPATH+"?controller=quiz_wrong&action=saveUpdate",				
				success:function(response){				
				    //Ext.Msg.alert('success',response.responseText);
				},				
				failure:function(response){				
				    Ext.Msg.alert('failure',response.responseText);
				},				
				params:{field:e.field,value:e.value,id:e.record.data.id}				
			});
	    }     
		store.load({params:{start:0, limit:8}});    
		return grid;
	}

});
