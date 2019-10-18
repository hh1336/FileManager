Ext.define("PSI.FileManager.FilePermissionForm", {
  extend: "PSI.AFX.BaseDialogForm",
  initComponent: function () {
    var me = this;
    var entity = me.getEntity();
    me.__permissionData = {};

    Ext.apply(me, {
      title: "文件权限管理",
      width: "40%",
      height: "50%",
      border: 0,
      layout: "border",
      items: [
        {
          xtype: "panel",
          region: "west",
          layout: "fit",
          width: "50%",
          cls: "PSI",
          split: true,
          border: 0,
          items: [me.getRoleGrid()]
        },
        {
          xtype: "panel",
          region: "west",
          layout: "fit",
          width: "50%",
          cls: "PSI",
          split: true,
          border: 0,
          items: [me.getPermissionGrid()]
        }
      ]
    });

    me.callParent(arguments);
    me.userGrid = me.getRoleGrid();
    me.permissionGrid = me.getPermissionGrid();
  },
  getRoleGrid: function () {//用户列表
    var me = this;
    if (me.__roleGrid) {
      return me.__roleGrid;
    }
    var modelName = "PSIRole";
    Ext.define(modelName, {
      extend: "Ext.data.Model",
      fields: ["id", "name", "code"]
    });

    var roleStore = Ext.create("Ext.data.Store", {
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

    var roleGrid = Ext.create("Ext.grid.Panel", {
      cls: "PSI",
      header: {
        height: 30,
        title: me.formatGridHeaderTitle("角色")
      },
      width: "100%",
      height: "100%",
      store: roleStore,
      columns: [{
        header: "编码",
        dataIndex: "code",
        width: "50%",
        menuDisabled: true
      }, {
        header: "角色名称",
        dataIndex: "name",
        flex: 1,
        menuDisabled: true
      }]
    });

    roleStore.on("load", me.initData, me);
    roleGrid.on("itemclick", me.onSelectRole, me);

    me.__roleGrid = roleGrid;
    return me.__roleGrid;
  },
  getPermissionGrid: function () {//权限列表
    var me = this;
    if (me.__permissionGrid) {
      return me.__permissionGrid;
    }

    var modelName = "permissionModel";
    Ext.define(modelName, {
      extend: "Ext.data.Model",
      fields: ["dir", "leaf", "type"]
    });

    var dataobj = {
      "dir": [
        {"dir": "新建文件夹", "type": "WJGL_ADD_DIR", "checked": true, "leaf": true},
        {"dir": "编辑文件夹", "type": "WJGL_EDIT_DIR", "checked": true, "leaf": true},
        {"dir": "删除文件夹", "type": "WJGL_DEL_DIR", "checked": true, "leaf": true},
        {"dir": "查看文件夹", "type": "WJGL_INTO_DIR", "checked": true, "leaf": true},
        {"dir": "移动文件夹", "type": "WJGL_MOVE_DIR", "checked": true, "leaf": true},
        {"dir": "上传文件", "type": "WJGL_UP_FILE", "checked": true, "leaf": true},
        {"dir": "下载", "type": "WJGL_DOWN_FILE", "checked": true, "leaf": true}
      ],
      "file": [
        {"dir": "更新文件", "type": "WJGL_EDIT_FILE", "checked": true, "leaf": true},
        {"dir": "删除文件", "type": "WJGL_DEL_FILE", "checked": true, "leaf": true},
        {"dir": "移动文件", "type": "WJGL_MOVE_FILE", "checked": true, "leaf": true},
        {"dir": "预览文件", "type": "WJGL_YL_FILE", "checked": true, "leaf": true},
        {"dir": "下载", "type": "WJGL_DOWN_FILE", "checked": true, "leaf": true}
      ]
    };

    var permissionStore = Ext.create("Ext.data.TreeStore", {
      model: modelName,
      root: {
        expanded: false,
        children: me.getEntity().fileSuffix == "dir" ? dataobj["dir"] : dataobj["file"]
      }
    });

    var permissionPanel = Ext.create("Ext.tree.Panel", {
      cls: "PSI",
      title: false,
      store: permissionStore,
      width: "100%",
      rootVisible: false,
      useArrows: true,
      columns: {
        defaults: {
          sortable: false,
          menuDisabled: true,
          draggable: false
        },
        items: [{
          xtype: "treecolumn",
          text: "权限",
          dataIndex: "dir",
          width: "100%"
        }]
      }
    });

    permissionPanel.on("checkchange", me.oncheckchange, me);

    me.__permissionGrid = permissionPanel;
    return me.__permissionGrid;
  },
  //选中状态发生改变
  oncheckchange: function (node, checked) {
    var me = this;
    var roleGrid = me.getRoleGrid();
    var roleData = roleGrid.getSelectionModel().getLastSelected().data;
    var nodeData = node.data;
    me.__permissionData[roleData.id][nodeData.type] = checked;

  },
  //选中一个用户
  onSelectRole: function (node, record, item, index) {
    var me = this;
    var permissionData = me.__permissionData[record.internalId];
    var root = me.__permissionGrid.getRootNode();
    var el = me.getEl() || Ext.getBody();
    el.mask("数据加载中...");
    for (var i = 0, len = root.childNodes.length; i < len; i++) {
      root.childNodes[i].data.checked = permissionData[root.childNodes[i].data.type];
      me.__permissionGrid.updateLayout(root.childNodes[i]);
    }
    me.__permissionGrid.doAutoRender();
    el.unmask();


  },
  //选中第一个角色
  onfileStoryLoad: function () {
    var me = this;
    var grid = me.getRoleGrid();
    grid.getSelectionModel().select(0, true);
  },
  //初始化权限数据
  initData: function () {
    var me = this;
    var grid = me.getRoleGrid();
    var rolekeys = grid.getSelectionModel().store.data.keys;
    for (var i = 0, len = rolekeys.length; i < len; i++) {
      var key = rolekeys[i];
      me.__permissionData[key] =
        {
          "WJGL_ADD_DIR": true,
          "WJGL_EDIT_DIR": true,
          "WJGL_DEL_DIR": true,
          "WJGL_INTO_DIR": true,
          "WJGL_MOVE_DIR": true,
          "WJGL_UP_FILE": true,
          "WJGL_DOWN_FILE": true,
          "WJGL_EDIT_FILE": true,
          "WJGL_DEL_FILE": true,
          "WJGL_MOVE_FILE": true,
          "WJGL_YL_FILE": true
        };
    }

    me.onfileStoryLoad();
  }

})
