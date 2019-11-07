Ext.define("PSI.FileManager.FileQueryForm", {
  extend: "PSI.AFX.BaseDialogForm",
  initComponent: function () {
    var me = this;
    var entity = me.getEntity();

    Ext.apply(me, {
      header: {
        title: "文件查询",
        height: 40
      },
      maximized: true,
      width: 700,
      height: 600,
      layout: "border",
      items: [{
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
        items: [{
          id: "queryName",
          labelWidth: 60,
          labelAlign: "right",
          labelSeparator: "",
          fieldLabel: "名称",
          margin: "5, 0, 0, 0",
          xtype: "textfield",
          value:entity["name"],
          listeners: {
            change: {
              fn: me.onQuery,
              scope: me
            }
          }
        }, {
          id: "queryType",
          xtype: "combo",
          queryMode: "local",
          editable: false,
          valueField: "id",
          labelWidth: 60,
          labelAlign: "right",
          labelSeparator: "",
          fieldLabel: "查询类型",
          margin: "5, 0, 0, 0",
          store: Ext.create("Ext.data.ArrayStore", {
            fields: ["id", "text"],
            data: [[1, "文件"], [0, "文件夹"]]
          }),
          value: entity["type"],
          listeners: {
            change: {
              fn: me.onQuery,
              scope: me
            }
          }
        },
          {
            xtype: "container",
            items: [{
              xtype: "button",
              text: "清空查询条件",
              width: 100,
              height: 26,
              margin: "5, 0, 0, 5",
              handler: me.clearQuery,
              scope: me
            }]
          }],
      },
        {
          xtype: "panel",
          region: "west",
          layout: "fit",
          width: "50%",
          split: true,
          collapsible: false,
          header: false,
          border: 0,
          items: [me.getQueryFilesPanel()]
        }, {
          region: "center",
          xtype: "panel",
          layout: "fit",
          border: 0,
          items: [me.getDirPanel()]
        }],
      tbar: [{
        text: "关闭",
        handler: function () {
          var me = this;
          me.close();
        },
        scope: this
      }]
    });

    me.__queryPanel = me.getQueryFilesPanel();
    me.__dirPanel = me.getDirPanel();
    me.callParent(arguments);
  },
  getQueryFilesPanel: function () {
    var me = this;
    if (me.__queryPanel) {
      return me.__queryPanel;
    }
    var entity = me.getEntity();

    var modelName = "QueryModel";
    Ext.define(modelName, {
      extend: "Ext.data.Model",
      fields: ["id", "id2", "children", "actionUserID", "actionTime", "parentDirID",
        "userName", "actionInfo", "leaf", "Version", "Name", "fileSize", "fileSuffix", "path"
      ]
    });

    var fileStory = Ext.create('Ext.data.TreeStore', {
      model: modelName,
      proxy: {
        type: "ajax",
        actionMethods: {
          read: "POST"
        },
        extraParams: {
          name: entity["name"],
          type: entity["type"]
        },
        url: me.URL("Home/FileManager/queryFiles"),
        reader: {
          type: 'json'
        }
      },
      root: {expanded: true}
    });


    var fileTree = Ext.create('Ext.tree.Panel', {
      cls: "PSI",
      header: {
        height: 30,
        title: me.formatGridHeaderTitle("查询结果")
      },
      store: fileStory,
      animate: true, // 开启动画效果
      rootVisible: false,
      useArrows: true,
      viewConfig: {
        loadMask: true,
      },
      columns: {
        defaults: {
          sortable: false,
          menuDisabled: true,
          draggable: false
        },
        items: [{
          xtype: "treecolumn",
          text: "名称",
          dataIndex: "Name",
          width: "30%"
        }, {
          text: "最后操作时间",
          dataIndex: "actionTime",
          width: "22%"
        }, {
          text: "操作人",
          dataIndex: "userName",
          width: "15%"
        }, {
          text: "操作描述",
          dataIndex: "actionInfo",
          width: "20%"
        }, {
          text: "路径",
          dataIndex: "path",
          width: "13%"
          // renderer: function (value) {
          //   return value.slice(0, 8);
          // }
        }]
      }
    });

    me.__queryPanel = fileTree;
    return me.__queryPanel;
  },

  getDirPanel: function () {
    var me = this;
    if (me.__dirPanel) {
      return me.__dirPanel;
    }

    var modelName = "DirModel";
    Ext.define(modelName, {
      extend: "Ext.data.Model",
      fields: ["id", "id2", "children", "actionUserID", "actionTime", "parentDirID",
        "userName", "actionInfo", "leaf", "Version", "Name", "fileSize", "fileSuffix"
      ]
    });

    var fileStory = Ext.create('Ext.data.TreeStore', {
      model: modelName,
      proxy: {
        type: "ajax",
        actionMethods: {
          read: "POST"
        },
        extraParams: {
          parentDirId: ""
        },
        url: me.URL("Home/FileManager/queryTree"),
        reader: {
          type: 'json'
        }
      },
      root: {expanded: true}
    });

    var fileTree = Ext.create('Ext.tree.Panel', {
      cls: "PSI",
      header: {
        height: 30,
        title: me.formatGridHeaderTitle("目录")
      },
      store: fileStory,
      animate: true, // 开启动画效果
      rootVisible: false,
      useArrows: true,
      viewConfig: {
        loadMask: true
      },
      columns: {
        defaults: {
          sortable: false,
          menuDisabled: true,
          draggable: false
        },
        items: [{
          xtype: "treecolumn",
          text: "名称",
          dataIndex: "Name",
          width: "30%"
        }, {
          text: "最后操作时间",
          dataIndex: "actionTime",
          width: "22%"
        }, {
          text: "操作人",
          dataIndex: "userName",
          width: "15%"
        }, {
          text: "操作描述",
          dataIndex: "actionInfo",
          width: "20%"
        }, {
          text: "版本号",
          dataIndex: "Version",
          width: "13%",
          renderer: function (value) {
            return value.slice(0, 8);
          }
        }]
      }
    });

    me.__dirPanel = fileTree;
    return me.__dirPanel;
  },

  onQuery: function () {
    var me = this;

    me.getQueryFilesPanel().getStore().proxy.extraParams = {
      name: Ext.getCmp("queryName").getValue(),
      type: Ext.getCmp("queryType").getValue()
    };

    me.freshFileGrid();
  },
  clearQuery: function () {
    var me = this;

    Ext.getCmp("queryName").setValue(null);
    Ext.getCmp("queryType").setValue(1);
  },
  freshFileGrid: function () {
    var me = this;
    me.getQueryFilesPanel().getStore().reload();
  },
})
