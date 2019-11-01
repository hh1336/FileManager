Ext.define("PSI.FileManager.FileActionLogForm", {
  extend: "PSI.AFX.BaseDialogForm",
  initComponent: function () {
    var me = this;
    var entity = me.getEntity();

    var modelName = "ActionLogModel";
    Ext.define(modelName, {
      extend: "Ext.data.Model",
      fields: ['id', 'action_time', 'action_user_id', 'action_info', 'action_user_name', 'type', 'name']
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
          id: entity.id
        },
        reader: {
          type: 'json',
          root: 'dataList',
          totalProperty: 'totalCount'
        }
      }
    });

    Ext.apply(me, {
      title: '历史版本',
      //height: "45%",
      cls: "PSI",
      width: "55%",
      height: "90%",
      id:"fileActionWindow",
      autoScroll: true,
      modal: true,
      Layout: "column",
      //closeAction: 'hide',
      // listeners:{
      //   close: {
      //     fn: function (panel,opts) {
      //       document.getElementById("FileActionInfo")
      //         .innerHTML = "";
      //     },
      //     scope: me
      //   },
      // },
      items: [{
        xtype: 'grid',
        border: false,
        sortableColumns: false,
        autoScroll: true,
        listeners: {
          itemClick: {
            fn: function (node, record) {
              me.__selectData = record.data;
              document.getElementById("FileActionInfo")
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
              text: '版本号',
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
              text: "名称",
              dataIndex: "name",
              width: "40%"
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
              text: "预览",
              width: "10%",
              dataIndex: "type",
              renderer: function (value) {
                var html = "<img src='' width='50%' height='50%' class='PSI-fid-2003'/>";
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
          id: "filePagingToobar",
          xtype: "pagingtoolbar",
          border: 0,
          store: myStore
        }, "-", {
          xtype: "displayfield",
          value: "每页显示"
        }, {
          id: "fileComboCountPerPage",
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
                myStore.pageSize = Ext.getCmp("fileComboCountPerPage").getValue();
                myStore.currentPage = 1;
                Ext.getCmp("filePagingToobar").doRefresh();
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
          html: "<textarea id='FileActionInfo' readonly style='border: none;width: 100%;height: 175px;'></textarea>"
        }],
      buttons: [
        {
          text: "撤回到选中版本",
          handler: function () {
            if (me.getParentForm().getActionLog() == "0") {
              return me.showInfo("没有权限");
            }
            if (!me.__selectData) {
              return me.showInfo("请选择对应版本");
            }
            var data = me.__selectData;
            return me.confirm("是否撤回[" + data.id.slice(0, 8) + "]版本", function () {
              Ext.Ajax.request({
                url: me.URL("Home/FileManager/revokeFile"),
                params: {
                  id: data.id,
                  fileName: data.name
                },
                success: function (response) {
                  var data = me.decodeJSON(response.responseText);
                  if (data) {
                    me.showInfo(data.msg, function () {
                      me.getParentForm().freshFileGrid();
                      me.close();
                    });
                  }
                }
              })
            });
          }
        }
      ]
    });

    me.callParent(arguments);
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
  // onWindowBeforeUnload: function (e) {
  //   return (window.event.returnValue = e.returnValue = '确认离开当前页面？');
  // },
});
