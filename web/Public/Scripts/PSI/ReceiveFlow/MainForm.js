Ext.define('PSI.ReceiveFlow.MainForm', {
  extend: "PSI.AFX.BaseMainExForm",
  config: {},
  initComponent: function () {
    let me = this;
    Ext.apply(me, {
      tbar: [
        {
          text: "接收流程",
          handler: me.onReceive,
          scope: me
        }, {
          text: "查看详情",
          handler: me.onSelectInfo,
          scope: me
        },
        {
          text: "刷新",
          handler: me.freshGrid,
          scope: me
        }
      ],
      items: [
        {
          id: "panelQueryCmp",
          region: "north",
          border: 0,
          height: 35,
          header: false,
          collapsible: true,
          collapseMode: "mini",
          layout: {
            type: "table",
            columns: 4
          },
          items: me.getQueryCmp()
        },
        {
          id: "panelCensorInfo",
          xtype: "panel",
          region: "west",
          layout: "fit",
          width: "100%",
          split: true,
          collapsible: true,
          header: false,
          border: 0,
          items: [me.getGrid()]
        }]
    });
    me.callParent(arguments);
  },
  //搜索栏
  getQueryCmp: function () {
    let me = this;
    return [{
      id: "editQueryName",
      labelWidth: 60,
      labelAlign: "right",
      labelSeparator: "",
      fieldLabel: "名称",
      margin: "5, 0, 0, 0",
      xtype: "textfield"
    }, {
      id: "editType",
      xtype: "combo",
      queryMode: "local",
      editable: true,
      valueField: "id",
      labelWidth: 60,
      labelAlign: "right",
      labelSeparator: "",
      fieldLabel: "状态",
      margin: "5, 0, 0, 0",
      store: Ext.create("Ext.data.ArrayStore", {
        fields: ["id", "text"],
        data: [["0", "正常"], ["1", "加急"]]
      }),
      value: ""
    },
      {
        xtype: "container",
        items: [{
          xtype: "button",
          text: "查询",
          width: 100,
          height: 26,
          margin: "5, 0, 0, 20",
          handler: me.freshGrid,
          scope: me
        }, {
          xtype: "button",
          text: "清空查询条件",
          width: 100,
          height: 26,
          margin: "5, 0, 0, 5",
          handler: me.onClearQuery,
          scope: me
        }, {
          xtype: "button",
          text: "隐藏查询条件栏",
          width: 130,
          height: 26,
          iconCls: "PSI-button-hide",
          margin: "5 0 0 10",
          handler: function () {
            Ext.getCmp("panelQueryCmp").collapse();
          },
          scope: me
        }]
      }];
  },
  //主页面
  getGrid: function () {
    let me = this;
    if (me.__grid) {
      return me.__grid;
    }
    let modelName = "ReceiveFlowModel";
    Ext.define(modelName, {
      extend: "Ext.data.Model",
      fields: ["id", "runId", "processId", "flowId", "remark", "runName",
        "isUrgent", "isSing", "parentSponsorText", "parentProcessId", "uId", "uName"]
    });

    let Store = Ext.create('Ext.data.Store', {
      autoLoad: true,
      pageSize: 10,
      model: modelName,
      proxy: {
        type: "ajax",
        url: me.URL("Home/ReceiveFlow/loadData"),
        actionMethods: {
          read: "POST"
        },
        extraParams: {},
        reader: {
          type: 'json',
          root: 'dataList',
          totalProperty: 'totalCount'
        }
      }
    });

    me.__grid = Ext.create("Ext.grid.Panel", {
      cls: "PSI",
      border: 1,
      columnLines: true,
      viewConfig: {
        enableTextSelection: true
      },
      store: Store,
      features: [{ftype: "summary"}],
      columns: [
        {xtype: "rownumberer", width: "10%", header: "序号"},
        {
          header: "流程名",
          dataIndex: "runName",
          menuDisabled: true,
          sortable: false,
          width: "25%"
        }, {
          header: "发起人",
          dataIndex: "uName",
          menuDisabled: true,
          sortable: false,
          width: "10%"
        }, {
          header: "是否加急",
          dataIndex: "isUrgent",
          menuDisabled: true,
          sortable: false,
          width: "10%",
          renderer: function (value) {
            return ~~value ? "是" : "否";
          }
        }, {
          header: "上一步审核",
          dataIndex: "parentSponsorText",
          menuDisabled: true,
          sortable: false,
          width: "20%"
        }, {
          header: "备注",
          dataIndex: "remark",
          menuDisabled: true,
          sortable: false,
          width: "25%"
        }
      ]
    });

    return me.__grid;

  },
  //刷新数据
  freshGrid: function () {
    let me = this;
    let store = me.getGrid().getStore();
    store.proxy.extraParams = {
      queryName: Ext.getCmp("editQueryName").getValue(),
      queryType: Ext.getCmp("editType").getValue()
    };
    store.reload();
  },
  //清空查询条件
  onClearQuery: function () {
    Ext.getCmp("editQueryName").setValue("");
    Ext.getCmp("editType").setValue("");
  },
  //得到选中的数据
  getSelectNodeData: function (action) {
    let me = this;
    let grid = me.__grid;
    let selected = grid.getSelectionModel().selected;
    let id = selected.keys[0];
    if (!selected.map[id]) {
      return {};
    }
    let data = selected.map[id].data;
    data["action"] = action;
    return data;
  },
  //查看详情
  onSelectInfo: function () {
  },
  //接收流程
  onReceive: function () {
    let me = this;
    let data = me.getSelectNodeData();
    if (Ext.JSON.encode(data) == "{}")
      return me.showInfo("请先选择数据");
    me.ajax({
      url: me.URL("Home/ReceiveFlow/receive"),
      params: {
        id: data['id'],
        isSing: data['isSing']
      },
      success: function (response) {
        let data = me.decodeJSON(response['responseText']);
        me.showInfo(data['msg']);
        me.freshGrid();
      }
    })
  },
});
