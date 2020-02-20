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
      }, {
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
          items: [me.getGrid()]
        }]
    });

    me.callParent(arguments);
  },
  //获取主面板
  getGrid: function () {
    let me = this;
    if (me.__grid)
      return me.__grid;

    let modelName = "ExamineModel";
    Ext.define(modelName, {
      extend: "Ext.data.Model",
      fields: ["id", "runName", "runId", "uId", "uName", "runRemark", "receiveTime",
        "status", "isBack", "remark", "isUserEnd", "flowStatus", "isUrgent"]
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
        enableTextSelection: true,
        getRowClass: me.changeRowClass
      },
      store: Store,
      features: [{ftype: "summary"}],
      columns: [
        {xtype: "rownumberer", width: "5%", header: "序号"},
        {
          header: "流程名称",
          dataIndex: "runName",
          menuDisabled: true,
          sortable: false,
          width: "19%"
        },
        {
          header: "发起人",
          dataIndex: "uName",
          menuDisabled: true,
          sortable: false,
          width: "8%"
        },
        {
          header: "发起时备注",
          dataIndex: "runRemark",
          menuDisabled: true,
          sortable: false,
          width: "24%"
        },
        {
          header: "接收时间",
          dataIndex: "receiveTime",
          menuDisabled: true,
          sortable: false,
          width: "10%"
        },
        {
          header: "备注",
          dataIndex: "remark",
          menuDisabled: true,
          sortable: false,
          width: "24%"
        },
        {
          header: "状态",
          dataIndex: "status",
          menuDisabled: true,
          sortable: false,
          width: "10%",
          renderer: function (value) {
            switch (value) {
              case "2":
                value = "待办理";
                break;
              case "3":
                value = "已通过";
                break;
              case "4":
                value = "未通过";
                break;
              case "5":
                value = "已打回";
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
        data: [["2", "待办理"], ["3", "已通过"], ["4", "未通过"], ["5", "已打回"]]
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
  onExamineFlow: function () {
    let me = this;
    let data = me.getSelectNodeData();
    if (Ext.JSON.encode(data) == "{}")
      return me.showInfo("请先选择数据");
    if (data['status'] != "2")
      return me.showInfo("已操作过流程");
    if (data['flowStatus'] != "0")
      return me.showInfo("流程已被禁用，无法进行审核");
    let form = Ext.create("PSI.Examine.ExamineWindow", {
      parentForm: me,
      entity: data
    });
    form.show();
  },
  //查看详细信息
  onSelectInfo: function () {
    let me = this;
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
  //改变行颜色
  changeRowClass: function (record) {
    let data = record['data'];
    //返回类名可给行加上指定样式
    //background-color-red
    //background-color-green
    //background-color-gray
  }

});
