Ext.define('PSI.FileManager.MainForm', {
  extend: "PSI.AFX.BaseMainExForm",
  config: {
    AddDir: null,
    EditDir: null,
    DeleteDir: null,
    UpFile: null,
    DeleteFile: null,
    EditFile: null,
    PreviewFile: null,
    Move: null,
    DownLoad: null,
    LookActionLog: null,
    ActionLog: null,
    FilePermission: null
  },
  initComponent: function () {
    var me = this;
    Ext.apply(me, {
      tbar: [{
        text: "新建文件夹",
        disabled: me.getAddDir() == "0",
        handler: me.onAddDir,
        scope: me
      }, {
        text: "编辑文件夹",
        disabled: me.getEditDir() == "0",
        handler: me.onEditDir,
        scope: me
      }, {
        text: "删除文件夹",
        disabled: me.getDeleteDir() == "0",
        handler: me.onDeleteDir,
        scope: me
      }, "-", {
        text: "上传文件",
        disabled: me.getUpFile() == "0",
        handler: me.onUploadMultipleFile,
        scope: me
      }, {
        text: "新窗口预览",
        disabled: me.getPreviewFile() == "0",
        handler: me.onNewWindowPreviewFile,
        scope: me
      },
          {
          text: "编辑文件",
          disabled: me.getEditFile() == "0",
          handler: me.onEditFile,
          scope: me
        },
        {
          text: "删除文件",
          disabled: me.getDeleteFile() == "0",
          handler: me.onDeleteFile,
          scope: me
        },
        {
          text: "下载",
          disabled: me.getDownLoad() == "0",
          handler: me.onDownLoad,
          scope: me
        },
        "-",
        {
          text: "操作记录",
          hidden: me.getLookActionLog() == "0",
          handler: me.onLookLog,
          scope: me
        }
      ],
      items: [{
        id: "panelFileManager",
        xtype: "panel",
        region: "west",
        layout: "fit",
        width: "50%",
        split: true,
        collapsible: true,
        header: false,
        border: 0,
        items: [me.getFileGrid()]
      }, {
        region: "center",
        xtype: "panel",
        layout: "fit",
        border: 0,
        items: [me.getFanel()]
      }]
    });

    me.callParent(arguments);
    me.FileTree = me.getFileGrid();
    me.FilePanel = me.getFanel();
  },
  getFileGrid: function () {
    var me = this;

    if (me.__fileGrid) {
      return me.__fileGrid;
    }
    var modelName = "FileModel";
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
        url: me.URL("Home/FileManager/loadDir"),
        reader: {
          type: 'json'
        }
      },
      root: {expanded: true}
    });

    fileStory.on("load", me.onfileStoryLoad, me);

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
        loadMask: true,
        plugins: {
          ptype: 'treeviewdragdrop',
          appendOnly: false         //只能拖着带非叶节点上
        },
        listeners: {
          //监听拖动
          drop: function (node, odata, overModel, dropPosition, options) {
            if (!(overModel.data.fileSuffix == "dir") && !(overModel.data.fileSuffix == "")) {
              me.showInfo("只能将内容移动到文件夹中");
              return me.freshFileGrid();
            }
            if (me.getMove() == "0") {
              me.showInfo("没有权限");
              return me.freshFileGrid();
            }
            me.confirm("确认要将该内容移动到['" + overModel.data.Name + "']目录中吗？", function () {
              Ext.Ajax.request({
                url: me.URL("Home/FileManager/MoveToDir"),
                params: {
                  mid: odata.item.dataset.recordid,//得到被拖拽对象的t_dir或t_file表的id
                  dirid: overModel.data.id2,//得到目标目录id
                  name: odata.item.children[0].innerText,//被拖拽对象的名称
                  todirname: overModel.data.Name//得到目标目录名称
                },
                callback: function (opt, success, response) {
                  var data = me.decodeJSON(response.responseText);
                  if (!data.success) {
                    me.showInfo(data.msg);
                  }
                  me.freshFileGrid();
                }
              });
            });
            me.freshFileGrid();
          }
        }
      },
      tools: [{
        type: "close",
        handler: function () {
          Ext.getCmp("panelFileManager").collapse();
        }
      }],
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

    var itemmenu = new Ext.menu.Menu();
    itemmenu.add({text: "下载", handler: me.itemContextClick, scope: me}, "-");
    itemmenu.add({text: "查看历史版本", handler: me.onLookFileLog, scope: me});
    if (!(me.getFilePermission() == "0")) {
      itemmenu.add("-", {text: "权限设置", handler: me.onFilePermission, scope: me});
    }
    itemmenu.add("-", {text: "刷新", handler: me.freshFileGrid, scope: me});

    var treemenu = new Ext.menu.Menu();
    treemenu.add({text: "新建文件夹", handler: me.containercontext, scope: me, cls: "PSI"}, "-");
    treemenu.add({
      text: "上传文件", handler: function () {
        me.onfileStoryLoad();
        me.onUploadMultipleFile();
      }, scope: me
    }, "-");
    treemenu.add({text: "刷新", handler: me.freshFileGrid, scope: me});

    //右击菜单
    fileTree.on("itemcontextmenu", function (node, record, item, index, e) {
      e.preventDefault();
      itemmenu.showAt(e.xy);
    }, me);

    //右击容器
    fileTree.on("containercontextmenu", function (node, e) {
      e.preventDefault();
      me.onfileStoryLoad();
      treemenu.showAt(e.xy);
    }, me);

    fileTree.on("itemdblclick", me.onPreviewFile, me);
    fileTree.on("containerclick", me.onfileStoryLoad, me);
    // fileTree.on("checkchange", function (node, checked) {
    //   me.onCheck(node, checked, me);
    // }, me);

    me.__fileGrid = fileTree;
    me.__fileTreeStory = fileStory;
    return me.__fileGrid;
  },

  getWindow: function () {
    var me = this;
    if (me.__window) {
      return me.__window;
    }
    var modelName = "PSIActionLogModel";
    Ext.define(modelName, {
      extend: "Ext.data.Model",
      fields: ['id', 'action_time', 'action_user_id', 'remarks', 'action_info', 'action_user_name', 'type', 'name']
    });

    var myStore = Ext.create('Ext.data.Store', {
      model: modelName,
      autoLoad: true,
      pageSize: 15,
      proxy: {
        type: 'ajax',
        url: me.URL("Home/FileManager/loadActionLog"),
        actionMethods: {
          read: "POST"
        },
        extraParams: {
          id: ""
        },
        reader: {
          type: 'json',
          root: 'dataList',
          totalProperty: 'totalCount'
        }
      }
    });

    me.__window = Ext.create('Ext.window.Window', {
      title: '操作记录',
      //height: "45%",
      cls: "PSI",
      width: "55%",
      height: "90%",
      autoScroll: true,
      modal: true,
      Layout: "column",
      closeAction: 'hide',
      items: [{
        xtype: 'grid',
        border: false,
        sortableColumns: false,
        autoScroll: true,
        listeners: {
          itemClick: {
            fn: function (node, record) {
              me.__selectData = record.data;
              document.getElementById("action_info")
                .innerHTML = record.data.action_info;
            },
            scope: me
          }
        },
        columns: {
          defaults: {
            sortable: false,
            menuDisabled: true,
            draggable: false
          },
          items: [
            {
              text: '版本',
              width: "10%",
              dataIndex: "id",
              renderer: function (value) {
                var version = value.slice(0, 8);
                var html = "<a href='#'>" + version + " </a>";
                return html;
              },
              listeners: {
                click: {
                  fn: me.lookOldVersion,
                  scope: me
                }
              }
            },
            {
              text: '名称',
              dataIndex: "name",
              width: "10%"
            },
            {
              text: '操作时间',
              dataIndex: "action_time",
              width: "25%"
            },
            {
              text: '操作人',
              dataIndex: "action_user_name",
              width: "15%"
            },
            {
              text: '操作描述',
              dataIndex: "remarks",
              width: "30%"
            },
            // {
            //   text: '操作备注',
            //   dataIndex: "action_info",
            //   width: "22%"
            // },
            {
              text: "预览",
              width: "10%",
              dataIndex: "type",
              renderer: function (value) {
                var html = "";
                if (value == "file") {
                  html = "<img src='' width='50%' height='50%' class='PSI-fid-2003'/>";
                }
                return html;
              },
              listeners: {
                click: {
                  fn: me.lookOldVersion,
                  scope: me
                }
              }
            }

          ]
        },
        store: myStore,
        bbar: ["->", {
          id: "pagingToobar",
          xtype: "pagingtoolbar",
          border: 0,
          store: myStore
        }, "-", {
          xtype: "displayfield",
          value: "每页显示"
        }, {
          id: "comboCountPerPage",
          xtype: "combobox",
          editable: false,
          width: 60,
          store: Ext.create("Ext.data.ArrayStore", {
            fields: ["text"],
            data: [["5"], ["15"], ["50"], ["100"]]
          }),
          value: 15,
          listeners: {
            change: {
              fn: function () {
                myStore.pageSize = Ext.getCmp("comboCountPerPage").getValue();
                myStore.currentPage = 1;
                Ext.getCmp("pagingToobar").doRefresh();
              },
              scope: me
            }
          }
        }, {
          xtype: "displayfield",
          value: "条记录"
        }]
      },
        {
          xtype: "panel",
          width: "100%",
          id: "action_panel",
          height: 175,
          html: "<textarea id='action_info' readonly style='border: none;width: 100%;height: 175px;'></textarea>"
        }],
      buttons: [
        {
          text: "撤回到选中版本",
          handler: function () {
            if (me.getActionLog() == "0") {
              return me.showInfo("没有权限");
            }
            if (!me.__selectData) {
              return me.showInfo("请选择对应版本");
            }
            var data = me.__selectData;
            if (data.type) {
              return me.confirm("是否撤回[" + data.id.slice(0, 8) + "]版本", function () {
                Ext.Ajax.request({
                  url: me.URL("Home/FileManager/revokeFile"),
                  params: {
                    id: data.id,
                    fileName: data.name
                  },
                  success: function (response) {
                    var data = me.decodeJSON(response.responseText);
                    console.log(response);
                    if (data) {
                      me.showInfo(data.msg, function () {
                        me.freshFileGrid();
                        me.__window.close();
                      });
                    }

                  }
                })
              });
            }
            me.confirm("是否回到[" + data.id.slice(0, 8) + "],该版本之后的所有操作将被撤销。", function () {
              Ext.Ajax.request({
                url: me.URL("Home/FileManager/backVersion"),
                params: {
                  id: data.id
                },
                success: function (response) {
                  var data = me.decodeJSON(response.responseText);
                  me.showInfo(data.msg, function () {
                    me.freshFileGrid();
                    me.__window.close();
                  });
                }
              });
            })
          }
        }
      ]
    });
    me.__window.on("hide", function () {
      me.__selectData = "";
      document.getElementById("action_info")
        .innerHTML = "";
    }, me);

    return me.__window;
  },

  getFanel: function () {
    var me = this;
    if (me.__filePanel) {
      return me.__filePanel;
    }

    me.__filePanel = Ext.create({
      cls: "PSI",
      xtype: 'panel',
      frame: false,
      header: {
        height: 30,
        title: me.formatGridHeaderTitle("文件预览")
      },
      layout: "column",
      //html: "<iframe id='mainframe' src='' frameborder='0' width='100%' height='100%'></iframe>"
      html: "<embed id='mainframe' src='' width='100%' height='100%' type='application/pdf'>"
    });
    return me.__filePanel;
  },
  //提取当前选中节点数据，并设置操作类型
  getSelectNodeData: function (action) {
    var me = this;
    var id = me.__fileGrid.getSelectionModel().selected.keys[0];
    if (!me.__fileGrid.getSelectionModel().selected.map[id]) {
      return {};
    }
    var data = me.__fileGrid.getSelectionModel().selected.map[id].data;
    data["action"] = action;
    return data;

  },
  //添加文件夹事件
  onAddDir: function () {
    var me = this;
    if (me.getAddDir() == "0") {
      return me.showInfo("没有权限");
    }
    var data = me.getSelectNodeData("add");
    var form = Ext.create("PSI.FileManager.DirEditForm", {
      parentForm: me,
      entity: data
    });
    form.show();
  },
  //编辑文件夹事件
  onEditDir: function () {
    var me = this;
    if (me.getEditDir() == "0") {
      return me.showInfo("没有权限");
    }
    var data = me.getSelectNodeData("edit");
    if (data.fileSuffix != "dir") {
      return me.onEditFile();
    }
    if (data.Name == "../") {
      return false;
    }
    var form = Ext.create("PSI.FileManager.DirEditForm", {
      parentForm: me,
      entity: data
    });
    form.show();
  }
  ,
  //删除文件夹
  onDeleteDir: function () {
    var me = this;
    if (me.getDeleteDir() == "0") {
      return me.showInfo("没有权限");
    }
    var data = me.getSelectNodeData('del');
    if (data["Name"] == "/") {
      return me.showInfo("请选择要删除的文件夹");
    }
    if (data.fileSuffix != "dir") {
      return me.onDeleteFile();
    }
    me.confirm("删除该文件夹，里面的内容将会同时删除，请确认操作", function () {
      Ext.Ajax.request({
        url: me.URL("Home/FileManager/delDir"),
        params: {
          id: data.id2,
          name: data.Name
        },
        success: function (response) {
          var data = me.decodeJSON(response.responseText);
          me.showInfo(data.msg, function () {
            me.freshFileGrid();
          });
        }
      });
    });
  }
  ,
  //编辑文件
  onEditFile: function () {
    var me = this;
    if (me.getEditFile() == "0") {
      return me.showInfo("没有权限");
    }
    var data = me.getSelectNodeData();
    console.log(data);
    if (data.fileSuffix != "dir" && data.Name != "../") {
      var form = Ext.create("PSI.FileManager.EditFileForm", {
        parentForm: me,
        entity: data
      });
      return form.show();
    }
    me.showInfo("请选择要修改的文件");
  },
  //多文件上传
  onUploadMultipleFile: function () {
    var me = this;
    if (me.getUpFile() == "0") {
      return me.showInfo("没有权限");
    }
    var data = me.getSelectNodeData();
    if (data.fileSuffix == "dir") {
      var form = Ext.create("PSI.FileManager.UploadMultipleFile", {
        parentForm: me,
        entity: data
      });
      return form.show();
    }
    me.showInfo("请选择文件夹作为上传目录");
  },
  //点击下载按钮
  onDownLoad: function () {
    var me = this;
    if (me.getDownLoad() == "0") {
      return me.showInfo("没有权限");
    }
    var chekedNode = me.FileTree.getChecked();
    var arr = [];
    if (chekedNode.length) {//勾选了的时候下载
      for (var i = 0, len = chekedNode.length; i < len; i++) {
        arr.push(chekedNode[i].data.id2);
      }
      return me.downFiles(arr, me);
    }
    //下载单击中的目标
    var data = me.getSelectNodeData();
    arr.push(data.id2);
    me.downFiles(arr, me);
  },
  //开始下载
  downFiles: function (arr, scope) {
    var data = arr.join("|");
    Ext.Ajax.request({
      url: scope.URL("Home/FileManager/downLoad"),
      params: {
        str: data
      },
      success: function (response) {
        var data = scope.decodeJSON(response.responseText);
        if (!data.success) {
          return scope.showInfo(data.msg);
        }
        window.open(data.url);
      }
    });
  },
  //右键开始下载
  itemContextClick: function () {
    var me = this;
    if (me.getDownLoad() == "0") {
      return me.showInfo("没有权限");
    }
    var data = me.getSelectNodeData();
  },

  //删除文件
  onDeleteFile: function () {
    var me = this;
    if (me.getDeleteFile() == "0") {
      return me.showInfo("没有权限");
    }
    var data = me.getSelectNodeData();
    if (data.fileSuffix == "dir") {
      return me.onDeleteDir();
    }
    var filename = data.Name;
    me.confirm("是否删除该文件，请确认操作", function () {
      Ext.Ajax.request({
        url: me.URL("Home/FileManager/delFile"),
        params: {
          id: data.id2,
          name: filename
        },
        success: function (response) {
          var data = me.decodeJSON(response.responseText);
          me.showInfo(data.msg, function () {
            me.freshFileGrid();
            var dom = Ext.get("mainframe");
            dom.set({"src": ""});
          });
        }
      });
    });

  }
  ,
  //预览文件
  onPreviewFile: function (node, record, item) {
    var me = this;
    if (me.getPreviewFile() == "0") {
      return me.showInfo("没有权限");
    }
    if (me.__fileGrid) {
      var data = me.getSelectNodeData();
      if (data.fileSuffix == "dir") {
        return me.onloadChildrenDir(node, record, item);
      }
      Ext.Ajax.request({
        url: me.URL("Home/FileManager/convertFile"),
        params: {
          id: data.id2
        },
        success: function (response) {
          var rsdata = me.decodeJSON(response.responseText);
          if (rsdata.success) {
            var dom = Ext.get("mainframe");
            if (!(rsdata.file_suffix == "pdf")) {
              dom.set({"src": me.URL("Home/FileManager/getFile?fileid=" + rsdata.id)});
              return true;
            }
            dom.set({"src": me.URL("Public/pdfjs/web/viewer.html?file=" + me.URL("Home/FileManager/getFile/fileid/" + rsdata.id))})
          } else {
            me.showInfo(rsdata.msg);
          }
        }
      });
    }
  },
  //查看操作记录
  onLookLog: function () {
    var me = this;
    if (me.getLookActionLog() == "0") {
      return me.showInfo("没有权限");
    }
    me.getWindow().child("grid").getStore().proxy.extraParams = {
      id: ""
    };
    me.getWindow().show();
    Ext.getCmp("pagingToobar").doRefresh();
  },

  //刷新树
  freshFileGrid: function () {
    var me = this;
    me.getFileGrid().getStore().reload();
  }
  ,
  onfileStoryLoad: function () {
    var me = this;
    var tree = me.getFileGrid();
    var root = tree.getRootNode();
    if (root) {
      var node = root.firstChild;
      if (node) {
        tree.getSelectionModel().select(node);
      }
    }
  },
  //让文件夹处于选中状态
  // onCheck: function (node, checked, me) {
  //   for (var i = 0, len = node.childNodes.length; i < len; i++) {
  //     node.childNodes[i].data.checked = checked;
  //     me.__fileGrid.updateLayout(node.childNodes[i]);
  //     me.onCheck(node.childNodes[i], checked, me);
  //   }
  // },
  //右键新增文件夹
  containercontext: function () {
    var me = this;
    var data = me.getSelectNodeData();
    if (!(data.Name == "../")) {
      var temp = me.__fileGrid.getSelectionModel().selected.map;
      me.__fileGrid.getSelectionModel().selected.map = {};
      me.onAddDir();
      me.__fileGrid.getSelectionModel().selected.map = temp;
      return false;
    }
    me.onAddDir();

  },
  //进入文件夹
  onloadChildrenDir: function (node, record, item) {
    var me = this;
    var data = me.getSelectNodeData();
    me.getFileGrid().getStore().proxy.extraParams = {
      parentDirID: data.id2
    };
    me.freshFileGrid();
  },
  //查看文件版本
  onLookFileLog: function () {
    var me = this;
    var data = me.getSelectNodeData();
    if (data.fileSuffix == "dir") {
      return me.showInfo("只能查看文件");
    }
    me.getWindow().child("grid").getStore().proxy.extraParams = {
      id: data.id
    };
    me.getWindow().show();
    Ext.getCmp("pagingToobar").doRefresh();
  },
  //预览旧文件
  lookOldVersion: function (node, el, index) {
    var me = this;
    if (node.getStore().getAt(index).data.type == "file") {
      Ext.Ajax.request({
        url: me.URL("Home/FileManager/convertFile"),
        params: {
          id: node.getStore().getAt(index).data.id
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
  },
  //新窗口预览文件
  onNewWindowPreviewFile: function () {
    var me = this;
    var data = me.getSelectNodeData();
    if (JSON.stringify(data) != '{}') {
      if ((data.Name == "../") || (data.fileSuffix == "dir")) {
        return me.showInfo("请选择文件");
      }
      me.ajax({
        url: me.URL("Home/FileManager/convertFile"),
        params: {
          id: data.id2
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
    } else {
      me.showInfo("请选择文件");
    }
  },
  //文件或文件夹权限
  onFilePermission: function () {
    var me = this;
    var data = me.getSelectNodeData();
    if (data.Name == "../") {
      return me.showInfo("请选择操作目标");
    }
    var form = Ext.create("PSI.FileManager.FilePermissionForm", {
      parentForm: me,
      entity: data
    });
    return form.show();
  }

})
;

