Ext.define("PSI.ProcessDesign.AddOrEditProcessForm", {
  extend: "PSI.AFX.BaseDialogForm",
  initComponent: function () {
    let me = this;
    let entity = me.getEntity();
    let isEdit = !(entity["action"] == "add");
    let title = isEdit ? "编辑流程" : "新建流程";

    let f = isEdit ? "edit-form-update.png" : "edit-form-create.png";
    let logoHtml = "<img style='float:left;margin:10px 20px 0px 10px;width:48px;height:48px;' src='"
      + PSI.Const.BASE_URL
      + "Public/Images/"
      + f
      + "'></img>"
      + "<h2 style='color:#196d83'>"
      + title
      + "</h2>"
      + "<p style='color:#196d83'>标记 <span style='color:red;font-weight:bold'>*</span>的是必须录入数据的字段</p>";

    Ext.apply(me, {
      header: {
        title: me.formatTitle(title),
        height: 40
      },
      width: 400,
      height: 450,
      layout: "border",
      items: [
        {
          region: "north",
          border: 0,
          height: 90,
          html: logoHtml
        },
        {
          region: "center",
          border: 0,
          id: "PSI_ProcessDesign_AddOrEditForm",
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
            value: isEdit ? entity["Id"] : null,
          }, {
            id: "PSI_ProcessDesign_FlowName",
            fieldLabel: "流程名",
            allowBlank: false,
            blankText: "没有输入流程化名称",
            beforeLabelTextTpl: PSI.Const.REQUIRED,
            name: "flowName",
            value: isEdit ? entity["FlowName"] : null,
            listeners: {
              specialkey: {
                fn: me.onEditNameSpecialKey,
                scope: me
              }
            },
            width: 300
          },
            {
              id: "PSI_ProcessDesign_FileType",
              xtype: "combo",
              queryMode: "local",
              editable: false,
              valueField: "id",
              labelWidth: 60,
              labelAlign: "right",
              name: "fileType",
              labelSeparator: "",
              fieldLabel: "文件类型",
              margin: "5, 0, 0, 0",
              store: Ext.create("Ext.data.ArrayStore", {
                fields: ["id", "text"],
                data: [["file", "文件"], ["dir", "文件夹"]]
              }),
              value: isEdit ? entity["FileType"] : "file"
            },
            {
              id: "PSI_ProcessDesign_FlowType",
              xtype: "combo",
              queryMode: "local",
              editable: false,
              name: "flowType",
              valueField: "id",
              labelWidth: 60,
              labelAlign: "right",
              labelSeparator: "",
              fieldLabel: "流程类型",
              margin: "5, 0, 0, 0",
              store: Ext.create("Ext.data.ArrayStore", {
                fields: ["id", "text"],
                data: [["create", "新建"], ["edit", "修订"], ["delete", "删除"], ["voided", "作废"]]
              }),
              value: isEdit ? entity["FlowType"] : "create"
            },
            {
              id: "PSI_ProcessDesign_SortOrder",
              fieldLabel: "排序",
              name: "sortOrder",
              width: 200,
              value: isEdit ? entity["SortOrder"] : 0
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
        }]
    });

    me.callParent(arguments);

    me.__AddOrEditForm = Ext.getCmp("PSI_ProcessDesign_AddOrEditForm");
    me.__flowName = Ext.getCmp("PSI_ProcessDesign_FlowName");


  },
  onEditNameSpecialKey: function (field, e) {
    let me = this;
    if (e.getKey() == e.ENTER) {
      me.__flowName.focus();
    }
  },
  onOK: function () {
    let me = this;
    let form = me.__AddOrEditForm;
    let el = form.getEl();
    el.mask("数据保存中...");
    form.submit({
      url: me.URL("Home/ProcessDesign/saveFlow"),
      method: "POST",
      success: function (response) {
        el.unmask();
        me.close();
        me.getParentForm().freshProcessDesignGrid();
      },
      failure: function (form, action) {
        el.unmask();
        me.showInfo(action.result.msg);
      }
    });
  }

})
