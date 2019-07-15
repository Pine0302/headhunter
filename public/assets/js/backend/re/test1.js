define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {


    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 're/test1/index',
                    add_url: 're/test1/add',
                    edit_url: 're/test1/edit',
                 //   del_url: 're/test1/del',
                    pass_url: 're/test1/pass',
                    deny_url: 're/test1/deny',
                    multi_url: 're/test1/multi',
                    prompt_url:'re/test1/prompt',
                    table: 're_company',
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
                        /*{field: 'id', title: __('Id')},*/
                        {field: 'comp_name', title: '公司名称'},
                        {field: 'job_name', title:'岗位名称'},
                        {field: 'lower_user_name', title:'入职用户'},
                        {field: 'up_user_name', title: '上级用户'},
                        {field: 'l_cash', title: '入职用户奖金'},
                        {field: 'u_cash', title: '上级用户奖金'},
                        {field: 'p_cash', title: '平台服务费'},
                        {field: 'rate', title: '奖励进度',operate:false},
                     /*   {field: 'a_cash', title: '分销商奖金'},*/
                       /* {field: 'reward_type',  title: '奖励方式', searchList: {'1':"固定金额", '2': "固定比例"}, style: 'min-width:100px;'},*/
                        {field: 'status',  title: '状态', searchList: {'1':"已发奖", '2': "未发奖"}, style: 'min-width:100px;'},
                        {field: 'reason',  title: '取消原因', style: 'min-width:100px;'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: function (value, row, index) {
                            var table = this.table;
                            // 操作配置
                            var options = table ? table.bootstrapTable('getOptions') : {};
                            // 默认按钮组
                            var buttons = $.extend([], this.buttons || []);
                            if(row.status_ori==2){
                                if (options.extend.pass_url !== '') {
                                    buttons.push({
                                        name: 'pass',
                                        icon: 'fa fa-check',
                                        title:'发送奖励',
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-primary btn-passone',
                                    });
                                }
                              /*  if (options.extend.edit_url !== '') {
                                    buttons.push({
                                        name: 'edit',
                                        icon: 'fa fa-circle',
                                        title:'取消发奖',
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-primary btn-editone',
                                        url: options.extend.edit_url
                                    });
                                }*/

                                if (options.extend.edit_url !== '') {
                                    buttons.push({
                                        name: 'edit',
                                        icon: 'fa fa-exchange',
                                        title: '编辑状态',
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-primary btn-editone',
                                        // url: options.extend.edit_url
                                    });
                                }

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
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});