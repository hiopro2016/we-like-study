class_23 = Ext.extend(Ext.app.Module, {
   id: 'id_23',

   init : function(){

   },
	
	createWindow : function(){
        var desktop = this.app.getDesktop();
        var win = desktop.getWindow(this.id);
        
        if(!win){			
            win = desktop.createWindow({
                id: this.id,
                title: '用户登录',
                width: 300,
                height: 250,
                iconCls: 'tab-icon',
                shim: false,
                constrainHeader: true,
                layout: 'fit',
                modal:true,
                items:[user_.getLogin()],
                taskbuttonTooltip: '<b>用户登录</b>'
            });
        }
        win.show();
    }
});