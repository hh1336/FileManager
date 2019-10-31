/**
 * 新增或编辑文件夹
 **/
Ext.define("PSI.FileManager.DirEditForm", {
  extend: "PSI.AFX.BaseDialogForm",
  initComponent: function () {
    var me = this;
    var entity = me.getEntity();

    var t = entity["action"] == "add" ? "新增文件夹" : "编辑文件夹";
    var f = entity["action"] == "edit"
      ? "edit-form-update.png"
      : "edit-form-create.png";
    var logoHtml = "<img style='float:left;margin:10px 20px 0px 10px;width:48px;height:48px;' src='"
      + PSI.Const.BASE_URL
      + "Public/Images/"
      + f
      + "'></img>"
      + "<h2 style='color:#196d83'>"
      + t
      + "</h2>"
      + "<p style='color:#196d83'>标记 <span style='color:red;font-weight:bold'>*</span>的是必须录入数据的字段</p>";

    Ext.apply(me, {
      header: {
        title: me.formatTitle(PSI.Const.PROD_NAME),
        height: 40
      },
      width: 400,
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
        id: "PSI_FileManager_DirEditForm_editForm",
        xtype: "form",
        layout: {
          type: "table",
          columns: 1
        },
        height: "100%",
        bodyPadding: 5,
        defaultType: 'textfield',
        fieldDefaults: {
          labelWidth: 60,
          labelAlign: "right",
          labelSeparator: "",
          msgTarget: 'side'
        },
        items: [{
          xtype: "hidden",
          name: "id",
          value: entity["action"] == "add" ? null : entity["id2"]
        }, {
          id: "PSI_FileManager_DirEditForm_editName",
          fieldLabel: "文件夹名",
          allowBlank: false,
          blankText: "没有输入文件名",
          beforeLabelTextTpl: PSI.Const.REQUIRED,
          name: "dirName",
          value: entity["action"] == "add" ? null : entity["Name"],
          listeners: {
            specialkey: {
              fn: me.onEditNameSpecialKey,
              scope: me
            }
          },
          width: 370
        }, {
          id: "PSI_FileManager_DirEditForm_editParentDir",
          xtype: "displayfield",
          fieldLabel: "上级目录:",
          width: 370,
          value: ""
        }, {
          id: "PSI_FileManager_DirEditForm_editParentDirId",
          xtype: "hidden",
          name: "parentDirID",
          value: ((entity["action"] == "add") && (entity["Name"] != "../")) ? entity["id2"] : entity["parentDirID"]
        }, {
          id: "PSI_FileManager_DirEditForm_editActionInfo",
          fieldLabel: "描述",
          name: "actionInfo",
          width: 370,
          value: entity["action"] == "add" ? null : entity["actionInfo"]
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
        show: {
          fn: me.onEditFormShow,
          scope: me
        },
        close: {
          fn: me.onWndClose,
          scope: me
        }
      }
    });

    me.callParent(arguments);
    me.editParentDir = Ext.getCmp("PSI_FileManager_DirEditForm_editParentDir");
    me.editParentDirId = Ext.getCmp("PSI_FileManager_DirEditForm_editParentDirId");
    me.editName = Ext.getCmp("PSI_FileManager_DirEditForm_editName");
    me.editActionInfo = Ext.getCmp("PSI_FileManager_DirEditForm_editActionInfo");

    me.editForm = Ext.getCmp("PSI_FileManager_DirEditForm_editForm");
  },
  onWindowBeforeUnload: function (e) {
    return (window.event.returnValue = e.returnValue = '确认离开当前页面？');
  },
  onWndClose: function () {
    var me = this;

    Ext.get(window).un('beforeunload', me.onWindowBeforeUnload);
  },
  onEditFormShow: function () {
    var me = this;
    Ext.get(window).on('beforeunload', me.onWindowBeforeUnload);

    me.editName.focus();

    var entity = me.getEntity();
    var el = me.getEl() || Ext.getBody();
    el.mask("数据加载中...");
    if (entity["action"] == "edit") {
      me.ajax({
        url: me.URL("Home/FileManager/dirParentName"),
        params: {
          id: entity["parentDirID"]
        },
        callback: function (options, success, response) {
          el.unmask();
          if (success) {
            var data = Ext.JSON.decode(response.responseText);
            me.editParentDirId.setValue(entity.parentDirID);
            if (data.parentDirName == "/") {
              me.editParentDir.setValue("/ (根目录)");
            } else {
              me.editParentDir.setValue("../" + data.parentDirName);
            }

          }
        }
      });
    } else {
      el.unmask();
      //me.editParentDirId.setValue(entity["id2"]);
      me.editParentDir.setValue(entity["Name"] || "根目录");
    }


  },
  onEditNameSpecialKey: function (field, e) {
    var me = this;
    if (e.getKey() == e.ENTER) {
      me.editParentDir.focus();
    }
  },
  onOK: function () {
    var me = this;
    var f = me.editForm;
    var el = f.getEl();
    el.mask("数据保存中...");
    f.submit({
      url: me.URL("Home/FileManager/mkDirOrEdit"),
      method: "POST",
      success: function (form, action) {
        el.unmask();
        me.close();
        me.getParentForm().freshFileGrid();
      },
      failure: function (form, action) {
        el.unmask();
        me.showInfo(action.result.msg, function () {
          me.editName.focus();
        });
      }
    });
  }
});
