/**
 * 选择用户
 */
Ext.define("PSI.Permission.SelectUserForm", {
  extend: "PSI.AFX.BaseDialogForm",

  config: {
    idList: null, // idList是数组
    parentForm: null
  },

  title: "选择用户",
  width: 600,
  height: 500,
  modal: true,
  layout: "fit",

  initComponent: function () {
    var me = this;
    // Ext.define("PSIUser_SelectUserForm", {
    //   extend: "Ext.data.Model",
    //   fields: ["id", "loginName", "name", "orgFullName",
    //     "enabled"]
    // });
    //
    // var userStore = Ext.create("Ext.data.Store", {
    //   model: "PSIUser_SelectUserForm",
    //   autoLoad: false,
    //   data: []
    // });
    //
    // var grid = Ext.create("Ext.grid.Panel", {
    //   cls: "PSI",
    //   header: {
    //     height: 30,
    //     title: me.formatGridHeaderTitle("属于当前角色的用户")
    //   },
    //   padding: 5,
    //   selModel: {
    //     mode: "MULTI"
    //   },
    //   selType: "checkboxmodel",
    //   viewConfig: {
    //     deferEmptyText: false,
    //     emptyText: "所有用户都已经加入到当前角色中了"
    //   },
    //   store: userStore,
    //   columnLines: true,
    //   columns: [{
    //     header: "用户姓名",
    //     dataIndex: "name",
    //     width: 120,
    //     menuDisabled: true
    //   }, {
    //     header: "登录名",
    //     dataIndex: "loginName",
    //     width: 120,
    //     menuDisabled: true
    //   }, {
    //     header: "所属组织",
    //     dataIndex: "orgFullName",
    //     flex: 1,
    //     menuDisabled: true
    //   }]
    // });

    var modelName = "UserModel";
    Ext.define(modelName, {
      extend: "Ext.data.Model",
      fields: ["id", "children", "loginName", "name", "leaf"]
    });

    var UserStory = Ext.create('Ext.data.TreeStore', {
      model: modelName,
      proxy: {
        type: "ajax",
        actionMethods: {
          read: "POST"
        },
        // extraParams: {
        //   parentDirId: ""
        // },
        url: me.URL("Home/Permission/buildUserTree"),
        reader: {
          type: 'json'
        }
      },
      root: {expanded: true}
    });

    var grid = Ext.create('Ext.tree.Panel', {
      cls: "PSI",
      header: {
        height: 30,
        title: me.formatGridHeaderTitle("属于当前角色的用户")
      },
      store: UserStory,
      animate: true, // 开启动画效果
      rootVisible: false,
      useArrows: true,
      //selType: "checkboxmodel",
      //columnLines: true,
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
          dataIndex: "name",
          width: "50%"
        }, {
          text: "登陆名",
          dataIndex: "loginName",
          width: "50%"
        }]
      }
    });

    grid.on("checkchange", me.onCheckedUser, me);

    me.__grid = grid;

    Ext.apply(me, {
      items: [grid],
      buttons: [{
        text: "确定",
        formBind: true,
        iconCls: "PSI-button-ok",
        handler: me.onOK,
        scope: me
      }, {
        text: "取消",
        handler: function () {
          me.close();
        },
        scope: me
      }],
      // listeners: {
      //   show: {
      //     fn: me.onWndShow,
      //     scope: me
      //   }
      // }
    });

    me.callParent(arguments);
  },
  onCheckedUser: function (node, checked) {
    var me = this;
    for (var i = 0, len = node.childNodes.length; i < len; i++) {
      node.childNodes[i].data.checked = checked;
      me.__grid.updateLayout(node.childNodes[i]);
      me.onCheckedUser(node.childNodes[i], checked, me);
    }
    me.__grid.doLayout();
  },

  onWndShow: function () {
    var me = this;
    var idList = me.getIdList();
    var userStore = me.__grid.getStore();

    var el = me.getEl() || Ext.getBody();
    el.mask("数据加载中...");
    Ext.Ajax.request({
      url: PSI.Const.BASE_URL
        + "Home/Permission/selectUsers",
      params: {
        idList: idList.join()
      },
      method: "POST",
      callback: function (options, success, response) {
        if (success) {
          var data = Ext.JSON
            .decode(response.responseText);

          for (var i = 0; i < data.length; i++) {
            var item = data[i];
            userStore.add({
              id: item.id,
              name: item.name,
              loginName: item.loginName,
              orgFullName: item.orgFullName
            });
          }
        }

        el.unmask();
      }
    });
  },

  onOK: function () {
    var grid = this.__grid;

    //var items = grid.getSelectionModel().getSelection();
    var checkeds = grid.getChecked();
    var items = [];
    for (var i = 0, len = checkeds.length; i < len; i++) {
      if (checkeds[i]["data"]["id"]) {
        items.push(checkeds[i]);
      }
    }
    if (items == null || items.length == 0) {
      PSI.MsgBox.showInfo("没有选择用户");

      return;
    }

    if (this.getParentForm()) {
      this.getParentForm().setSelectedUsers(items);
    }

    this.close();
  }
});
