wls.quiz.log = Ext.extend(wls.quiz, {
	
	 type:'log'
	,id:null
	,logData:null
	,ajaxIds:function(nextFunction){
		var thisObj = this;
		$.blockUI({
			message: '<h1>'+il8n.Loading+'</h1><br/>'+il8n.Wait+'......' 
		}); 
		$.ajax({
			url: thisObj.config.AJAXPATH+"?controller=quiz_log&action=getOne",
			data: {id:thisObj.id},					
			type: "POST",
			success: function(msg){
				
				var obj = jQuery.parseJSON(msg);
				thisObj.logData = obj;
				thisObj.questionsIds = obj.id_question;	
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
				"<td width='50%'>"+il8n.DateCreated+"</td>" +
				"<td width='50%'>"+this.logData.date_created+"</td>" +	
				"</tr>" +
				"<tr>" +
				"<td>"+il8n.Score.Total+"</td>" +
				"<td>"+this.logData.cent+"</td>" +	
				"</tr>" +
				"<tr>" +
				"<td>"+il8n.Score.Score+"</td>" +
				"<td>"+this.logData.mycent+"</td>" +	
				"</tr>" +	
				"<tr>" +
				"<td>"+il8n.Count.Right+"</td>" +
				"<td>"+this.logData.count_right+"</td>" +	
				"</tr>" +	
				"<tr>" +
				"<td>"+il8n.Count.Wrong+"</td>" +
				"<td>"+this.logData.count_wrong+"</td>" +	
				"</tr>" +
				"<tr>" +
				"<td>"+il8n.Proportion+"</td>" +
				"<td>"+this.logData.proportion+"</td>" +	
				"</tr>" +
				"<tr>" +
				"<td>"+il8n.Application+"</td>" +
				"<td>"+this.logData.application+"</td>" +	
				"</tr>" +				
				"</table>";
		$("#paperBrief").append(str);
	}	
	,submit:function(nextFunction){
		if(this.state==4||this.state==42)return;

		var thisObj = this;
		$.ajax({
			url: thisObj.config.AJAXPATH+"?controller=quiz_log&action=getAnswers",
			data: {id:thisObj.id},
			type: "POST",
			success: function(msg){
				thisObj.state = 4;
				var obj = thisObj.answersData = jQuery.parseJSON(msg);
				for(var i=0;i<obj.length;i++){
					thisObj.questions[i].answerData = obj[i];
					thisObj.questions[i].setMyAnser();
				}
				thisObj.addDescriptions();
				eval(nextFunction);
			}
		});
	}
	,getListStuff:function(){
		var cm = new Ext.grid.ColumnModel({
		    defaults: {
		        sortable: true   
		    },
		    columns: [{
		             header: il8n.ID
		            ,dataIndex: 'id'
		        },{
		             header: "科目编号"
		            ,dataIndex: 'id_level_subject'
		            ,hidden:true
		        },{
		             header: il8n.Subject
		            ,dataIndex: 'name_subject'
		        },{
		             header: "题目数量"
		            ,dataIndex: 'count_questions'
		        },{
		             header: "做对数"
		            ,dataIndex: 'count_right'
		        },{
		             header: "做错数"
		            ,dataIndex: 'count_wrong'
		        },{
		             header: "对错率"
		            ,dataIndex: 'proportion'
		            ,hidden:true
		        },{
		             header: "得分"
		            ,dataIndex: 'mycent'
		        },{
		             header: "总分"
		            ,dataIndex: 'cent'
		        },{
		             header: "来源"
		            ,dataIndex: 'name_application'
		        }
		        
		    ]
		});
		
		return {cm:cm,fields:['id','date_created','id_level_subject','id_user', 'cent','mycent','count_right','count_wrong','count_giveup','count_total','name_subject','count_questions','name_application']};
	}
	,getList:function(domid){
		var listStuff = this.getListStuff();
		var thisObj = this;
		var store = new Ext.data.JsonStore({
		    autoDestroy: true,
		    url: thisObj.config.AJAXPATH+'?controller=quiz_log&action=jsonList',
		    root: 'data',
		    idProperty: 'id',
		    fields: listStuff.fields
		});
		
		var tb = new Ext.Toolbar({
			id:"w_q_lg_l_tb"
		});	
		
		var grid = new Ext.grid.GridPanel({
		    store: store,
		    cm: listStuff.cm,     
		    id: domid,
		    width: 600,
		    height: 300,
		    tbar: tb,
		    bbar : new Ext.PagingToolbar({
				store : store,
				pageSize : 15,
				displayInfo : true
			})
		});
		
		var privilege = user_.myUser.privilege.split(",");
		for(var i=0;i<privilege.length;i++){
			if(privilege[i]=='115101'){
				tb.add({
					text: il8n.Import,
			        handler : function(){   
						var win = new Ext.Window({
							id:'w_q_p_l_i',
							layout:'fit',
							width:500,
							height:300,	
							html: "<iframe src ='"+thisObj.config.AJAXPATH+"?controller=quiz_log&action=viewUpload' width='100%' height='250' />"
						});
						win.show(this);
					}
				});
			}else if(privilege[i]=='115102'){
				tb.add({
					text: il8n.Export,
			        handler : function(){   
						var win = new Ext.Window({
							id:'w_q_p_l_e',
							layout:'fit',
							width:500,
							height:300,	
							html: "<iframe src ='"+thisObj.config.AJAXPATH+"?controller=quiz_log&action=viewExport' width='100%' height='250' />"
						});
						win.show(this);
					}
				});
			}else if(privilege[i]=='115103'){
				tb.add({
					text: il8n.Delete,
			        handler : function(){   
						Ext.Ajax.request({				
							method:'POST',				
							url:thisObj.config.AJAXPATH+"?controller=quiz_log&action=delete",				
							success:function(response){				
							    store.load();
							},				
							failure:function(response){				
							    Ext.Msg.alert('failure',response.responseText);
							},				
							params:{id:Ext.getCmp(domid).getSelectionModel().selections.items[0].data.id}				
						});		
					}
				});
			}else if(privilege[i]=='115107'){
				tb.add({
			        text: il8n.Review+il8n.Log,
			        handler : function(){
						window.open(thisObj.config.AJAXPATH+"?controller=quiz_log&action=viewOne&id="+Ext.getCmp(domid).getSelectionModel().selections.items[0].data.id);
					}
			    });   
			}
		}
  
		store.load({params:{start:0, limit:8}});    
		return grid;
	}
	,getMyList:function(domid){
		var listStuff = this.getListStuff();
		var thisObj = this;
		var store = new Ext.data.JsonStore({
		    autoDestroy: true,
		    url: thisObj.config.AJAXPATH+'?controller=quiz_log&action=getMyList',
		    root: 'data',
		    idProperty: 'id',
		    fields: listStuff.fields
		});
		
		var tb = new Ext.Toolbar({
			id:"w_q_lg_ml_tb"
		});	
		
		var grid = new Ext.grid.GridPanel({
		    store: store,
		    cm: listStuff.cm,     
		    id: domid,
		    width: 600,
		    height: 300,
		    tbar: tb,
		    bbar : new Ext.PagingToolbar({
				store : store,
				pageSize : 15,
				displayInfo : true
			})
		});
		
		var privilege = user_.myUser.privilege.split(",");
		for(var i=0;i<privilege.length;i++){
			if(privilege[i]=='125301'){
				tb.add({
			        text: il8n.Review+il8n.Log,
			        handler : function(){
			        	
						var lid = Ext.getCmp(domid).getSelectionModel().selections.items[0].data.id;
						var uid = user_.myUser.id;
				        var desktop = QoDesk.App.getDesktop();
				        
				        var win = desktop.getWindow(lid+'_qdesk');							
			        	var winWidth = desktop.getWinWidth();
						var winHeight = desktop.getWinHeight();
	
						if(!win){
							win = desktop.createWindow({
								id:lid+'_qdesk',
								title: '查看练习记录',
								width: winWidth,
								height: winHeight,
								layout: 'fit',
								plain:false,
								html:'<iframe src="'+thisObj.config.AJAXPATH+"?controller=quiz_log&action=viewOne&id="+lid+"&uid="+uid+'&temp='+Math.random()+'" style="width:100%; height:100%;" frameborder="no" border="0" marginwidth="0" marginheight="0">'
							});
						}
					    win.show();				        	
			        	
			        	
//						window.open(thisObj.config.AJAXPATH+"?controller=quiz_log&action=viewOne&id="+Ext.getCmp(domid).getSelectionModel().selections.items[0].data.id);
					}
			    });   
			}
		}
  
		store.load({params:{start:0, limit:15}});    
		return grid;
	}

});
