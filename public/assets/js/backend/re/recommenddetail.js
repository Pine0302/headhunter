define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 're/recommenddetail/index',
                    add_url: 're/recommenddetail/add',
                    edit_url: 're/recommenddetail/edit',
                    del_url: 're/recommenddetail/del',
                    multi_url: 're/recommenddetail/multi',
                    table: 're_recommenddetail',
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
                        {field: 'reply_id', title: __('Reply_id')},
                        {field: 'low_user_id', title: __('Low_user_id')},
                        {field: 'up_user_id', title: __('Up_user_id')},
                        {field: 'rec_user_id', title: __('Rec_user_id')},
                        {field: 'lower_cash', title: __('Lower_cash'), operate:'BETWEEN'},
                        {field: 'rec_cash', title: __('Rec_cash'), operate:'BETWEEN'},
                        {field: 'p_cash', title: __('P_cash'), operate:'BETWEEN'},
                        {field: 'agent_cash', title: __('Agent_cash'), operate:'BETWEEN'},
                        {field: 'way', title: __('Way')},
                        {field: 'status', title: __('Status')},
                        {field: 'total_cash', title: __('Total_cash'), operate:'BETWEEN'},
                        {field: 'admin_id', title: __('Admin_id')},
                        {field: 'create_at', title: __('Create_at'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'update_at', title: __('Update_at'), operate:'RANGE', addclass:'datetimerange'},
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