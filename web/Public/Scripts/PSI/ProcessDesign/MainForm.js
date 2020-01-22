Ext.define('PSI.ProcessDesign.MainForm', {
  extend: "PSI.AFX.BaseMainExForm",
  config: {},
  initComponent: function () {
    var me = this;

    Ext.apply(me, {
      tbar: [{
        text: "新建流程",
        //disabled: me.getAddDir() == "0",
        handler: me.onAddProcess,
        scope: me
      },
        {
          text: "编辑流程",
          handler: me.onEditProcess,
          scope: me
        }, {
          text: "设计流程",
          handler: me.onDesignFlow,
          scope: me
        },
        {
          text: "流程开关",
          menu: [
            {
              text: "禁用流程",
              handler: me.onDisableFlow,
              scope: me
            },
            {
              text: "启用流程",
              handler: me.onOpenFlow,
              scope: me
            }
          ]
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
          id: "panelProcessDesign",
          xtype: "panel",
          region: "west",
          layout: "fit",
          width: "65%",
          split: true,
          collapsible: true,
          header: false,
          border: 0,
          items: [me.getProcessDesignGrid()]
        }, {
          region: "center",
          xtype: "panel",
          layout: "fit",
          border: 0,
          items: [me.getViewProcess()]
        }]
    });
    me.callParent(arguments);
    me.loadjsPlumb();
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
    },
    //   {
    //   id: "editFileType",
    //   xtype: "combo",
    //   queryMode: "local",
    //   editable: true,
    //   valueField: "id",
    //   labelWidth: 60,
    //   labelAlign: "right",
    //   labelSeparator: "",
    //   fieldLabel: "文件类型",
    //   margin: "5, 0, 0, 0",
    //   store: Ext.create("Ext.data.ArrayStore", {
    //     fields: ["id", "text"],
    //     data: [["file", "文件"], ["dir", "文件夹"]]
    //   }),
    //   value: ""
    // },
    //   {
    //     id: "editActionType",
    //     xtype: "combo",
    //     queryMode: "local",
    //     editable: true,
    //     valueField: "id",
    //     labelWidth: 60,
    //     labelAlign: "right",
    //     labelSeparator: "",
    //     fieldLabel: "流程类型",
    //     margin: "5, 0, 0, 0",
    //     store: Ext.create("Ext.data.ArrayStore", {
    //       fields: ["id", "text"],
    //       data: [["create", "新建"], ["edit", "修订"], ["delete", "删除"], ["voided", "作废"]]
    //     }),
    //     value: ""
    //   },
      {
        xtype: "container",
        items: [{
          xtype: "button",
          text: "查询",
          width: 100,
          height: 26,
          margin: "5, 0, 0, 20",
          handler: me.freshProcessDesignGrid,
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
  //grid
  getProcessDesignGrid: function () {
    let me = this;

    if (me.__processDesignGrid) {
      return me.__processDesignGrid;
    }

    let modelName = "ProcessDesignModel";
    Ext.define(modelName, {
      extend: "Ext.data.Model",
      fields: ["Id", "FlowType", "FileType",
        "FlowName", "SortOrder", "Status", "UId", "UName", "AddTime"]
    });

    let ProcessStory = Ext.create('Ext.data.Store', {
      autoLoad: true,
      pageSize: 10,
      model: modelName,
      proxy: {
        type: "ajax",
        url: me.URL("Home/ProcessDesign/loadProcess"),
        actionMethods: {
          read: "POST"
        },
        extraParams: {
          ProcessName: "",
          ProcessType: "",
          ProcessAction: "",
          Status: ""
        },
        reader: {
          type: 'json',
          root: 'dataList',
          totalProperty: 'totalCount'
        }
      }
    });

    me.__processDesignGrid = Ext.create("Ext.grid.Panel", {
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
      store: ProcessStory,
      features: [{ftype: "summary"}],
      columns: [
        {xtype: "rownumberer", width: "10%", header: "序号"},
        // {
        //   header: "流程类型",
        //   dataIndex: "FlowType",
        //   menuDisabled: true,
        //   sortable: false,
        //   width: "8%",
        //   renderer: function (value) {
        //     let flowtype;
        //     switch (value) {
        //       case "create":
        //         flowtype = "新建";
        //         break;
        //       case "edit":
        //         flowtype = "修订";
        //         break;
        //       case "delete":
        //         flowtype = "删除";
        //         break;
        //       case "voided":
        //         flowtype = "移动";
        //         break;
        //       default:
        //         flowtype = value;
        //         break;
        //     }
        //     return flowtype;
        //   }
        // },
        // {
        //   header: "操作类型",
        //   dataIndex: "FileType",
        //   menuDisabled: true,
        //   sortable: false,
        //   width: "8%",
        //   renderer: function (value) {
        //     return value == "file" ? "文件" : "文件夹";
        //   }
        // },
        {header: "流程名称", dataIndex: "FlowName", menuDisabled: true, sortable: false, width: "30%"},
        {
          header: "状态",
          dataIndex: "Status",
          menuDisabled: true,
          sortable: false,
          width: "10%",
          renderer: function (value) {
            switch (value) {
              case "0":
                value = "正常";
                break;
              case "1":
                value = "已禁用";
                break;
              default:
                break;
            }
            return value;
          }
        },
        {header: "排序", dataIndex: "SortOrder", menuDisabled: true, sortable: false, width: "10%"},
        {header: "创建时间", dataIndex: "AddTime", menuDisabled: true, sortable: false, width: "20%"},
        {header: "创建用户", dataIndex: "UName", menuDisabled: true, sortable: false, width: "20%"}
      ]
    });

    //双击时编辑流程信息
    me.__processDesignGrid.on("itemdblclick", me.onEditProcess, me);
    //单击时显示流程预览
    me.__processDesignGrid.on("itemclick", me.onViewProcess, me);

    return me.__processDesignGrid;
  },
  //加载jsPlumb工具
  loadjsPlumb: function () {
    let me = this;
    jsPlumb.ready(function () {
      me.__jsPlumb = jsPlumb.getInstance();
    });
  },
  //流程预览panel
  getViewProcess: function () {
    let me = this;
    if (me.__viewProcessPanel) {
      return me.__viewProcessPanel;
    }
    let viewProcessPanel = Ext.create({
      cls: "PSI",
      xtype: 'panel',
      header: {
        height: 30,
        title: me.formatGridHeaderTitle("流程预览")
      },
      split: true,
      autoScroll: true,
      collapsible: false,
      header: false,
      border: 1,
      layout: "column",
      html: '<div id="Preview" style="height: 2000px;overflow-y:auto;"></div>'
    });

    me.__viewProcessPanel = viewProcessPanel;
    return me.__viewProcessPanel;

  },
  //显示流程预览
  onViewProcess: function (self, record) {
    let me = this;
    let itemId = record['data']['Id'];
    let config = {
      connector: ["Flowchart"],
      paintStyle: {stroke: '#5e96db', strokeWidth: 2},
      endpointStyle: {fill: 'lightgray', outlineStroke: 'lightgray'},
      maxConnections: -1,
      endpoint: ["Dot", {radius: 1}],
      anchor: 'Continuous',
      // ConnectorZIndex: 5,
      overlays: [['Arrow', {width: 12, length: 12, location: 0.5}]]
    };

    document.getElementById('Preview').innerHTML = "";
    me.__jsPlumb.deleteEveryEndpoint();
    me.__jsPlumb.clear();
    me.ajax({
      url: me.URL("Home/ProcessDesign/loadDesign"),
      params: {
        id: itemId
      },
      success: function (response) {
        let data = me.decodeJSON(response['responseText']);
        for (let i = 0, len = data['data'].length; i < len; i++) {
          let node = document.createElement('div');
          node.innerHTML = data['data'][i]['process_name'];
          node.setAttribute("name", data['data'][i]['process_type']);
          node.setAttribute("class", "item");
          node.setAttribute("style",
            'top: '
            + data['data'][i]['set_top'] + 'px;left: '
            + data['data'][i]['set_left'] + 'px;');
          node.setAttribute("id", data['data'][i]['id']);
          document.getElementById("Preview").appendChild(node);
        }
        for (let i = 0, len = data['data'].length; i < len; i++) {
          if (data['data'][i]['process_to']) {
            let targetArr = data['data'][i]['process_to'].split(',');
            for (let j = 0; j < targetArr.length; j++) {
              me.__jsPlumb.connect({
                source: data['data'][i]['id'],
                target: targetArr[j]
              }, config);
            }
          }
        }
      }
    });
  },
  //清空查询条件
  onClearQuery: function () {
    Ext.getCmp("editQueryName").setValue(null);
    Ext.getCmp("editActionType").setValue("create");
    Ext.getCmp("editFileType").setValue("file");
  },
  //刷新数据
  freshProcessDesignGrid: function () {
    let me = this;
    let store = me.getProcessDesignGrid().getStore();
    store.proxy.extraParams = {
      ProcessName: Ext.getCmp("editQueryName").getValue(),
      ProcessType: Ext.getCmp("editActionType").getValue(),
      ProcessAction: Ext.getCmp("editFileType").getValue(),
      Status: ""
    };
    store.reload();
  },
  //得到选中的数据
  getSelectNodeData: function (action) {
    var me = this;
    var panel = me.__processDesignGrid;
    var selected = panel.getSelectionModel().selected;
    var id = selected.keys[0];
    if (!selected.map[id]) {
      return {};
    }
    var data = selected.map[id].data;
    data["action"] = action;
    return data;
  },
  //添加流程
  onAddProcess: function () {
    let me = this;
    let data = me.getSelectNodeData("add");
    let form = Ext.create("PSI.ProcessDesign.AddOrEditProcessForm", {
      parentForm: me,
      entity: data
    });
    form.show();
  },
  //编辑流程信息
  onEditProcess: function () {
    let me = this;
    let data = me.getSelectNodeData("edit");
    if(data['Status'] == 1){
      return me.showInfo("流程在运行中，无法编辑");
    }
    let form = Ext.create("PSI.ProcessDesign.AddOrEditProcessForm", {
      parentForm: me,
      entity: data
    });
    form.show();
  },
  //禁用流程
  onDisableFlow: function () {
    let me = this;
    let data = me.getSelectNodeData();
    if (!data["Id"]) {
      return me.showInfo("请选择要操作的对象");
    }
    if (data["Status"] == 1) {
      return me.showInfo("已禁用，无需重复操作")
    }
    me.confirm("确定要禁用该流程？", function () {
        me.ajax({
          url: me.URL("Home/ProcessDesign/disableFlow"),
          params: {
            Id: data["Id"]
          },
          success: function (response) {
            let data = me.decodeJSON(response.responseText);
            me.showInfo(data["msg"]);
            me.freshProcessDesignGrid();
          }
        })
      }
    );

  },
  //启用流程
  onOpenFlow: function () {
    let me = this;
    let data = me.getSelectNodeData();
    if (!data["Id"]) {
      return me.showInfo("请选择要操作的对象");
    }
    me.ajax({
      url: me.URL("Home/ProcessDesign/openFlow"),
      params: {
        Id: data["Id"]
      },
      success: function (response) {
        let data = me.decodeJSON(response.responseText);
        me.showInfo(data["msg"]);
        me.freshProcessDesignGrid();
      }
    });
  },
  //设计流程
  onDesignFlow: function () {
    let me = this;
    let data = me.getSelectNodeData();
    if (!data["Id"]) {
      return me.showInfo("请选择要操作的对象");
    }
    let window = Ext.create("PSI.ProcessDesign.DesignFlowWindow", {
      parentForm: me,
      entity: data
    });
    window.show();
  }

});
