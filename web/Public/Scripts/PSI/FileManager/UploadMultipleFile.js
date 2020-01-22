/**
 * 上传文件
 **/

Ext.define("PSI.FileManager.UploadMultipleFile", {
  extend: "PSI.AFX.BaseDialogForm",
  config: {
    filePath: null
  },
  initComponent: function () {
    var me = this;
    var entity = me.getEntity();

    var t = "上传文件";
    var f = "edit-form-create.png";
    var logoHtml = "<img style='float:left;margin:10px 20px 0px 10px;width:48px;height:48px;' src='"
      + PSI.Const.BASE_URL
      + "Public/Images/"
      + f
      + "'></img>"
      + "<h2 style='color:#196d83'>"
      + t
      + "</h2>"

    Ext.apply(me, {
      header: {
        title: me.formatTitle(PSI.Const.PROD_NAME),
        height: 40
      },
      width: 550,
      height: 400,
      layout: "border",
      items: [{
        region: "north",
        border: 0,
        height: 90,
        html: logoHtml
      }, {
        region: "center",
        border: 0,
        id: "PSI_FileManager_UpFileForm",
        xtype: "form",
        fileUpload: true,
        frame: true,
        autoScroll: true,
        layout: {
          type: "table",
          columns: 1
        },
        height: "100%",
        bodyPadding: 5,
        defaultType: 'textfield',
        fieldDefaults: {
          labelWidth: 80,
          labelAlign: "right",
          labelSeparator: "",
          msgTarget: 'side'
        },
        items: [
          // {
          //   xtype: "displayfield",
          //   fieldLabel: " ",
          //   width: 500,
          //   value: "<div id='console'></div>"
          // },
          {
            xtype: "displayfield",
            fieldLabel: "选择文件:",
            id: "uploadBtn",
            width: 500,
            value: "<a href='javascript:;' id='uploadBtn'>选择文件</a>"
          },
          {
            id: "uploadFilesInfo",
            xtype: "displayfield",
            fieldLabel: "已选择文件:",
            width: 500,
            value: "<div id='filesInfo'></div>&nbsp;<a href='javascript:;' id='cleanFiles'>清空文件</a>"
          },
          {
            fieldLabel: "文件编码:",
            id: "fileCode",
            width: 500,
          },
          {
            id: "actionInfo",
            fieldLabel: "描述:",
            xtype: "textarea",
            autoScroll: true,
            width: 500,
            value: "",
          }],
        listeners: {
          afterrender: {
            fn: me.initUploadcomponment,
            scope: me
          }
        },
        buttons: [{
          text: "确定",
          formBind: true,
          iconCls: "PSI-button-ok",
          id: "okBtn",
          // handler: me.onOK,
          // scope: me
        }, {
          text: "取消",
          handler: function () {
            me.confirm("请确认是否取消操作?", function () {
              me.close();
            });
          },
          scope: me
        }]
      }],
      listeners: {
        close: {
          fn: me.onWndClose,
          scope: me
        }
      }
    });

    me.callParent(arguments);
    me.editParentDirId = Ext.getCmp("PSI_FileManager_UpFileForm_parentDirId");
    me.editActionInfo = Ext.getCmp("PSI_FileManager_UpFileForm_actionInfo");
    me.parentDir = Ext.getCmp("PSI_FileManager_UpFileForm_parentDir");

    me.editForm = Ext.getCmp("PSI_FileManager_UpFileForm");
  },
  onWindowBeforeUnload: function (e) {
    return (window.event.returnValue = e.returnValue = '确认离开当前页面？');
  },
  onWndClose: function () {
    var me = this;

    Ext.get(window).un('beforeunload', me.onWindowBeforeUnload);
  },
  //初始化组件
  initUploadcomponment: function () {
    var me = this;
    var entity = me.getEntity();
    var el = me.getEl() || Ext.getBody();
    me.ajax({
      url: me.URL("Home/SuffixConfig/loadSuffix"),
      success: function (response) {
        var rs = me.decodeJSON(response.responseText);
        var mime_types = [
          {title: "office", extensions: rs.office.replace(/-/g, ',')},
          {title: "picture", extensions: rs.picture.replace(/-/g, ',')},
          {title: "other", extensions: rs.other.replace(/-/g, ',')}
        ];

        var uploader = new plupload.Uploader({
          browse_button: document.getElementById('uploadBtn'),
          url: me.URL("Home/FileManager/upFile"),
          flash_swf_url: me.URL("Public/plupload/Moxie.swf"),
          silverlight_xap_url: me.URL("Public/plupload/Moxie.xap"),
          filters: {
            max_file_size: '200mb',
            mime_types: mime_types,
            prevent_duplicates: true//不允许重复文件
          },
          init: {
            PostInit: function () {
              Ext.get("okBtn").dom.onclick = function () {
                var count = uploader.files.length;
                if (count > 10) {
                  me.showInfo("一次只允许上传10个文件");
                } else if (count == 0) {
                  me.showInfo("没有要上传的文件");
                } else {
                  var parentDirId = entity["Name"] == "../" ? entity["parentDirID"] : entity["id2"];
                  var actionInfo = Ext.getCmp("actionInfo").getValue();
                  var fileCode = Ext.getCmp("fileCode").getValue();
                  uploader.setOption({
                    multipart_params: {
                      parentDirID: parentDirId,
                      actionInfo: actionInfo,
                      fileCode: fileCode
                    }
                  });
                  uploader.start();
                  el.mask("上传中，请稍等");
                }
                return false;
              };
              document.getElementById("cleanFiles").onclick = function (e) {
                e.preventDefault();
                uploader.splice(0, uploader.files.length);
                document.getElementById("filesInfo").innerHTML = "";
              };
            },

            FilesAdded: function (up, files) {
              plupload.each(files, function (file) {
                document.getElementById("filesInfo").innerHTML +=
                  '<span style="margin-right: 10px;" id="' + file.id + '">' + file.name + ' (' +
                  plupload.formatSize(file.size) + '),</span>';
              });
            }

          }
        });

        uploader.init();

        uploader.bind("UploadComplete", function (uploader, file) {
          console.log(uploader);
          console.log(file);
          me.showInfo("上传完成",function () {
            me.close();
            me.getParentForm().freshFileGrid();
            el.unmask();
          });
        });
        uploader.bind("Error",function (uploader,err) {
          console.log(uploader);
          console.log(err);
        })

      }
    });

  },
  onOK: function () {
    var me = this;
    var f = me.editForm;
    var el = f.getEl();
    el.mask("数据保存中...");
    f.submit({
      url: me.URL("Home/FileManager/upFile"),
      method: "POST",
      params: {
        path: me.getFilePath()
      },
      success: function (form, action) {
        el.unmask();
        me.close();
        me.getParentForm().freshFileGrid();
      },
      failure: function (form, action) {
        el.unmask();
        me.close();
        me.showInfo(action.result.msg);
      }
    });
  },
});
