Ext.define("PSI.JointlySign.ExamineWindow", {
  extend: "PSI.AFX.BaseDialogForm",
  initComponent: function () {
    let me = this;

    Ext.apply(me, {
      width: 1000,
      height: 600,
      layout: "border",
      items: [{
        title: "审核",
        xtype: "panel",
        region: "west",
        layout: "fit",
        width: "33%",
        height: "30%",
        split: false,
        collapsible: false,
        border: 1,
        items: [me.getWestPanel()]
      }, {
        title: "审核进度",
        region: "center",
        xtype: "panel",
        height: "30%",
        layout: "fit",
        border: 0,
        items: [me.getCenterPanel()]
      }, {
        title: "文件信息",
        region: "south",
        xtype: "panel",
        layout: "fit",
        height: "30%",
        border: 0,
        items: [me.getSouthPanel()]
      },
        {
          title: "会签进度",
          region: "south",
          xtype: "panel",
          layout: "fit",
          height: "40%",
          border: 0,
          items: [me.getJointlySignAdvance()]
        }
      ]
    });

    me.callParent(arguments);
  },
  //审核Panel
  getWestPanel: function () {
    let me = this;
    if (me.__west)
      return me.__west;

    let data = me.getEntity();
    me.__west = Ext.create("Ext.form.Panel", {
      header: false,
      border: 0,
      padding: "0 0 0 10",
      items: [{
        id: "content",
        fieldLabel: "意见:",
        labelWidth: 30,
        anchor: '100%',
        xtype: "textarea",
        autoScroll: true,
        value: "",
      }, {
        xtype: "button",
        disabled: false,
        text: "通过",
        margin: "10 0 0 10",
        handler: me.onPass,
        scope: me
      }, {
        xtype: "button",
        disabled: false,
        margin: "10 0 0 10",
        text: "不通过",
        handler: me.onFail,
        scope: me
      }]
    });

    return me.__west;

  },
  //审核进度Panel
  getCenterPanel: function () {
    let me = this;
    if (me.__center)
      return me.__center;

    let data = me.getEntity();

    let modelName = "CenterModel";
    Ext.define(modelName, {
      extend: "Ext.data.Model",
      fields: ["sponsorUser", "remark", "status", "bltime", "receiveTime"]
    });

    let Store = Ext.create('Ext.data.Store', {
      autoLoad: true,
      pageSize: 10,
      model: modelName,
      proxy: {
        type: "ajax",
        url: me.URL("Home/JointlySign/flowAdvance"),
        actionMethods: {
          read: "POST"
        },
        extraParams: {
          signId: data['id'],
          runProcessId: data['runProcessId']
        },
        reader: {
          type: 'json',
          root: 'dataList',
          totalProperty: 'totalCount'
        }
      }
    });

    me.__center = Ext.create("Ext.grid.Panel", {
      cls: "PSI",
      border: 0,
      columnLines: true,
      store: Store,
      columns: [
        {xtype: "rownumberer", width: "6%", header: "序号"},
        {header: '审批人', width: "14%", dataIndex: 'sponsorUser', sortable: false, menuDisabled: true},
        {header: '操作', width: "11%", dataIndex: 'status', sortable: false, menuDisabled: true},
        {header: '接收时间', width: "22.5%", dataIndex: "receiveTime", sortable: false, menuDisabled: true},
        {header: '审核时间', width: "22.5%", dataIndex: "bltime", sortable: false, menuDisabled: true},
        {header: '审批意见', width: "23.5%", dataIndex: 'remark', sortable: false, menuDisabled: true}
      ]
    });

    return me.__center;
  },
  //文件信息Panel
  getSouthPanel: function () {
    let me = this;
    let data = me.getEntity();
    let modelName = "SouthModel";
    Ext.define(modelName, {
      extend: "Ext.data.Model",
      fields: ["suffix", "size", "name", "path"]
    });
    let Store = Ext.create('Ext.data.Store', {
      model: modelName,
      data: []
    });
    me.ajax({
      url: me.URL("Home/Examine/getFileInfoByRunId"),
      params: {
        runId: data['runId']
      },
      success: function (response) {
        let p_json = Ext.JSON.decode(response["responseText"]);
        let file_json = Ext.JSON.decode(p_json["params_json"]);
        if (file_json['vType'] == "file") {
          Store.add(file_json);
        } else {

        }
      }
    });

    me.__south = Ext.create("Ext.grid.Panel", {
      cls: "PSI",
      border: 1,
      columnLines: true,
      store: Store,
      columns: [
        {header: '文件名', width: "30%", dataIndex: 'name', sortable: false, menuDisabled: true},
        {header: '拓展名', width: "20%", dataIndex: 'suffix', sortable: false, menuDisabled: true},
        {
          header: '文件大小',
          width: "20%",
          dataIndex: "size",
          sortable: false,
          menuDisabled: true,
          renderer: function (val) {
            val /= 1024;
            if (val < 1024)
              return val.toFixed(2) + "KB";
            return (val / 1024).toFixed(2) + "M";
          }
        },
        {
          header: '预览',
          width: "29.8%",
          dataIndex: 'path',
          sortable: false,
          menuDisabled: true,
          renderer: function (val) {
            return "<a href='" + me.URL(val) + "' target='_blank'>预览</a>"
          }
        }
      ]
    });
    return me.__south;

  },
  //获取会签进度
  getJointlySignAdvance: function () {
    let me = this;
    let data = me.getEntity();
    if (me.__jointlySignAdvance)
      return me.__jointlySignAdvance;
    let modelName = "JointlySignAdvanceModel";
    Ext.define(modelName, {
      extend: "Ext.data.Model",
      fields: ["id", "uName", "isAgree", "receiveTime", "content"]
    });
    let Store = Ext.create('Ext.data.Store', {
      model: modelName,
      data: []
    });

    let toolbar = Ext.create('Ext.toolbar.Toolbar', {
      width: 700,
      items: []
    });

    me.ajax({
      url: me.URL("/Home/JointlySign/jointlySignAdvance"),
      params: {
        runProcessId: data['runProcessId']
      },
      success: function (response) {
        let data = me.decodeJSON(response['responseText']);
        Store.add(data);
      }
    });

    me.ajax({
      url: me.URL("/Home/JointlySign/loadJointlyCount"),
      params: {
        runProcessId: data['runProcessId']
      },
      success: function (response) {
        let data = me.decodeJSON(response['responseText']);
        let str = "会签人数共： " + data['receiveCount'] + "/" + data['jointlySignCount'] + " 人，" +
          "待审核：" + data['unauditedCount'] + " 人，" +
          "通过：" + data['passCount'] + " 人，" +
          "不通过：" + data['failCount'] + " 人";
        toolbar.add({
          text: str
        });
      }
    });

    me.__jointlySignAdvance = Ext.create("Ext.grid.Panel", {
      cls: "PSI",
      border: 1,
      columnLines: true,
      store: Store,
      columns: [
        {xtype: "rownumberer", width: "10%", header: "序号"},
        {header: '审核人', width: "20%", dataIndex: 'uName', sortable: false, menuDisabled: true},
        {header: '接收时间', width: "25%", dataIndex: 'receiveTime', sortable: false, menuDisabled: true},
        {
          header: '操作',
          width: "10%",
          dataIndex: 'isAgree',
          sortable: false,
          menuDisabled: true,
          renderer: function (val) {
            val = ~~val;
            return val == 0 ? "待审核" : val == 1 ? "已通过" : "不通过";
          }
        },
        {header: '意见', width: "34.5%", dataIndex: 'content', sortable: false, menuDisabled: true}
      ],
      bbar: toolbar
    });

    return me.__jointlySignAdvance;
  },
  //通过
  onPass: function () {
    let me = this;
    me.send("/Home/JointlySign/pass", "审核过后，审核意见不可改，是否操作？", me);
  },
  //不通过
  onFail: function () {
    let me = this;
    me.send("/Home/JointlySign/fail", "提交后审核意见不可改，是否操作？", me);
  },
  send: function (url, msg, me) {
    let data = me.getEntity();
    me.confirm(msg, function () {
      let contentCmp = Ext.getCmp("content");
      me.ajax({
        url: me.URL(url),
        params: {
          signId: data['id'],
          content: contentCmp.getValue()
        },
        success: function (response) {
          let data = me.decodeJSON(response['responseText']);
          if (data['success']) {
            me.close();
          }
          me.showInfo(data['msg']);
          location.reload();
        }
      });
    });
  }
});
