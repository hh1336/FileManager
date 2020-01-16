Ext.define("PSI.ProcessDesign.SelectUserForm", {
  extend: "PSI.AFX.BaseDialogForm",
  title: "选择用户",
  width: 600,
  height: 500,
  modal: true,
  layout: "fit",
  initComponent:function () {
    let me = this;

    let modelName = "UserModel";
    Ext.define(modelName, {
      extend: "Ext.data.Model",
      fields: ["id", "children", "loginName", "name", "leaf"]
    });

    let UserStory = Ext.create('Ext.data.TreeStore', {
      model: modelName,
      proxy: {
        type: "ajax",
        actionMethods: {
          read: "POST"
        },
        url: me.URL("Home/Permission/buildUserTree"),
        reader: {
          type: 'json'
        }
      },
      root: {expanded: true}
    });

    let grid = Ext.create('Ext.tree.Panel', {
      cls: "PSI",
      header: {
        height: 30,
        title: me.formatGridHeaderTitle("属于当前角色的用户")
      },
      store: UserStory,
      animate: true,
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
      }]
    });

    me.callParent(arguments);
  },
  onCheckedUser: function (node, checked) {
    let me = this;
    for (let i = 0, len = node.childNodes.length; i < len; i++) {
      node.childNodes[i].data.checked = checked;
      me.__grid.updateLayout(node.childNodes[i]);
      me.onCheckedUser(node.childNodes[i], checked, me);
    }
    me.__grid.doLayout();
  },
  onOK: function () {
    let me = this;
    let grid = this.__grid;
    let entity = me.getEntity();
    let checkeds = grid.getChecked();
    let items = [];
    for (let i = 0, len = checkeds.length; i < len; i++) {
      if (checkeds[i]["data"]["id"]) {
        items.push(checkeds[i]);
      }
    }
    me.getParentForm().setCheckUser(items,entity);
    this.close();
  }

});
