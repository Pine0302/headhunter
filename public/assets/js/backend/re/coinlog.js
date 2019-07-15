define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 're/coinlog/index',
                    add_url: 're/coinlog/add',
                    edit_url: 're/coinlog/edit',
                    del_url: 're/coinlog/del',
                    multi_url: 're/coinlog/multi',
                    table: 're_coin_log',
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
                        {field: 'id', title: __('Id')},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'user_type', title: __('User_type'),visible:false},
                        {field: 'user_type_text', title: __('User_type'),operate:false},
                        {field: 'num', title: __('Num')},
                        {field: 'way', title: __('Way'), visible:false, searchList: {"1":__('Way 1')}},
                        {field: 'way_text', title: __('Way'), operate:false},
                        {field: 'method', title: __('Method'), visible:false, searchList: {"2":__('Method 2')}},
                        {field: 'method_text', title: __('Method'), operate:false},
                     /*   {field: 're_coin_order_id', title: __('Re_coin_order_id')},
                        {field: 're_topic_id', title: __('Re_topic_id')},
                        {field: 're_apply_id', title: __('Re_apply_id')},
                        {field: 're_sign_log_id', title: __('Re_sign_log_id')},
                        {field: 're_resume_id', title: __('Re_resume_id')},
                        {field: 'status', title: __('Status'), visible:false, searchList: {"1":__('Status 1')}},
                        {field: 'status_text', title: __('Status'), operate:false},
                        {field: 'left_coin', title: __('Left_coin')},*/
                        {field: 'create_at', title: __('Create_at'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'expire_at', title: __('Expire_at'),operate:false},
                       /* {field: 'update_at', title: __('Update_at'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'admin_id', title: __('Admin_id')},*/
                       /* {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}*/
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