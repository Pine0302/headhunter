define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 're/apply/index',
                    add_url: 're/apply/add',
                    edit_url: 're/apply/edit',
                    del_url: 're/apply/del',
                    pass_url:'re/apply/pass',
                    deny_url:'re/apply/deny',
                    detail_url:'re/apply/detail',
                    multi_url: 're/apply/multi',
                    ajax_url: 're/apply/test',
                    table: 're_apply',
               //     dragsort_url: 're/apply/test',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'reResume.name', title: '用户姓名'},
                   /*     {field: 'reResume.age', title: '出生年月'},*/
                        {field: 'reResume.gender', title: '性别'},
                        {field: 'reResume.mobile', title: '手机号'},
                       /* {
                            field: 'button',
                            width: "120px",
                            title: __('按钮组'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'ajax',
                                    url: 're/apply/test',
                                    text:'点击查看手机号',
                                    title:"点击查看手机号",
                                    classname: 'btn btn-block btn-default btn-ajax',
                                    confirm:'确定查看?',
                                    refresh:true,
                                    success: function (data, ret) {
                                        $(".btn-refresh").trigger("click");
                                    //    Layer.alert(ret.msg + ",返回数据：" + JSON.stringify(data));
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;

                                       // table.bootstrapTable('refresh');
                                    },
                                    error: function (data, ret) {
                                        /!*console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;*!/
                                        $(".btn-refresh").trigger("click");
                                    }
                                },
                            ],
                            formatter: function (value, row, index) {
                                if(row.offer_status!=0) {
                                    return row.reResume.mobile
                                } else{
                                    var that = $.extend({},this);
                                    $.extend(that,{
                                        buttons:[{
                                            name: 'ajax',
                                            url: 're/apply/test',
                                            text:'点击查看手机号',
                                            title:"点击查看手机号",
                                            classname: 'btn btn-block btn-default btn-ajax',
                                            confirm:'确定查看?',
                                            refresh:true,
                                            success: function (data, ret) {
                                                //Layer.alert(ret.msg + ",返回数据：" + JSON.stringify(data));
                                                //如果需要阻止成功提示，则必须使用return false;
                                                //return false;
                                             //   $(".btn-refresh").trigger("click");

                                            },
                                            error: function (data, ret) {
                                                console.log(data, ret);
                                                Layer.alert(ret.msg);
                                                return false;
                                            }
                                        }]
                                    });
                                    // console.log(Table.api.formatter.operate.call(that,value,row,index));
                                    return Table.api.formatter.buttons.call(that,value,row,index);
                                }
                            }
                        },*/

                 /*     {field: 'reResume.user_address', title: '住址'},*/
                        {field: 'reCompany.name', title: '公司名称'},
                        {field: 'reJob.name', title: '岗位名称'},
                        {field: 'offer',  title: '是否录用', searchList: {'1':"已录用", '2': "未录用",'0':'待查看','3':'已查看','4':'通知面试','5':'离职'}, style: 'min-width:100px;'},
                        {field: 'recUser.username', title:'推荐人'},
                        {field: 'update_at', operate: false, title:'更新时间', addclass:'datetimerange'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: function (value, row, index) {
                            var table = this.table;
                            // 操作配置
                            var options = table ? table.bootstrapTable('getOptions') : {};
                            // 默认按钮组
                            var buttons = $.extend([], this.buttons || []);
                          //  options.exportDataType = "selected";
                            /*if (options.extend.edit_url !== '') {
                                buttons.push({
                                    name: 'edit',
                                    icon: 'fa fa-exchange',
                                    title: '编辑状态',
                                    extend: 'data-toggle="tooltip"',
                                    classname: 'btn btn-xs btn-primary btn-editone',
                                  //   url: options.extend.detail_url
                                });
                            }*/

                            if (options.extend.detail_url !== '') {
                                buttons.push({
                                    name: 'detail',
                                    icon: 'fa fa-list',
                                    title: '详情',
                                    extend: 'data-toggle="tooltip"',
                                    classname: 'btn btn-xs btn-primary btn-detailone',
                                    url: options.extend.detail_url
                                });
                            }

                            /*if (options.extend.del_url !== '') {
                                buttons.push({
                                    name: 'del',
                                    icon: 'fa fa-trash',
                                    title: __('Del'),
                                    extend: 'data-toggle="tooltip"',
                                    classname: 'btn btn-xs btn-danger btn-delone'
                                });
                            }*/
                            if((row.show_status==2)){
                                if (options.extend.pass_url !== '') {
                                    buttons.push({
                                        name: 'pass',
                                        icon: 'fa fa-check',
                                        title:'允许查看',
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-primary btn-passone',
                                    });
                                }

                               /* if (options.extend.deny_url !== '') {
                                    buttons.push({
                                        name: 'deny',
                                        icon: 'fa fa-times',
                                        title:'拒绝',
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-primary btn-denyone',
                                    });
                                }
*/
                            }

                            return Table.api.buttonlink(this, buttons, value, row, index, 'operate');
                        }}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            alert(123);
            Controller.api.bindevent();
        },
        edit: function () {

            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                console.log(123);
              /*  $(".btn-detail").click(function(){
                    alert(123);
                })*/
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});