define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'useraccount/index',
                    add_url: 'useraccount/add',
                    edit_url: 'useraccount/edit',
                    del_url: 'useraccount/del',
                    multi_url: 'useraccount/multi',
                    table: 'user_account',
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
                        {field: 'user_id', title: __('User_id'), operate:'BETWEEN'},
                        {field: 'total_account', title: __('Total_account'), operate:'BETWEEN'},
                        {field: 'new_income', title: __('New_income'), operate:'BETWEEN'},
                        {field: 'pend_income', title: __('Pend_income'), operate:'BETWEEN'},
                        {field: 'update_at', title: __('Update_at')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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