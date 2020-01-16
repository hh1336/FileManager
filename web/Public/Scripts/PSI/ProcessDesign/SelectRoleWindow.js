Ext.define("PSI.ProcessDesign.SelectRoleForm", {
  extend: "PSI.AFX.BaseDialogForm",
  title: "请选择角色",
  width: 600,
  height: 500,
  modal: true,
  layout: "fit",
  initComponent: function () {
    let me = this;

    let modelName = "RserModel";
    Ext.define(modelName, {
      extend: "Ext.data.Model",
      fields: ["id", "name", "code"]
    });

    let roleStore = Ext.create("Ext.data.Store", {
      model: modelName,
      autoLoad: true,
      proxy: {
        type: "ajax",
        actionMethods: {
          read: "POST"
        },
        url: me.URL("Home/FileManager/loadRole"),
        reader: {
          type: 'json'
        }
      }
    });

    let grid = Ext.create('Ext.grid.Panel', {
      cls: "PSI",
      header: {
        height: 30,
        title: me.formatGridHeaderTitle("角色")
      },
      store: roleStore,
      features: [{ftype: "summary"}],
      border: 0,
      columnLines: true,
      selModel: Ext.create('Ext.selection.CheckboxModel', {mode: "SINGLE"}),
      columns: [{
        header: "编码",
        dataIndex: "code",
        width: "30%",
        menuDisabled: true
      }, {
        header: "角色名称",
        dataIndex: "name",
        flex: 1,
        menuDisabled: true
      }]
    });

    me.__grid = grid;

    Ext.apply(me, {
      items: [grid],
      buttons: [{
        text: "确定",
        formBind: true,
        iconCls: "PSI-button-ok",
        handler: me.onOK,
        scope: me
      }, {
        text: "取消",
        handler: function () {
          me.close();
        },
        scope: me
      }]
    });

    me.callParent(arguments);
  },
  onOK: function () {
    let me = this;
    let grid = this.__grid;
    let model = grid.getSelectionModel();
    let checkeds = model.getSelection();
    let items = [];
    for (let i = 0, len = checkeds.length; i < len; i++) {
      if (checkeds[i]["data"]["id"]) {
        items.push(checkeds[i]);
      }
    }
    me.getParentForm().setCheckRole(items);
    this.close();
  }

});
