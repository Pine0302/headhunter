define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 're/ratio/index',
                    add_url: 're/ratio/add',
                    edit_url: 're/ratio/edit',
                    del_url: 're/ratio/del',
                    multi_url: 're/ratio/multi',
                    table: 're_ratio',
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
                 /*       {field: 'id', title: __('Id')},*/
                      /*  {field: 'rec_user_cash', title: __('Rec_user_cash'), operate:false},
                        {field: 'rec_user_per', title: __('Rec_user_per'),operate:false},*/
                        {field: 'p_cash', title: __('P_cash'), operate:false},
                        {field: 'p_per', title: __('P_per'),operate:false},

                        {field: 'admin.info', title:"代理商信息",operate:false},
                        {field: 'reward_type',  title: '推荐获佣金方式', searchList: {'1':"固定金额", '2': "固定比例"}, style: 'min-width:100px;'},
                        {field: 'withdraw_per', title:'会员提现比例(%)',operate:false},
                     /*   {field: 'agent_cash', title: __('Agent_cash'), operate:false},
                        {field: 'agent_per', title: __('Agent_per'),operate:false},*/
                     /*   {field: 'create_at', title: __('Create_at'), operate:'RANGE', addclass:'datetimerange'},
                     {field: 'update_at', title: __('Update_at'), operate:'RANGE', addclass:'datetimerange'},*/
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter:
                            function (value, row, index) {
                            var table = this.table;
                            // 操作配置
                            var options = table ? table.bootstrapTable('getOptions') : {};
                            // 默认按钮组
                            var buttons = $.extend([], this.buttons || []);
                                if (options.extend.edit_url !== '') {
                                    buttons.push({
                                        name: 'edit',
                                        icon: 'fa fa-edit',
                                        title: '编辑',
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-primary btn-editone'
                                        //   url: options.extend.edit_url
                                    });
                                }
                                if (options.extend.del_url !== '') {
                                    buttons.push({
                                        name: 'del',
                                        icon: 'fa fa-trash',
                                        title: __('Del'),
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-danger btn-delone'
                                    });
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