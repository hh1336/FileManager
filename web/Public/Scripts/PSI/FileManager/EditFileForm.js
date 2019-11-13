/**
 * 上传文件
 **/
Ext.define("PSI.FileManager.EditFileForm", {
  extend: "PSI.AFX.BaseDialogForm",
  config: {
    filePath: null
  },
  initComponent: function () {
    var me = this;
    var entity = me.getEntity();
    console.log(entity);

    var t = "编辑文件";
    var f = "edit-form-create.png";
    var logoHtml = "<img style='float:left;margin:10px 20px 0px 10px;width:48px;height:48px;' src='"
      + PSI.Const.BASE_URL
      + "Public/Images/"
      + f
      + "'></img>"
      + "<h2 style='color:#196d83'>"
      + t
      + "</h2>";

    Ext.apply(me, {
      header: {
        title: me.formatTitle(PSI.Const.PROD_NAME),
        height: 40
      },
      width: 600,
      height: 330,
      layout: "border",
      items: [{
        region: "north",
        border: 0,
        height: 90,
        html: logoHtml
      }, {
        region: "center",
        border: 0,
        id: "PSI_FileManager_EditFileForm",
        xtype: "form",
        fileUpload: true,
        frame: true,
        layout: {
          type: "table",
          columns: 1
        },
        height: "100%",
        bodyPadding: 5,
        defaultType: 'textfield',
        value: entity["Name"],
        fieldDefaults: {
          labelWidth: 100,
          labelAlign: "right",
          labelSeparator: "",
          msgTarget: 'side'
        },
        items: [{
          xtype: 'filefield',
          fieldLabel: '新文件：',
          id: 'uploadinput',
          name: 'file',
          blankText: '请上传文件',
          width: 450,
          size: 40,
          listeners: {
            change: function (fileinfo, opts) {
              me.setFilePath(opts);
            }
          }
        }, {
          id: "PSI_FileManager_EditFileForm_fileId",
          xtype: "hidden",
          name: "fileId",
          value: entity["id2"]
        },
          {
            id: "PSI_FileManager_EditFileForm_fileName",
            xtype: "displayfield",
            fieldLabel: "文件名称:",
            name: "fileName",
            value: entity["Name"]
          },
          {
            fieldLabel: "文件编码:",
            id: "fileCode",
            name:"fileCode",
            width: 450,
            value: entity["fileCode"]
          },
          {
            id: "PSI_FileManager_EditFileForm_actionInfo",
            fieldLabel: "描述:",
            xtype: "textarea",
            name: "actionInfo",
            width: 450,
            value: entity["actionInfo"]
          }],
        buttons: [{
          text: "确定",
          formBind: true,
          iconCls: "PSI-button-ok",
          handler: me.onOK,
          scope: me
        }, {
          text: "取消",
          handler: function () {
            me.confirm("请确认是否取消操作?", function () {
              me.close();
            });
          },
          scope: me
        }]
      }],
      listeners: {
        close: {
          fn: me.onWndClose,
          scope: me
        }
      }
    });

    me.callParent(arguments);
    me.editParentDirId = Ext.getCmp("PSI_FileManager_EditFileForm_fileId");
    me.editActionInfo = Ext.getCmp("PSI_FileManager_EditFileForm_actionInfo");

    me.editForm = Ext.getCmp("PSI_FileManager_EditFileForm");
  },
  onWindowBeforeUnload: function (e) {
    return (window.event.returnValue = e.returnValue = '确认离开当前页面？');
  },
  onWndClose: function () {
    var me = this;

    Ext.get(window).un('beforeunload', me.onWindowBeforeUnload);
  },
  onOK: function () {
    var me = this;
    var f = me.editForm;
    var el = f.getEl();
    el.mask("数据保存中...");
    f.submit({
      url: me.URL("Home/FileManager/editFile"),
      method: "POST",
      params: {
        path: me.getFilePath(),
        fileName:me.getEntity().Name
      },
      success: function (form, action) {
        el.unmask();
        me.close();
        me.getParentForm().freshFileGrid();
      },
      failure: function (form, action) {
        el.unmask();
        me.close();
        me.showInfo(action.result.msg);
      }
    });
  }
});
