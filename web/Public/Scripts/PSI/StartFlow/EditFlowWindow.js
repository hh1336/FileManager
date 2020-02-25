Ext.define("PSI.StartFlow.EditFlowWindow", {
  extend: "PSI.AFX.BaseDialogForm",
  initComponent: function () {
    let me = this;
    let entity = me.getEntity();
    let params_json = me.decodeJSON(entity['json']);
    Ext.apply(me, {
      cls: "PSI",
      header: {
        title: me.formatTitle("编辑工作流"),
        height: 40
      },
      width: 600,
      height: 500,
      layout: "border",
      items: [{
        region: "center",
        border: 0,
        id: "flowForm",
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
          id: "id",
          value: entity['id']
        }, {
          id: "RunName",
          fieldLabel: "流程名",
          allowBlank: false,
          blankText: "没有输入流程名",
          beforeLabelTextTpl: PSI.Const.REQUIRED,
          padding: "15 0 0 0",
          name: "runName",
          value: entity["runName"],
          listeners: {
            specialkey: {
              fn: me.onEditNameSpecialKey,
              scope: me
            }
          },
          width: 370
        },
          {
            fieldLabel: "操作类型",
            disabled: true,
            width: 370,
            padding: "15 0 0 0",
            value: entity['action']
          },
          {
            fieldLabel: "选择流程",
            width: 370,
            xtype: "combo",
            id: "FlowId",
            padding: "15 0 0 0",
            name: "flowId",
            editable: false,
            valueField: "id",
            labelWidth: 80,
            store: Ext.create("Ext.data.ArrayStore", {
              fields: ["id", "text"],
              data: entity['flows']
            }),
            value: entity['flowId']
          },
          {
            id: "IsUrgent",
            xtype: "checkbox",
            padding: "15 0 0 0",
            fieldLabel: "是否需要加急",
            labelWidth: 90,
            name: "isUrgent",
            checked: ~~entity['isUrgent']
          },
          {
            id: "NextProcessUser",
            fieldLabel: "下一步审核人",
            labelWidth: 90,
            disabled: true,
            padding: "15 0 0 0",
            width: 370,
            value: entity['nextProcessUsers']
          },
          {
            id: "Remark",
            xtype: "textareafield",
            fieldLabel: "备注",
            labelWidth: 50,
            disabled: false,
            padding: "15 0 0 0",
            width: "60%",
            height: 50,
            value: entity['remark']
          },
          {
            disabled: true,
            width: 600,
          },
          {
            xtype: "panel",
            width: "100%",
            id: "FileInfo",
            height: 105,
            listeners: {
              "afterrender": {
                fn: function () {
                  Ext.get('FileName').on("click", me.onPreview, me);
                },
                scope: me
              }
            },
            html: "<a href='#' id='FileName'>" + params_json['name'] + "</a>"
          }],
        buttons: [{
          text: "确定",
          //formBind: true,
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
      }]
    });

    me.callParent(arguments);
    me.__fileInfo = params_json;
  },
  //错误提示
  onEditNameSpecialKey: function (field, e) {
    var me = this;
    if (e.getKey() == e.ENTER) {
      me.editParentDir.focus();
    }
  },
  //保存流信息
  onOK: function () {
    let me = this;
    let params = {};
    params['id'] = Ext.getCmp("id").getValue();
    params['runName'] = Ext.getCmp("RunName").getValue();
    params['flowId'] = Ext.getCmp("FlowId").getValue();
    params['isUrgent'] = Ext.getCmp("IsUrgent").getValue();
    params['remark'] = Ext.getCmp("Remark").getValue();
    me.ajax({
      url: me.URL("/Home/StartFlow/saveFlow"),
      params: params,
      success: function (response) {
        let data = me.decodeJSON(response['responseText']);
        me.showInfo(data['msg']);
        me.getParentForm().freshGrid();
        me.close();

      }
    });

  },
  //点击需要预览文件
  onPreview: function () {
    let me = this;
    let entity = me.getEntity();
    me.ajax({
      url: me.URL("/Home/StartFlow/previewFile"),
      params: {
        flowId: entity['id'],
        filePath: me.__fileInfo['path'],
        ext: me.__fileInfo['suffix'],
        fileName: me.__fileInfo['save_name']
      },
      success: function (response) {
        let data = me.decodeJSON(response['responseText']);
        if (data['success']) {
          let url = me.URL(data['msg']);
          window.open(url);
          return;
        }
        me.showInfo(data['msg']);
      }
    });
  }

});
