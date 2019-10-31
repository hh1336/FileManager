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

        roleGrid.on("select", me.onSelectRole, me);
        roleStore.on("load", me.onfileStoryLoad, me);
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
            fields: ["name", "leaf", "type"]
        });

        var dataobj = {
            "dir": [
                {"name": "新建文件夹", "type": "-9998-02", "checked": false, "leaf": true},
                {"name": "编辑文件夹", "type": "-9998-03", "checked": false, "leaf": true},
                {"name": "删除文件夹", "type": "-9998-04", "checked": false, "leaf": true},
                {"name": "查看文件夹", "type": "-9998-14", "checked": false, "leaf": true},
                {"name": "移动文件夹", "type": "-9998-15", "checked": false, "leaf": true},
                {"name": "上传文件", "type": "-9998-05", "checked": false, "leaf": true},
                {"name": "下载", "type": "-9998-10", "checked": false, "leaf": true}
            ],
            "file": [
                {"name": "更新文件", "type": "-9998-06", "checked": false, "leaf": true},
                {"name": "删除文件", "type": "-9998-08", "checked": false, "leaf": true},
                {"name": "移动文件", "type": "-9998-09", "checked": false, "leaf": true},
                {"name": "预览文件", "type": "-9998-07", "checked": false, "leaf": true},
                {"name": "下载", "type": "-9998-10", "checked": false, "leaf": true}
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
                    dataIndex: "name",
                    width: "100%"
                }]
            }
            // bbar:[{
            //   text:"全选",
            //   handler:me.onCheckAll,
            //   scope:me
            // }]
        });

        permissionPanel.on("checkchange", me.oncheckchange, me);

        me.__permissionGrid = permissionPanel;
        return me.__permissionGrid;
    },
    //选中状态发生改变
    oncheckchange: function (node, checked) {
        var me = this;
        var roleGrid = me.getRoleGrid();
        var roleData = roleGrid.getSelectionModel().getLastSelected();
        var nodeData = node.data;
        var fileData = me.getEntity();

        var el = me.getEl() || Ext.getBody();
        el.mask("数据加载中...");
        me.ajax({
            url: me.URL("Home/FileManager/setRolePermission"),
            params: {
                roleId: roleData.data.id,
                fileType: nodeData.type,
                fileId: fileData.id2,
                checked: checked
            },
            success: function (response) {
                var data = me.decodeJSON(response.responseText);
                if (!data.success) {
                    me.showInfo(data.msg);
                }
                el.unmask();
            }
        })

    },
    //选中一个用户
    onSelectRole: function (node, record, item, index) {
        var me = this;
        var root = me.__permissionGrid.getRootNode();

        var roleId = record.data.id;
        var fileId = me.getEntity().id2;
        var el = me.getEl() || Ext.getBody();
        el.mask("数据加载中...");
        me.ajax({
            url: me.URL("Home/FileManager/getRolePermission"),
            params: {
                fileId: fileId,
                roleId: roleId
            },
            success: function (response) {
                var permissions = me.decodeJSON(response.responseText);
                var count = permissions.length;
                for (var i = 0, len = root.data.children.length; i < len; i++) {
                    if (!count) {
                        root.data.children[i].checked = false;
                    }
                    root.data.children[i].checked = false;
                    for (var j = 0; j < count; j++) {
                        if (root.data.children[i].type == permissions[j]["permission_fid"]) {
                            root.data.children[i].checked = true;
                            break;
                        }
                    }
                }
                me.__permissionGrid.setRootNode(root.data);
                me.__permissionGrid.doLayout();
            }
        });


        me.__permissionGrid.doAutoRender();
        el.unmask();

    },
    //选中第一个角色
    onfileStoryLoad: function () {
        var me = this;
        var grid = me.getRoleGrid();
        grid.getSelectionModel().select(0, true);
    },

    //全选
    onCheckAll: function () {

    }

});
