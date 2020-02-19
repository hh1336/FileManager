Ext.define("PSI.Examine.ExamineWindow", {
  extend: "PSI.AFX.BaseDialogForm",
  initComponent: function () {
    let me = this;

    Ext.apply(me, {
      width: 900,
      height: 600,
      layout: "border",
      items: [{
        title: "审核",
        xtype: "panel",
        region: "west",
        layout: "fit",
        width: "40%",
        height: "30%",
        split: false,
        collapsible: false,
        border: 1,
        items: [me.getWestPanel()]
      }, {
        title: "审核进度",
        region: "center",
        xtype: "panel",
        height: "30%",
        layout: "fit",
        border: 1,
        items: [me.getCenterPanel()]
      }, {
        title: "文件信息",
        region: "south",
        xtype: "panel",
        layout: "fit",
        height: "70%",
        border: 1,
        items: [me.getSouthPanel()]
      }]
    });

    me.callParent(arguments);
  },
  //审核Panel
  getWestPanel: function () {
    let me = this;
    if (me.__west)
      return me.__west;

    let data = me.getEntity();
    console.log(data);
    me.__west = Ext.create("Ext.form.Panel", {
      header: false,
      border: 0,
      padding: "0 0 0 10",
      items: [{
        id: "remark",
        fieldLabel: "意见:",
        labelWidth: 30,
        anchor: '100%',
        xtype: "textarea",
        autoScroll: true,
        value: data['remark'],
      }, {
        xtype: "button",
        disabled: false,
        text: "通过",
        margin: "10 0 0 10",
        handler: me.onPass
      }, {
        xtype: "button",
        disabled: false,
        margin: "10 0 0 10",
        text: "不通过",
        handler: me.onFail
      }, {
        xtype: "button",
        disabled: false,
        margin: "10 0 0 10",
        text: "打回",
        handler: me.onBack
      }, {
        xtype: "button",
        disabled: false,
        margin: "10 0 0 10",
        text: "通过并结束流程",
        handler: me.onPassEnd
      }]
    });

    return me.__west;

  },
  //审核进度Panel
  getCenterPanel: function () {
    let me = this;
    if (me.__center)
      return me.__center;

    let data = me.getEntity();

    let modelName = "CenterModel";
    Ext.define(modelName, {
      extend: "Ext.data.Model",
      fields: ["sponsorUser", "remark", "status", "bltime"]
    });

    let Store = Ext.create('Ext.data.Store', {
      autoLoad: true,
      pageSize: 10,
      model: modelName,
      proxy: {
        type: "ajax",
        url: me.URL("Home/Examine/flowAdvance"),
        actionMethods: {
          read: "POST"
        },
        extraParams: {
          runProcessId: data['id']
        },
        reader: {
          type: 'json',
          root: 'dataList',
          totalProperty: 'totalCount'
        }
      }
    });

    me.__center = me.__grid = Ext.create("Ext.grid.Panel", {
      cls: "PSI",
      border: 1,
      columnLines: true,
      store: Store,
      columns: [
        {xtype: "rownumberer", width: "10%", header: "序号"},
        {header: '审批人', width: "15%", dataIndex: 'sponsorUser', sortable: false, menuDisabled: true},
        {header: '操作', width: "20%", dataIndex: 'status', sortable: false, menuDisabled: true},
        {header: '审核时间', width: "25%", dataIndex: "bltime", sortable: false, menuDisabled: true},
        {header: '审批意见', width: "30%", dataIndex: 'remark', sortable: false, menuDisabled: true}
      ]
    });

    return me.__center;
  },
  //文件信息Panel
  getSouthPanel: function () {

  },
  //通过
  onPass: function () {

  },
  //不通过
  onFail: function () {

  },
  //打回
  onBack: function () {

  },
  //通过并结束流程
  onPassEnd: function () {

  }
});
