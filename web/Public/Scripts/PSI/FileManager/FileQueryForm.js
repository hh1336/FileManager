Ext.define("PSI.FileManager.FileQueryForm", {
  extend: "PSI.AFX.BaseDialogForm",

  initComponent: function () {
    var me = this;
    var entity = me.getEntity();
    me.__count = 0;

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
          value: entity["name"],
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
          width: "65%",
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
        text: "编辑",
        handler: me.onEdit,
        scope: me
      },
        {
          text: "移动",
          handler: me.onMove,
          scope: me
        },
        {
          text: "新窗口预览",
          handler: me.windowPreview,
          scope: me
        },
        {
          text: "关闭",
          handler: function () {
            var me = this;
            me.close();
          },
          scope: me
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
          width: "17%"
        }, {
          text: "路径",
          dataIndex: "path",
          width: "16%"
        }]
      }
    });

    fileTree.on("itemdblclick", me.onEdit, me);

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

    if (me.__t1) {
      return false;
    }

    me.__t1 = window.setTimeout(function () {
      me.freshFileGrid();
      window.clearTimeout(me.__t1);
      me.__t1 = "";
    }, 500, me);

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
  freshDirPanel: function () {
    var me = this;
    me.getDirPanel().getStore().reload();
  },
  getSelectQueryData: function (type) {
    var me = this;
    var panel = me.__queryPanel;
    if (type == "tree") {
      panel = me.__dirPanel;
    }
    var selected = panel.getSelectionModel().selected;
    var id = selected.keys[0];
    if (!selected.map[id]) {
      return {};
    }
    var data = selected.map[id].data;
    return data;
  },
  onEdit: function () {
    var me = this;
    var data = me.getSelectQueryData();
    if (JSON.stringify(data) == "{}") {
      return me.showInfo("请选择目标");
    }
    var form;
    if (data["fileSuffix"] != "dir") {//是文件
      form = Ext.create("PSI.FileManager.EditFileForm", {
        parentForm: me,
        entity: data
      });
    } else {
      data["action"] = "edit";
      form = Ext.create("PSI.FileManager.DirEditForm", {
        parentForm: me,
        entity: data
      });
    }
    console.log(data);
    form.show();
    form.on("close", function () {
      me.freshDirPanel();
      me.getParentForm().freshFileGrid();
    }, me);
  },
  onMove: function () {
    var me = this;
    var queryData = me.getSelectQueryData();
    var treeNode = me.getSelectQueryData("tree");
    if (JSON.stringify(queryData) == "{}" || JSON.stringify(treeNode) == "{}") {
      return me.showInfo("请选择要移动的文件和要移动到的文件夹");
    }
    if (queryData["id2"] == treeNode["id2"]) {
      return me.showInfo("不能将文件夹移动到自己里面");
    }
    if (queryData["parentDirID"] == treeNode["id2"]) {
      return me.showInfo("目标已在该文件夹下");
    }
    me.confirm("确认要将该内容移动到['" + treeNode["Name"] + "']目录中吗？",
      function () {
        me.ajax({
          url: me.URL("Home/FileManager/MoveToDir"),
          params: {
            mid: queryData["id"],//得到被拖拽对象的t_dir或t_file表的id
            dirid: treeNode["id2"],//得到目标目录id
            name: queryData["Name"],//被拖拽对象的名称
            todirname: treeNode["Name"]//得到目标目录名称
          },
          callback: function (opt, success, response) {
            var rsdata = me.decodeJSON(response.responseText);
            if (!rsdata.success) {
              me.showInfo(rsdata.msg);
            }
            me.freshFileGrid();
            me.freshDirPanel();
            me.getParentForm().freshFileGrid();
          }
        });
      });
  },
  windowPreview: function () {
    var me = this;
    var data = me.getSelectQueryData();
    if (JSON.stringify(data) == "{}") {
      return me.showInfo("请选择文件");
    }
    if (data["fileSuffix"] == "dir") {
      return me.showInfo("只能预览文件");
    }
    me.ajax({
      url: me.URL("Home/FileManager/convertFile"),
      params: {
        id: data["id2"]
      },
      success: function (response) {
        var rsdata = me.decodeJSON(response.responseText);
        if (rsdata.success) {
          var url;
          if (rsdata.file_suffix == "pdf") {
            url = me.URL("Public/pdfjs/web/viewer.html?file=" + me.URL("Home/FileManager/getFile/fileid/" + rsdata.id));
          } else {
            url = me.URL("Home/FileManager/getFile?fileid=" + rsdata.id);
          }
          window.open(url);
        } else {
          me.showInfo(rsdata.msg);
        }
      }
    });

  }
})
