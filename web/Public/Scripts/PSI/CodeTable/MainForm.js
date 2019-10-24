//
// 码表设置 - 主界面
//
Ext.define("PSI.CodeTable.MainForm", {
  extend: "PSI.AFX.BaseMainExForm",
  border: 0,

  initComponent: function () {
    var me = this;

    Ext.apply(me, {
      tbar: me.getToolbarCmp(),
      layout: "border",
      items: [{
        region: "center",
        layout: "border",
        border: 0,
        items: [{
          region: "center",
          xtype: "panel",
          layout: "border",
          border: 0,
          items: [{
            region: "center",
            layout: "fit",
            border: 0,
            items: me.getMainGrid()
          }, {
            region: "south",
            layout: "fit",
            border: 0,
            height: "60%",
            split: true,
            items: [me.getColsGrid()]
          }]
        }, {
          id: "panelCategory",
          xtype: "panel",
          region: "west",
          layout: "fit",
          width: 300,
          split: true,
          collapsible: true,
          header: false,
          border: 0,
          items: [me.getCategoryGrid()]
        }]
      }]
    });

    me.callParent(arguments);

    me.refreshCategoryGrid();
  },

  getToolbarCmp: function () {
    var me = this;

    return [{
      text: "新增码表分类",
      handler: me.onAddCategory,
      scope: me
    }, {
      text: "编辑码表分类",
      handler: me.onEditCategory,
      scope: me
    }, {
      text: "删除码表分类",
      handler: me.onDeleteCategory,
      scope: me
    }, "-", {
      text: "新增码表",
      handler: me.onAddCodeTable,
      scope: me
    }, {
      text: "编辑码表",
      handler: me.onEditCodeTable,
      scope: me
    }, {
      text: "删除码表",
      handler: me.onDeleteCodeTable,
      scope: me
    }, "-", {
      text: "帮助",
      handler: function () {
        me.showInfo("TODO");
      }
    }, "-", {
      text: "关闭",
      handler: function () {
        me.closeWindow();
      }
    }];
  },

  getCategoryGrid: function () {
    var me = this;

    if (me.__categoryGrid) {
      return me.__categoryGrid;
    }

    var modelName = "PSICodeTableCategory";

    Ext.define(modelName, {
      extend: "Ext.data.Model",
      fields: ["id", "code", "name"]
    });

    me.__categoryGrid = Ext.create("Ext.grid.Panel", {
      cls: "PSI",
      viewConfig: {
        enableTextSelection: true
      },
      header: {
        height: 30,
        title: me.formatGridHeaderTitle("码表分类")
      },
      tools: [{
        type: "close",
        handler: function () {
          Ext.getCmp("panelCategory")
            .collapse();
        }
      }],
      columnLines: true,
      columns: [{
        header: "分类编码",
        dataIndex: "code",
        width: 80,
        menuDisabled: true,
        sortable: false
      }, {
        header: "码表分类",
        dataIndex: "name",
        width: 200,
        menuDisabled: true,
        sortable: false
      }],
      store: Ext.create("Ext.data.Store", {
        model: modelName,
        autoLoad: false,
        data: []
      }),
      listeners: {
        select: {
          fn: me.onCategoryGridSelect,
          scope: me
        }
      }
    });

    return me.__categoryGrid;
  },

  getMainGrid: function () {
    var me = this;

    if (me.__mainGrid) {
      return me.__mainGrid;
    }

    var modelName = "PSICodeTable";

    Ext.define(modelName, {
      extend: "Ext.data.Model",
      fields: ["id", "code", "name", "tableName",
        "memo", "fid", "mdVersion", "isFixed", "enableParentId",
        "handlerClassName"]
    });

    me.__mainGrid = Ext.create("Ext.grid.Panel", {
      cls: "PSI",
      viewConfig: {
        enableTextSelection: true
      },
      header: {
        height: 30,
        title: me.formatGridHeaderTitle("码表")
      },
      columnLines: true,
      columns: [{
        header: "编码",
        dataIndex: "code",
        width: 80,
        menuDisabled: true,
        sortable: false
      }, {
        header: "码表名称",
        dataIndex: "name",
        width: 200,
        menuDisabled: true,
        sortable: false
      }, {
        header: "数据库表名",
        dataIndex: "tableName",
        width: 200,
        menuDisabled: true,
        sortable: false
      }, {
        header: "fid",
        dataIndex: "fid",
        width: 200,
        menuDisabled: true,
        sortable: false
      }, {
        header: "备注",
        dataIndex: "memo",
        width: 300,
        menuDisabled: true,
        sortable: false
      }, {
        header: "版本",
        dataIndex: "mdVersion",
        width: 90,
        menuDisabled: true,
        sortable: false
      }, {
        header: "系统固有",
        dataIndex: "isFixed",
        width: 80,
        menuDisabled: true,
        sortable: false,
        renderer: function (value) {
          return parseInt(value) == 1 ? "是" : "否";
        }
      }, {
        header: "层级数据",
        dataIndex: "enableParentId",
        width: 80,
        menuDisabled: true,
        sortable: false,
        renderer: function (value) {
          return parseInt(value) == 1 ? "是" : "否";
        }
      }, {
        header: "业务逻辑类名",
        dataIndex: "handlerClassName",
        width: 300,
        menuDisabled: true,
        sortable: false
      }],
      store: Ext.create("Ext.data.Store", {
        model: modelName,
        autoLoad: false,
        data: []
      }),
      listeners: {
        select: {
          fn: me.onMainGridSelect,
          scope: me
        }
      }
    });

    return me.__mainGrid;
  },

  getColsGrid: function () {
    var me = this;

    if (me.__colsGrid) {
      return me.__colsGrid;
    }

    var modelName = "PSICodeTableCols";

    Ext.define(modelName, {
      extend: "Ext.data.Model",
      fields: ["id", "caption", "fieldName",
        "fieldType", "fieldLength", "fieldDecimal",
        "valueFrom", "valueFromTableName",
        "valueFromColName", "valueFromColNameDisplay", "mustInput",
        "showOrder", "sysCol", "isVisible",
        "widthInView", "note", "showOrderInView", "editorXtype"]
    });

    me.__colsGrid = Ext.create("Ext.grid.Panel", {
      cls: "PSI",
      viewConfig: {
        enableTextSelection: true
      },
      header: {
        height: 30,
        title: me.formatGridHeaderTitle("码表列")
      },
      tbar: [{
        text: "新增列",
        handler: me.onAddCol,
        scope: me
      }, "-", {
        text: "编辑列",
        handler: me.onEditCol,
        scope: me
      }, "-", {
        text: "删除列",
        handler: me.onDeleteCol,
        scope: me
      }],
      columnLines: true,
      columns: {
        defaults: {
          menuDisabled: true,
          sortable: false
        },
        items: [{
          header: "列标题",
          dataIndex: "caption",
          width: 150,
          locked: true
        }, {
          header: "列数据库名",
          dataIndex: "fieldName",
          width: 150
        }, {
          header: "列数据类型",
          dataIndex: "fieldType",
          width: 80
        }, {
          header: "列数据长度",
          dataIndex: "fieldLength",
          align: "right",
          width: 90
        }, {
          header: "列小数位数",
          dataIndex: "fieldDecimal",
          align: "right",
          width: 90
        }, {
          header: "值来源",
          dataIndex: "valueFrom",
          width: 120
        }, {
          header: "值来源表",
          dataIndex: "valueFromTableName",
          width: 150
        }, {
          header: "值来源字段(关联用)",
          dataIndex: "valueFromColName",
          width: 150
        }, {
          header: "值来源字段(显示用)",
          dataIndex: "valueFromColNameDisplay",
          width: 150
        }, {
          header: "系统字段",
          dataIndex: "sysCol",
          width: 70
        }, {
          header: "对用户可见",
          dataIndex: "isVisible",
          width: 80
        }, {
          header: "必须录入",
          dataIndex: "mustInput",
          width: 70
        }, {
          header: "列视图宽度(px)",
          dataIndex: "widthInView",
          width: 120,
          align: "right"
        }, {
          header: "编辑界面中显示次序",
          dataIndex: "showOrder",
          width: 140,
          align: "right"
        }, {
          header: "视图中显示次序",
          dataIndex: "showOrderInView",
          width: 130,
          align: "right"
        }, {
          header: "编辑器类型",
          dataIndex: "editorXtype",
          width: 130
        }, {
          header: "备注",
          dataIndex: "note",
          width: 200
        }]
      },
      store: Ext.create("Ext.data.Store", {
        model: modelName,
        autoLoad: false,
        data: []
      })
    });

    return me.__colsGrid;
  },

  onAddCategory: function () {
    var me = this;

    var form = Ext.create("PSI.CodeTable.CategoryEditForm", {
      parentForm: me
    });

    form.show();
  },

  refreshCategoryGrid: function (id) {
    var me = this;
    var grid = me.getCategoryGrid();
    var el = grid.getEl() || Ext.getBody();
    el.mask(PSI.Const.LOADING);
    var r = {
      url: me.URL("Home/CodeTable/categoryList"),
      callback: function (options, success, response) {
        var store = grid.getStore();

        store.removeAll();

        if (success) {
          var data = me.decodeJSON(response.responseText);
          store.add(data);

          if (store.getCount() > 0) {
            if (id) {
              var r = store.findExact("id", id);
              if (r != -1) {
                grid.getSelectionModel().select(r);
              }
            } else {
              grid.getSelectionModel().select(0);
            }
          }
        }

        el.unmask();
      }
    };

    me.ajax(r);
  },

  onEditCategory: function () {
    var me = this;

    var item = me.getCategoryGrid().getSelectionModel()
      .getSelection();
    if (item == null || item.length != 1) {
      me.showInfo("请选择要编辑的码表分类");
      return;
    }

    var category = item[0];

    var form = Ext.create("PSI.CodeTable.CategoryEditForm", {
      parentForm: me,
      entity: category
    });

    form.show();
  },

  onDeleteCategory: function () {
    var me = this;
    var item = me.getCategoryGrid().getSelectionModel()
      .getSelection();
    if (item == null || item.length != 1) {
      me.showInfo("请选择要删除的码表分类");
      return;
    }

    var category = item[0];

    var store = me.getCategoryGrid().getStore();
    var index = store.findExact("id", category.get("id"));
    index--;
    var preIndex = null;
    var preItem = store.getAt(index);
    if (preItem) {
      preIndex = preItem.get("id");
    }

    var info = "请确认是否删除码表分类: <span style='color:red'>"
      + category.get("name") + "</span>";

    var funcConfirm = function () {
      var el = Ext.getBody();
      el.mask("正在删除中...");

      var r = {
        url: me.URL("Home/CodeTable/deleteCodeTableCategory"),
        params: {
          id: category.get("id")
        },
        callback: function (options, success, response) {
          el.unmask();

          if (success) {
            var data = me.decodeJSON(response.responseText);
            if (data.success) {
              me.tip("成功完成删除操作");
              me.refreshCategoryGrid(preIndex);
            } else {
              me.showInfo(data.msg);
            }
          } else {
            me.showInfo("网络错误");
          }
        }
      };

      me.ajax(r);
    };

    me.confirm(info, funcConfirm);
  },

  onCategoryGridSelect: function () {
    var me = this;
    me.refreshMainGrid();
  },

  refreshMainGrid: function (id) {
    var me = this;
    me.getColsGrid().getStore().removeAll();

    var item = me.getCategoryGrid().getSelectionModel()
      .getSelection();
    if (item == null || item.length != 1) {
      me.getMainGrid().setTitle(me.formatGridHeaderTitle("码表"));
      return;
    }

    var category = item[0];

    var grid = me.getMainGrid();
    grid.setTitle(me.formatGridHeaderTitle("属于分类["
      + category.get("name") + "]的码表"));
    var el = grid.getEl() || Ext.getBody();
    el.mask(PSI.Const.LOADING);
    var r = {
      url: me.URL("Home/CodeTable/codeTableList"),
      params: {
        categoryId: category.get("id")
      },
      callback: function (options, success, response) {
        var store = grid.getStore();

        store.removeAll();

        if (success) {
          var data = me.decodeJSON(response.responseText);
          store.add(data);

          if (store.getCount() > 0) {
            if (id) {
              var r = store.findExact("id", id);
              if (r != -1) {
                grid.getSelectionModel().select(r);
              }
            } else {
              grid.getSelectionModel().select(0);
            }
          }
        }

        el.unmask();
      }
    };

    me.ajax(r);
  },

  onAddCodeTable: function () {
    var me = this;

    var item = me.getCategoryGrid().getSelectionModel()
      .getSelection();
    if (item == null || item.length != 1) {
      me.showInfo("请选择一个的码表分类");
      return;
    }

    var category = item[0];

    var form = Ext.create("PSI.CodeTable.CodeTableEditForm", {
      parentForm: me,
      category: category
    });
    form.show();
  },

  onEditCodeTable: function () {
    var me = this;

    var item = me.getMainGrid().getSelectionModel().getSelection();
    if (item == null || item.length != 1) {
      me.showInfo("请选择要编辑的码表");
      return;
    }

    var codeTable = item[0];

    var form = Ext.create("PSI.CodeTable.CodeTableEditForm", {
      parentForm: me,
      entity: codeTable
    });
    form.show();
  },

  onMainGridSelect: function () {
    var me = this;
    me.refreshColsGrid();
  },

  refreshColsGrid: function (id) {
    var me = this;
    var item = me.getMainGrid().getSelectionModel().getSelection();
    if (item == null || item.length != 1) {
      me.getMainGrid().setTitle(me.formatGridHeaderTitle("码表"));
      me.getColsGrid().setTitle(me.formatGridHeaderTitle("码表列"));
      return;
    }

    var codeTable = item[0];

    var grid = me.getColsGrid();
    grid.setTitle(me.formatGridHeaderTitle("属于码表["
      + codeTable.get("name") + "]的列"));
    var el = grid.getEl() || Ext.getBody();
    el.mask(PSI.Const.LOADING);
    var r = {
      url: me.URL("Home/CodeTable/codeTableColsList"),
      params: {
        id: codeTable.get("id")
      },
      callback: function (options, success, response) {
        var store = grid.getStore();

        store.removeAll();

        if (success) {
          var data = me.decodeJSON(response.responseText);
          store.add(data);

          if (store.getCount() > 0) {
            if (id) {
              var r = store.findExact("id", id);
              if (r != -1) {
                grid.getSelectionModel().select(r);
              }
            } else {
              grid.getSelectionModel().select(0);
            }
          }
        }

        el.unmask();
      }
    };

    me.ajax(r);
  },

  onDeleteCodeTable: function () {
    var me = this;
    var item = me.getMainGrid().getSelectionModel().getSelection();
    if (item == null || item.length != 1) {
      me.showInfo("请选择要删除的码表");
      return;
    }

    var codeTable = item[0];

    var store = me.getMainGrid().getStore();
    var index = store.findExact("id", codeTable.get("id"));
    index--;
    var preIndex = null;
    var preItem = store.getAt(index);
    if (preItem) {
      preIndex = preItem.get("id");
    }

    var info = "请确认是否删除码表: <span style='color:red'>"
      + codeTable.get("name")
      + "</span><br /><br />当前操作只删除码表元数据，数据库实际表不会删除";

    var funcConfirm = function () {
      var el = Ext.getBody();
      el.mask("正在删除中...");

      var r = {
        url: me.URL("Home/CodeTable/deleteCodeTable"),
        params: {
          id: codeTable.get("id")
        },
        callback: function (options, success, response) {
          el.unmask();

          if (success) {
            var data = me.decodeJSON(response.responseText);
            if (data.success) {
              me.tip("成功完成删除操作");
              me.refreshMainGrid(preIndex);
            } else {
              me.showInfo(data.msg);
            }
          } else {
            me.showInfo("网络错误");
          }
        }
      };

      me.ajax(r);
    };

    me.confirm(info, funcConfirm);
  },

  onAddCol: function () {
    var me = this;

    var item = me.getMainGrid().getSelectionModel().getSelection();
    if (item == null || item.length != 1) {
      me.showInfo("请选择要新增列的码表");
      return;
    }

    var codeTable = item[0];

    var form = Ext.create("PSI.CodeTable.CodeTableColEditForm", {
      codeTable: codeTable,
      parentForm: me
    });
    form.show();
  },

  onEditCol: function () {
    var me = this;

    var item = me.getMainGrid().getSelectionModel().getSelection();
    if (item == null || item.length != 1) {
      me.showInfo("请选择要编辑列的码表");
      return;
    }

    var codeTable = item[0];

    var item = me.getColsGrid().getSelectionModel().getSelection();
    if (item == null || item.length != 1) {
      me.showInfo("请选择要编辑的列");
      return;
    }
    var col = item[0];

    var form = Ext.create("PSI.CodeTable.CodeTableColEditForm", {
      codeTable: codeTable,
      entity: col,
      parentForm: me
    });
    form.show();
  },

  // 删除码表列
  onDeleteCol: function () {
    var me = this;
    var item = me.getMainGrid().getSelectionModel().getSelection();
    if (item == null || item.length != 1) {
      me.showInfo("请选择码表");
      return;
    }
    var codeTable = item[0];

    var item = me.getColsGrid().getSelectionModel().getSelection();
    if (item == null || item.length != 1) {
      me.showInfo("请选择要删除的列");
      return;
    }
    var col = item[0];

    var store = me.getColsGrid().getStore();
    var index = store.findExact("id", col.get("id"));
    index--;
    var preIndex = null;
    var preItem = store.getAt(index);
    if (preItem) {
      preIndex = preItem.get("id");
    }

    var info = "请确认是否删除码表列: <span style='color:red'>"
      + col.get("caption")
      + "</span><br /><br />当前操作只删除码表列的元数据，数据库表的字段不会删除";

    var funcConfirm = function () {
      var el = Ext.getBody();
      el.mask("正在删除中...");

      var r = {
        url: me.URL("Home/CodeTable/deleteCodeTableCol"),
        params: {
          tableId: codeTable.get("id"),
          id: col.get("id")
        },
        callback: function (options, success, response) {
          el.unmask();

          if (success) {
            var data = me.decodeJSON(response.responseText);
            if (data.success) {
              me.tip("成功完成删除操作");
              me.refreshColsGrid(preIndex);
            } else {
              me.showInfo(data.msg);
            }
          } else {
            me.showInfo("网络错误");
          }
        }
      };

      me.ajax(r);
    };

    me.confirm(info, funcConfirm);
  }
});
