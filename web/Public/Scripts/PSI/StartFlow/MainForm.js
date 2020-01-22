Ext.define('PSI.StartFlow.MainForm', {
  extend: "PSI.AFX.BaseMainExForm",
  config: {},
  initComponent: function () {
    let me = this;
    Ext.apply(me, {
      tbar: [
        {
          text: "编辑流程",
          handler: me.onEditProcess,
          scope: me
        }, {
          text: "查看详情",
          handler: me.onSelectInfo,
          scope: me
        },
        {
          text: "启动工作流",
          handler: me.onStartFlow,
          scope: me
        },
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
          items: [me.getCensorGrid()]
        }]
    });

    me.ajax({
      url: me.URL("/Home/StartFlow/loadCheckFlow"),
      success: function (resposne) {
        me.__flows = me.decodeJSON(resposne['responseText']);
      }
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
        data: [["0", "未启动"], ["1", "流程中"], ["2", "通过"], ["2", "退回"]]
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
  //获取主面板
  getCensorGrid: function () {
    let me = this;
    if (me.__grid)
      return me.__grid;

    let modelName = "StartFlowModel";
    Ext.define(modelName, {
      extend: "Ext.data.Model",
      fields: ["id", "flowId", "action", "runName", "json", "isUrgent",
        "nextProcessUsers", "updatetime", "status"]
    });

    let Store = Ext.create('Ext.data.Store', {
      autoLoad: true,
      pageSize: 10,
      model: modelName,
      proxy: {
        type: "ajax",
        url: me.URL("Home/StartFlow/loadRunFlow"),
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
      header: {
        height: 30,
        title: me.formatGridHeaderTitle("所有流程")
      },
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
        },
        {
          header: "操作类型",
          dataIndex: "action",
          menuDisabled: true,
          sortable: false,
          width: "15%"
        },
        {
          header: "下一步审核人",
          dataIndex: "nextProcessUsers",
          menuDisabled: true,
          sortable: false,
          width: "20%"
        },
        {header: "更新时间", dataIndex: "updatetime", menuDisabled: true, sortable: false, width: "20%"},
        {
          header: "状态",
          dataIndex: "status",
          menuDisabled: true,
          sortable: false,
          width: "10%",
          renderer: function (value) {
            switch (value) {
              case "0":
                value = "未启动";
                break;
              case "1":
                value = "流程中";
                break;
              case "2":
                value = "已通过";
                break;
              case "3":
                value = "退回";
                break;
              default:
                break;
            }
            return value;
          }
        }
      ]
    });

    return me.__grid;

  },
  //得到选中的数据
  getSelectNodeData: function () {
    var me = this;
    var panel = me.__grid;
    var selected = panel.getSelectionModel().selected;
    var id = selected.keys[0];
    if (!selected.map[id]) {
      return {};
    }
    var data = selected.map[id].data;
    return data;
  },
  //刷新数据
  freshGrid: function () {
    let me = this;
    let store = me.getCensorGrid().getStore();
    store.proxy.extraParams = {
      ProcessName: Ext.getCmp("editQueryName").getValue(),
      ProcessType: Ext.getCmp("editType").getValue()
    };
    store.reload();
  },
  //清空查询条件
  onClearQuery: function () {
    Ext.getCmp("editQueryName").setValue("");
    Ext.getCmp("editType").setValue("");
  },
  //编辑流程
  onEditProcess: function () {
    let me = this;
    let data = me.getSelectNodeData();
    if (JSON.stringify(data) == "{}") {
      return me.showInfo('请选择需要编辑的数据');
    }
    if (data['status'] != 0) {
      return me.showInfo("工作流已开始或结束，无法进行编辑");
    }
    data['flows'] = me.__flows;
    console.log(data);
    let from = Ext.create("PSI.StartFlow.EditFlowWindow", {
      parentForm: me,
      entity: data
    });
    from.show();


  }
  ,
  //查看详情
  onSelectInfo: function () {
    let me = this;
  }
  ,
  //启动工作流
  onStartFlow: function () {
    let me = this;
    let data = me.getSelectNodeData();
    console.log(data);
    if (JSON.stringify(data) == "{}") {
      return me.showInfo('请选择工作流');
    }

  }

})
;
