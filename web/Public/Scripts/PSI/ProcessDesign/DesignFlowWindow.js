Ext.define("PSI.ProcessDesign.DesignFlowWindow", {
  extend: "PSI.AFX.BaseDialogForm",
  initComponent: function () {
    let me = this;

    Ext.apply(me, {
      cls: "PSI",
      header: {
        title: "设计流程",
        height: 30
      },
      width: 1000,
      height: 600,
      layout: "border",
      items: [
        {
          xtype: "panel",
          region: "west",
          layout: "fit",
          width: "60%",
          split: true,
          autoScroll: false,
          collapsible: false,
          header: false,
          border: 1,
          html: "<div id='DesignContainer'></div>"
        },
        {
          region: "center",
          xtype: "panel",
          layout: "fit",
          border: 0,
          items: me.getNodeInfoPanel()
        }
      ],
      tbar: [
        {
          text: "添加起始步骤",
          handler: me.onAddStartStep,
          scope: me
        },
        {
          text: "添加普通步骤",
          handler: me.onAddStep,
          scope: me
        },
        {
          text: "刷新",
          handler: me.repaint,
          scope: me
        },
        {
          text: "保存设计",
          handler: me.onSaveDesign,
          scope: me
        }, "|",
        {
          text: "<span style='color:red;'>*</span> 单击可以对步骤信息进行编辑，双击可以删除步骤或连接线"
        }
      ],
      listeners: {
        "close": {
          fn: function () {
            me.__firstInstance.deleteEveryEndpoint();
            me.__firstInstance.clear();
          },
          scope: me
        }
      }
    });

    me.callParent(arguments);
    me.loadjsPlumb();

  },
  //加载jsPlumb
  loadjsPlumb: function () {
    let me = this;


    me.connections = [];
    jsPlumb.ready(function () {
      document.getElementById('Preview').innerHTML = "";
      me.__firstInstance = jsPlumb.getInstance();
      me.__firstInstance.deleteEveryEndpoint();
      me.__firstInstance.clear();

      me.__firstInstance.importDefaults({
        ConnectionsDetachable: false
      });
      //绑定事件
      me.jsPlumbEvent();
      me.loadDesign();
    })
  },
  //jsPlumb的事件
  jsPlumbEvent: function () {
    let me = this;
    //删除线事件
    me.__firstInstance.bind('dblclick', function (conn) {
      me.confirm("是否删除这条线？", function () {
        jsPlumb.deleteConnection(conn);
      })
    });
    //连接前触发事件
    me.__firstInstance.bind('beforeDrop', function (info, originalEvent) {
      let connLen = me.connections.length;
      //不能自己连接自己
      if (info['sourceId'] == info['targetId']) {
        jsPlumb.deleteConnection(info['connection']);
        return false;
      }
      //如果没有建立过任何连接，直接建立连接
      if (!connLen) {
        me.connections.push({
          "source": info['sourceId'],
          "target": info['targetId']
        });
        return true;
      }
      //不能重复建立连接
      for (let i = 0; i < connLen; i++) {
        if (me.connections[i]['source'] == info['sourceId']
          && me.connections[i]['target'] == info['targetId']) {
          return false;
        }
        //不能循环建立连接
        if (me.connections[i]['source'] == info['targetId']
          && me.connections[i]['target'] == info['sourceId']) {
          return false;
        }
      }
      me.connections.push({
        "source": info['sourceId'],
        "target": info['targetId']
      });
      return true;

    });
  },
  //节点的公共配置
  getItemConfig: function () {
    return {
      isSource: true,
      isTarget: true,
      connector: ["Flowchart"],
      paintStyle: {stroke: '#5e96db', strokeWidth: 2},
      endpointStyle: {fill: 'lightgray', outlineStroke: 'lightgray'},
      maxConnections: -1,
      endpoint: ["Dot", {radius: 1}],
      anchor: 'Continuous',
      ConnectorZIndex: 5,
      overlays: [['Arrow', {width: 12, length: 12, location: 0.5}]]
    };
  },
  //加载设计流程
  loadDesign: function () {
    let me = this;
    let entity = me.getEntity();
    me.ajax({
      url: me.URL("Home/ProcessDesign/loadDesign"),
      params: {
        id: entity['Id']
      },
      success: function (response) {
        let data = me.decodeJSON(response['responseText']);
        for (let i = 0, len = data['data'].length; i < len; i++) {
          let node = document.createElement('div');
          node.innerHTML = data['data'][i]['process_name'];
          node.setAttribute("name", data['data'][i]['process_type']);
          node.setAttribute("class", "item");
          node.setAttribute("style",
            'top: '
            + data['data'][i]['set_top'] + 'px;left: '
            + data['data'][i]['set_left'] + 'px;');
          node.setAttribute("id", data['data'][i]['id']);
          document.getElementById("DesignContainer").appendChild(node);
          me.__firstInstance.draggable(data['data'][i]['id'], {containment: "#DesignContainer"});
          me.__firstInstance.addEndpoint(data['data'][i]['id'], {
            anchor: "Center",
            endpoint: ["Dot", {radius: 3}]
          }, me.getItemConfig());
          let dom = Ext.get(data['data'][i]['id']);
          dom.on("click", me.onEditNode, me);
          dom.on("dblclick", me.onDeleteNode, me);

        }
        for (let i = 0, len = data['data'].length; i < len; i++) {
          if (data['data'][i]['process_to']) {
            let targetArr = data['data'][i]['process_to'].split(',');
            for (let j = 0; j < targetArr.length; j++) {
              me.__firstInstance.connect({
                source: data['data'][i]['id'],
                target: targetArr[j]
              }, me.getItemConfig());
            }
          }
        }
      }
    })

  },
  //刷新设计
  repaint: function () {
    let me = this;
    document.getElementById('DesignContainer').innerHTML = "";
    me.__firstInstance.deleteEveryEndpoint();
    me.__firstInstance.clear();
    me.loadDesign();
  },
  //获取步骤Panel
  getNodeInfoPanel: function () {
    let me = this;
    if (me.__nodeInfoPanel) {
      return me.__nodeInfoPanel;
    }

    me.__nodeInfoPanel = Ext.create("Ext.form.Panel", {
      cls: "PSI",
      defaultType: 'textfield',
      border: 0,
      items: [
        {
          xtype: "hiddenfield",
          id: "id",
          name: "id",
          value: ""
        },
        {
          id: "ProcessName",
          fieldLabel: "步骤名称",
          name: "processName",
          labelWidth: 65,
          padding: '10 0 0 20',
          width: 200,
          value: ""
        },
        {
          id: "ProcessType",
          fieldLabel: "步骤类型",
          xtype: "combo",
          editable: false,
          padding: '10 0 0 20',
          valueField: "type",
          labelWidth: 60,
          hidden: true,
          store: Ext.create("Ext.data.ArrayStore", {
            fields: ["type", "text"],
            data: [["Step", "正常"], ["End", "最后一步"]]
          }),
          value: "Step"
        },
        {
          id: "BackgroundColor",
          fieldLabel: "背景颜色",
          name: "backgroundColor",
          labelWidth: 65,
          padding: '10 0 0 20',
          width: 250,
          emptyText: "填单词‘red’或16进制‘#ccc’",
          value: ""
        },
        {
          id: "Color",
          fieldLabel: "字体颜色",
          name: "color",
          labelWidth: 65,
          padding: '10 0 0 20',
          width: 250,
          emptyText: "填单词‘red’或16进制‘#ccc’",
          value: ""
        },
        {
          disabled: true,
          width: 600,
        },
        {
          id: "ProcessingMode",
          fieldLabel: "办理方式",
          xtype: "combo",
          name: "processingMode",
          editable: false,
          padding: '10 0 0 20',
          valueField: "type",
          labelWidth: 60,
          store: Ext.create("Ext.data.ArrayStore", {
            fields: ["type", "text"],
            data: [["user", "用户"], ["role", "角色"]]
          }),
          value: "user",
          listeners: {
            change: {
              fn: function (self, newValue) {
                if (newValue == "role") {
                  me.form["IsSing"].setVisible(false);
                  me.form["UserText"].setVisible(false);
                  me.form["RoleText"].setVisible(true);
                  me.form["UserText"].setValue('');
                  me.form["UserIds"].setValue('');

                } else {
                  me.form["IsSing"].setVisible(true);
                  me.form["UserText"].setVisible(true);
                  me.form["RoleText"].setVisible(false);
                }
              }
            }
          }
        },
        {
          xtype: "checkbox",
          id: "IsUserEnd",
          fieldLabel: "允许审核人直接结束流程",
          name: "isUserEnd",
          labelWidth: 150,
          width: 250,
          padding: '10 0 0 20'
        },
        {
          xtype: "checkbox",
          id: "IsUseropPass",
          fieldLabel: "允许抄送人代审核",
          name: "isUseropPass",
          labelWidth: 110,
          width: 250,
          padding: '10 0 0 20'
        },
        {
          xtype: "hiddenfield",
          id: "ResponIds",
          name: "responIds",
          listeners: {
            "change": {
              fn: function (self, newValue) {
                let me = this;
                if (!newValue) {
                  return true;
                }
                let idsarr = newValue.split(',');
                let uidarr = me.form['UserIds'].getValue().split(',');
                for (let i = 0, len = idsarr.length; i < len; i++) {
                  for (let j = 0, ulen = uidarr.length; j < ulen; j++) {
                    if (idsarr[i] == uidarr[j]) {
                      me.showInfo("抄送人和审核人不能相同");
                      self.setValue("");
                      self.nextSibling().self().setValue("");
                      return;
                    }
                  }
                }
              },
              scope: me
            }
          },
          value: ""
        },
        {
          id: "ResponText",
          name: "responText",
          fieldLabel: "选择抄送人",
          labelWidth: 80,
          width: 250,
          padding: '10 0 0 20',
          value: "",
          listeners: {
            "focus": {
              fn: function (self) {
                let form = Ext.create("PSI.ProcessDesign.SelectUserForm", {
                  parentForm: me,
                  entity: ['ResponIds', 'ResponText']
                });
                form.show();
              },
              scope: me
            }
          }
        },
        {
          xtype: "checkbox",
          id: "IsSing",
          fieldLabel: "开启会签",
          name: "isSing",
          labelWidth: 60,
          width: 250,
          padding: '10 0 0 20'
        },
        {
          xtype: "hiddenfield",
          id: "UserIds",
          name: "userIds",
          listeners: {
            "change": {
              fn: function (self, newValue) {
                let me = this;
                if (!newValue) {
                  return true;
                }
                let uidarr = newValue.split(',');
                let rearr = me.form['ResponIds'].getValue().split(',');
                for (let i = 0, ulen = uidarr.length; i < ulen; i++) {
                  for (let j = 0, len = rearr.length; j < len; j++) {
                    if (rearr[i] == uidarr[j]) {
                      me.showInfo("抄送人和审核人不能相同");
                      self.setValue("");
                      self.nextSibling().self().setValue("");
                      return;
                    }
                  }
                }
              },
              scope: me
            }
          },
          value: ""
        },
        {
          id: "UserText",
          name: "userText",
          fieldLabel: "选择审核人",
          labelWidth: 80,
          width: 250,
          padding: '10 0 0 20',
          value: "",
          listeners: {
            "focus": {
              fn: function (self) {
                let form = Ext.create("PSI.ProcessDesign.SelectUserForm", {
                  parentForm: me,
                  entity: ['UserIds', 'UserText']
                });
                form.show();
              },
              scope: me
            }
          }
        },
        {
          xtype: "hiddenfield",
          id: "RoleIds",
          name: "roleIds",
          value: ""
        },
        {
          id: "RoleText",
          name: "roleText",
          fieldLabel: "请选择角色",
          labelWidth: 90,
          width: 250,
          padding: '10 0 0 20',
          hidden: true,
          value: "",
          listeners: {
            "focus": {
              fn: function (self) {
                let form = Ext.create("PSI.ProcessDesign.SelectRoleForm", {
                  parentForm: me
                });
                form.show();
              },
              scope: me
            }
          }
        },
        {
          id: "IsBack",
          fieldLabel: "是否可回退",
          name: "isBack",
          xtype: "combo",
          editable: false,
          padding: '10 0 0 20',
          valueField: "id",
          labelWidth: 70,
          store: Ext.create("Ext.data.ArrayStore", {
            fields: ["id", "text"],
            data: [['0', "不允许"], ['1', "回到开始"], ['2', "回到上一步"]]
          }),
          value: '0'
        },
        {
          disabled: true,
          width: 600,
        },
        {
          xtype: 'button',
          text: '保存步骤信息',
          margin: '10 0 0 20',
          scope: me,
          handler: me.onSaveProcessInfo
        },
        {
          xtype: 'button',
          text: '重置步骤信息',
          margin: '10 0 0 20',
          scope: me,
          handler: me.resetProcessInfo
        }

      ]
    });
    me.form = {};
    me.form['id'] = Ext.getCmp("id");
    me.form['ProcessName'] = Ext.getCmp("ProcessName");
    me.form['ProcessType'] = Ext.getCmp("ProcessType");
    me.form['BackgroundColor'] = Ext.getCmp("BackgroundColor");
    me.form['Color'] = Ext.getCmp("Color");
    me.form['ProcessingMode'] = Ext.getCmp("ProcessingMode");
    me.form['IsUserEnd'] = Ext.getCmp("IsUserEnd");
    me.form['IsUseropPass'] = Ext.getCmp("IsUseropPass");
    me.form['ResponIds'] = Ext.getCmp("ResponIds");
    me.form['ResponText'] = Ext.getCmp("ResponText");
    me.form['IsSing'] = Ext.getCmp("IsSing");
    me.form['UserIds'] = Ext.getCmp("UserIds");
    me.form['UserText'] = Ext.getCmp("UserText");
    me.form['RoleIds'] = Ext.getCmp("RoleIds");
    me.form['RoleText'] = Ext.getCmp("RoleText");
    me.form['IsBack'] = Ext.getCmp("IsBack");

    return me.__nodeInfoPanel;
  },
  //设置已选择的人员
  setCheckUser: function (data, entity) {
    let me = this;
    let ids = "";
    let names = "";
    for (let i = 0, len = data.length; i < len; i++) {
      if ((i + 1) == len) {
        ids += data[i]["internalId"];
        names += data[i]['data']['name']
      } else {
        ids += data[i]["internalId"] + ",";
        names += data[i]['data']['name'] + ",";
      }
    }
    if (entity[0] == 'UserIds') {
      if (me.form['IsSing'].getValue()) {
        ids = data[0]["internalId"];
        names = data[0]['data']['name'];
      }
    }

    me.form[[entity[0]]].setValue(ids);
    me.form[entity[1]].setValue(names);
  },
  //设置已选择的角色
  setCheckRole: function (data) {
    let me = this;
    Ext.getCmp("RoleIds").setValue(data[0]['internalId']);
    Ext.getCmp("RoleText").setValue(data[0]['data']['name']);
  },
  //添加起始节点
  onAddStartStep: function () {
    let me = this;
    let type = "StartStep";
    let entity = me.getEntity();
    let params = {
      flowId: entity["Id"]
    };
    let node = document.getElementsByName(type);
    if (node.length) {
      return me.showInfo("起始节点只能存在一个");
    }
    me.buildTarget("div", "起始节点", type, params);
  },
  //添加普通步骤
  onAddStep: function () {
    let me = this;
    let type = "Step"
    let entity = me.getEntity();
    let params = {
      flowId: entity["Id"]
    };
    me.buildTarget("div", "步骤", type, params);
  },
  //保存设计流程
  onSaveDesign: function () {
    let me = this;
    let elements = me.__firstInstance.getManagedElements();  //得到点
    console.log(elements);
    let conns = me.__firstInstance.getConnections(); // 得到线
    let trim = function (str, isglobal) {
      let result
      result = str.replace(/(^\s+)|(\s+$)/g, '')
      if (isglobal && isglobal.toLowerCase() === 'g') {
        result = result.replace(/\s/g, '')
      }
      return result
    }
    for (let key in elements) {
      let el = elements[key]['el'];
      let stylestr = el.attributes['style']['value'];
      let Arr = stylestr.split(';');
      Arr = Arr.filter(item => {
        return item != ''
      });
      let str = '';
      Arr.forEach(item => {
        let test = '';
        trim(item).split(':').forEach(item2 => {
          test += '"' + trim(item2) + '":'
        });
        str += test + ','
      });
      str = str.replace(/:,/g, ',')
      str = str.substring(0, str.lastIndexOf(','))
      str = '{' + str + '}';

      let positionObj = JSON.parse(str);
      let setTop = positionObj['top'].substr(0, positionObj['top'].length - 2);
      let setLeft = positionObj['left'].substr(0, positionObj['left'].length - 2);

      let processTo = "";

      for (let i = 0; i < conns.length; i++) {
        if (conns[i]['sourceId'] == key) {
          processTo += conns[i]['targetId'] + ",";
        }
        if ((i + 1) == conns.length) {
          processTo = processTo.substring(0, processTo.length - 1);
        }
      }

      me.ajax({
        url: me.URL("Home/ProcessDesign/saveDesign"),
        params: {
          'id': key,
          'processTo': processTo,
          'setTop': setTop,
          'setLeft': setLeft,
          'style': positionObj
        },
        success: function (response) {
          let data = me.decodeJSON(response['responseText']);
          if (!data['success']) {
            return me.showInfo(data['msg']);
          }
        }
      });
    }

    me.repaint();

  },
  //快速创建一个节点
  buildTarget: function (target, html, type, params) {
    let me = this;
    params['processType'] = type
    me.saveProcessXHR(params, function (data) {
      let node = document.createElement(target);
      node.innerHTML = html;
      node.setAttribute("name", type);
      node.setAttribute("class", "item");
      node.setAttribute("style", 'top: 20px;left: 50px;');
      node.setAttribute("id", data['data']);
      document.getElementById("DesignContainer").appendChild(node);
      me.__firstInstance.draggable(data['data'], {containment: "#DesignContainer"});
      me.__firstInstance.addEndpoint(data['data'], {
        anchor: "Center",
        endpoint: ["Dot", {radius: 3}]
      }, me.getItemConfig());
      let dom = Ext.get(data['data']);
      dom.on("click", me.onEditNode, me);
      dom.on("dblclick", me.onDeleteNode, me);
    });
  },
  //保存步骤信息XHR
  saveProcessXHR: function (params, callback) {
    let me = this;
    me.ajax({
      url: me.URL("Home/ProcessDesign/saveProcess"),
      params: params,
      success: function (resposne) {
        let data = me.decodeJSON(resposne.responseText);
        if (!data['success']) {
          me.showInfo(data['msg']);
          return data['success'];
        }
        callback(data);
      }
    });
  },
  //编辑步骤信息
  onEditNode: function (e, dom) {
    e.preventDefault();
    let me = this;
    me.resetProcessInfo();
    let domId = dom.attributes['id']['value'];
    let formCmp = me.form;
    me.ajax({
      url: me.URL("Home/ProcessDesign/getNodeInfo"),
      params: {
        id: domId
      },
      success: function (response) {
        let data = me.decodeJSON(response['responseText']);
        if (!data['success']) {
          return me.showInfo(data['msg']);
        }
        formCmp['id'].setValue(domId);
        formCmp['ProcessName'].setValue(data['data']['process_name']);
        if (data['data']['process_type'] == 'StartStep') {
          formCmp['ProcessType'].setVisible(false);
        } else {
          formCmp['ProcessType'].setVisible(true);
        }
        formCmp['ProcessType'].setValue(data['data']['process_type']);
        formCmp['IsUserEnd'].setValue(data['data']['is_user_end']);
        formCmp['IsUseropPass'].setValue(data['data']['is_userop_pass']);
        formCmp['ResponIds'].setValue(data['data']['respon_ids']);
        formCmp['ResponText'].setValue(data['data']['respon_text']);
        formCmp['IsSing'].setValue(data['data']['is_sing']);
        formCmp['UserIds'].setValue(data['data']['user_ids']);
        formCmp['UserText'].setValue(data['data']['user_text']);
        formCmp['IsBack'].setValue(data['data']['is_back']);
        formCmp['RoleIds'].setValue(data['data']['role_ids']);
        formCmp['RoleText'].setValue(data['data']['role_text']);
        formCmp['ProcessingMode'].setValue(data['data']['processing_mode']);
      }
    });


  },
  //删除步骤
  onDeleteNode: function (e, dom) {
    e.preventDefault();
    let me = this;
    let id = dom.attributes['id'].value;

    me.confirm('确定删除此节点？', function () {
      me.__firstInstance.deleteConnectionsForElement(dom);
      dom.remove();

      me.ajax({
        url: me.URL("Home/ProcessDesign/deleteNode"),
        params: {
          id: id
        },
        success: function (response) {
          let data = me.decodeJSON(response['responseText']);
          if (data['success']) {
            me.onSaveDesign();
            me.resetProcessInfo();
          }
          return me.showInfo(data['msg']);

        }
      });

    })

  },
  //保存步骤信息
  onSaveProcessInfo: function () {
    let me = this;
    if (!me.form['id'].getValue()) {
      return me.showInfo("请先选择步骤");
    }

    let params = {};
    params['id'] = me.form['id'].getValue();
    params['processName'] = me.form['ProcessName'].getValue();
    params['processType'] = me.form['ProcessType'].getValue();
    params['backgroundColor'] = me.form['BackgroundColor'].getValue();
    params['color'] = me.form['Color'].getValue();
    params['processingMode'] = me.form['ProcessingMode'].getValue();
    params['isUserEnd'] = me.form['IsUserEnd'].getValue();
    params['isUseropPass'] = me.form['IsUseropPass'].getValue();
    params['responIds'] = me.form['ResponIds'].getValue();
    params['responText'] = me.form['ResponText'].getValue();
    params['isSing'] = me.form['IsSing'].getValue();
    params['userIds'] = me.form['UserIds'].getValue();
    params['userText'] = me.form['UserText'].getValue();
    params['roleIds'] = me.form['RoleIds'].getValue();
    params['roleText'] = me.form['RoleText'].getValue();
    params['isBack'] = me.form['IsBack'].getValue();
    me.saveProcessXHR(params, function (data) {
      me.showInfo(data['msg']);
      let dom = document.getElementById(params['id']);
      dom.innerText = params['processName'];
    });
  },
  //重置步骤信息
  resetProcessInfo: function () {
    let me = this;
    me.form['id'].setValue('');
    me.form['ProcessName'].setValue('');
    me.form['ProcessType'].setValue('');
    me.form['BackgroundColor'].setValue('');
    me.form['Color'].setValue('');
    me.form['ProcessingMode'].setValue('');
    me.form['IsUserEnd'].setValue(false);
    me.form['IsUseropPass'].setValue(false);
    me.form['ResponIds'].setValue('');
    me.form['ResponText'].setValue('');
    me.form['IsSing'].setValue(false);
    me.form['UserIds'].setValue('');
    me.form['UserText'].setValue('');
    me.form['RoleIds'].setValue('');
    me.form['RoleText'].setValue('');
    me.form['IsBack'].setValue(false);
  }

})
