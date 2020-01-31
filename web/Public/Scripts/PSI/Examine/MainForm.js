Ext.define('PSI.Examine.MainForm', {
  extend: "PSI.AFX.BaseMainExForm",
  config: {},
  initComponent: function () {
    let me = this;

    Ext.apply(me, {
      tbar: [{
        text: "审批流程",
        handler: me.onExamineFlow,
        scope: me
      },{
        text: "查看详细信息",
        handler: me.onSelectInfo,
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
          id: "panelFlowInfo",
          xtype: "panel",
          region: "west",
          layout: "fit",
          width: "100%",
          split: true,
          collapsible: true,
          header: false,
          border: 0,
          items: [me.getFlowGrid()]
        }]
    });

    me.callParent(arguments);
  },
  //获取主面板
  getFlowGrid: function () {
    let me = this;
    if (me.__grid)
      return me.__grid;

    let modelName = "ExamineModel";
    Ext.define(modelName, {
      extend: "Ext.data.Model",
      fields: ["id"]
    });

    let Store = Ext.create('Ext.data.Store', {
      autoLoad: true,
      pageSize: 10,
      model: modelName,
      proxy: {
        type: "ajax",
        url: me.URL("Home/Examine/loadFlow"),
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
        data: [["0", "未处理"], ["1", "已通过"], ["2", "已打回"]]
      }),
      value: ""
    }, {
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
  //打开审批流程窗口
  onExamineFlow:function(){
    let me = this;
  },
  //通过选中的流程
  onPastFlow: function () {
    let me = this;
  },
  //打回选中的流程
  onBackFlow: function () {
    let me = this;
  },
  //查看详细信息
  onSelectInfo: function () {
    let me = this;
  },
  //刷新数据
  freshGrid: function () {
    let me = this;
    let store = me.getFlowGrid().getStore();
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
  getSelectNodeData: function () {
    let me = this;
    let panel = me.__grid;
    let selected = panel.getSelectionModel().selected;
    let id = selected.keys[0];
    if (!selected.map[id]) {
      return {};
    }
    let data = selected.map[id].data;
    return data;
  },

});
