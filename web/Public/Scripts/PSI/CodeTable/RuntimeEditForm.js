//
// 码表运行 - 新增或编辑界面
//
Ext.define("PSI.CodeTable.RuntimeEditForm", {
  extend: "PSI.AFX.BaseDialogForm",

  config: {
    metaData: null
  },

  initComponent: function () {
    var me = this;

    var md = me.getMetaData();

    var entity = me.getEntity();

    me.adding = entity == null;

    var buttons = [];
    if (!entity) {
      var btn = {
        text: "保存并继续新增",
        formBind: true,
        handler: function () {
          me.onOK(true);
        },
        scope: me
      };

      buttons.push(btn);
    }

    var btn = {
      text: "保存",
      formBind: true,
      iconCls: "PSI-button-ok",
      handler: function () {
        me.onOK(false);
      },
      scope: me
    };
    buttons.push(btn);

    var btn = {
      text: entity == null ? "关闭" : "取消",
      handler: function () {
        me.close();
      },
      scope: me
    };
    buttons.push(btn);

    // 通过可见字段的个数计算出Form的高度
    var cnt = 0;
    var md = me.getMetaData();
    for (var i = 0; i < md.cols.length; i++) {
      var colMd = md.cols[i];
      if (colMd.isVisible) {
        cnt++;
      }
    }
    // TODO 这个算法没有处理字段过多的问题，需要优化
    var formHeight = 190 + cnt * 30;

    var t = entity == null ? "新增" + md.name : "编辑" + md.name;
    var f = entity == null
      ? "edit-form-create.png"
      : "edit-form-update.png";
    var logoHtml = "<img style='float:left;margin:10px 20px 0px 10px;width:48px;height:48px;' src='"
      + PSI.Const.BASE_URL
      + "Public/Images/"
      + f
      + "'></img>"
      + "<h2 style='color:#196d83'>"
      + t
      + "</h2>"
      + "<p style='color:#196d83'>标记 <span style='color:red;font-weight:bold'>*</span>的是必须录入数据的字段</p>";
    Ext.apply(me, {
      header: {
        title: me.formatTitle(PSI.Const.PROD_NAME),
        height: 40
      },
      width: 400,
      height: formHeight,
      layout: "border",
      listeners: {
        show: {
          fn: me.onWndShow,
          scope: me
        },
        close: {
          fn: me.onWndClose,
          scope: me
        }
      },
      items: [{
        region: "north",
        height: 90,
        border: 0,
        html: logoHtml
      }, {
        region: "center",
        border: 0,
        id: "PSI_CodeTable_RuntimeEditForm_editForm",
        xtype: "form",
        layout: {
          type: "table",
          columns: 1
        },
        height: "100%",
        bodyPadding: 5,
        defaultType: 'textfield',
        fieldDefaults: {
          labelWidth: 60,
          labelAlign: "right",
          labelSeparator: "",
          msgTarget: 'side',
          width: 370,
          margin: "5"
        },
        items: me.getEditItems(),
        buttons: buttons
      }]
    });

    me.callParent(arguments);

    me.editForm = Ext.getCmp("PSI_CodeTable_RuntimeEditForm_editForm");
  },

  getEditItems: function () {
    var me = this;

    var entity = me.getEntity();

    var md = me.getMetaData();
    if (!md) {
      return [];
    }

    var result = [{
      xtype: "hidden",
      name: "id",
      value: entity == null ? null : entity.get("id")
    }, {
      xtype: "hidden",
      name: "fid",
      value: me.getMetaData().fid
    }];

    var colsMd = md.cols;
    var colsCount = colsMd.length;
    for (var i = 0; i < colsCount; i++) {
      var colMd = colsMd[i];

      if (colMd.isVisible) {
        var item = {
          fieldLabel: colMd.caption,
          xtype: colMd.editorXtype,
          name: colMd.fieldName,
          id: me.buildEditId(colMd.fieldName),
          listeners: {
            specialkey: {
              fn: me.onEditSpecialKey,
              scope: me
            }
          }
        };

        if (md.treeView && colMd.fieldName == "parent_id") {
          // hiddenParentId用来在提交Form的时候向后台传递上级id
          var hiddenParentId = Ext.create("Ext.form.field.Hidden", {
            id: me.buildEditId("parent_id"),
            name: "parent_id"
          });
          result.push(hiddenParentId);

          Ext.apply(item, {
            idCmp: hiddenParentId,
            metadata: md,
            id: me.buildEditId("parent_id_value"),
            name: "parent_id_value"
          });
        }

        if (parseInt(colMd.valueFrom) == 2) {
          // 引用系统数据字典
          // TODO 当前是用combo来展示数据，当字典数据量大的时候是不合适的，需要进一步优化
          var store = Ext.create("Ext.data.ArrayStore", {
            fields: [colMd.valueFromColName, "name"],
            data: []
          });
          store.add(colMd.valueFromExtData);
          Ext.apply(item, {
            xtype: "combo",
            queryMode: "local",
            editable: false,
            valueField: colMd.valueFromColName,
            displayField: "name",
            store: store,
            value: store.getAt(0)
          });
        }

        if (colMd.mustInput) {
          // 必录项
          Ext.apply(item, {
            allowBlank: false,
            blankText: "没有输入" + colMd.caption,
            beforeLabelTextTpl: PSI.Const.REQUIRED
          });
        }

        result.push(item);
      }
    }

    return result;
  },

	/**
	 * 保存
	 */
  onOK: function (thenAdd) {
    var me = this;

    var f = me.editForm;
    var el = f.getEl();
    el && el.mask(PSI.Const.SAVING);
    var sf = {
      url: me.URL("/Home/CodeTable/editCodeTableRecord"),
      method: "POST",
      success: function (form, action) {
        me.__lastId = action.result.id;

        el && el.unmask();

        PSI.MsgBox.tip("数据保存成功");
        me.focus();
        if (thenAdd) {
          me.clearEdit();
        } else {
          me.close();
        }
      },
      failure: function (form, action) {
        el.unmask();
        PSI.MsgBox.showInfo(action.result.msg, function () {
          me.focusOnFirstEdit();
        });
      }
    };
    f.submit(sf);
  },

  clearEdit: function () {
    var me = this;
    var md = me.getMetaData();

    for (var i = 0; i < md.cols.length; i++) {
      var colMd = md.cols[i];
      if (colMd.isVisible) {
        var id = me.buildEditId(colMd.fieldName);
        var edit = Ext.getCmp(id);
        if (edit) {

          if (parseInt(colMd.valueFrom) == 2) {
            edit.setValue(edit.getStore().getAt(0));
          } else {
            edit.setValue(null);
            edit.clearInvalid();
          }
        }
      }
    }

    me.focusOnFirstEdit();
  },

  onWindowBeforeUnload: function (e) {
    return (window.event.returnValue = e.returnValue = '确认离开当前页面？');
  },

  onWndClose: function () {
    var me = this;

    Ext.get(window).un('beforeunload', me.onWindowBeforeUnload);

    if (me.__lastId) {
      if (me.getParentForm()) {
        me.getParentForm().refreshMainGrid(me.__lastId);
      }
    }
  },

  onWndShow: function () {
    var me = this;
    var md = me.getMetaData();

    Ext.get(window).on('beforeunload', me.onWindowBeforeUnload);

    var el = me.getEl();
    el && el.mask(PSI.Const.LOADING);
    Ext.Ajax.request({
      url: me.URL("/Home/CodeTable/recordInfo"),
      params: {
        id: me.adding ? null : me.getEntity().get("id"),
        fid: md.fid
      },
      method: "POST",
      callback: function (options, success, response) {
        if (success) {
          var data = Ext.JSON.decode(response.responseText);
          me.setDataForEdit(data);
        }

        el && el.unmask();
      }
    });
  },

  setDataForEdit: function (data) {
    var me = this;

    if (me.adding) {
      me.focusOnFirstEdit();
      return;
    }

    if (!data) {
      return;
    }

    var md = me.getMetaData();
    for (var i = 0; i < md.cols.length; i++) {
      var colMd = md.cols[i];
      if (colMd.isVisible) {
        var fieldName = colMd.fieldName;
        var id = me.buildEditId(fieldName);
        var edit = Ext.getCmp(id);
        if (edit) {
          if (fieldName == "parent_id") {
            id = me.buildEditId("parent_id_value");
            edit = Ext.getCmp(id);
            if (edit) {
              edit.setValue(data["parent_id_value"]);
              edit.setIdValue(data["parent_id"]);
            }
          } else {
            edit.setValue(data[fieldName]);
          }
        }
      }
    }

    me.focusOnFirstEdit();
  },

  focusOnFirstEdit: function () {
    var me = this;
    var md = me.getMetaData();

    // 把输入焦点设置为第一个可见的输入框
    for (var i = 0; i < md.cols.length; i++) {
      var colMd = md.cols[i];
      if (colMd.isVisible) {
        var id = me.buildEditId(colMd.fieldName);
        var edit = Ext.getCmp(id);
        if (edit) {
          edit.focus();
        }

        return;
      }
    }
  },

  buildEditId: function (fieldName) {
    var me = this;
    var md = me.getMetaData();
    return "PSI_CodeTable_RuntimeEditForm_edit_" + md.fid + "_" + fieldName;
  },

  onEditSpecialKey: function (field, e) {
    var me = this;

    if (e.getKey() === e.ENTER) {
      var id = field.getId();

      var md = me.getMetaData();
      var currentEditIndex = -1;
      for (var i = 0; i < md.cols.length; i++) {
        var colMd = md.cols[i];
        if (id == me.buildEditId(colMd.fieldName)) {
          currentEditIndex = i;

          if (currentEditIndex == md.cols.length - 1) {
            // 是最后一个编辑框，这时候回车就提交Form
            me.onOK(me.adding);
            return;
          } else {
            continue;
          }
        }

        if (currentEditIndex > -1) {
          var nextEditId = me.buildEditId(colMd.fieldName);
          var edit = Ext.getCmp(nextEditId);
          if (edit) {
            edit.focus();
            edit.setValue(edit.getValue());
            return;
          }
        }
      }
    }
  }
});
