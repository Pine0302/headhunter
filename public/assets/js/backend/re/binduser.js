define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 're/binduser/index',
                    add_url: 're/binduser/add',
                    edit_url: 're/binduser/edit',
                    del_url: 're/binduser/del',
                    multi_url: 're/binduser/multi',
                    table: 're_binduser',
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
                        {field: 'id', title: __('Id'),operate:false},
                        {field: 'user.nickname', title: __('User_id'),operate:false},
                        {field: 'mobile', title: __('Mobile')},
                     /*   {field: 'admin_id', title: __('Admin_id')},*/
                        {field: 'reCompany.name', operate:false, title: __('Re_company_id')},
                        {field: 'create_at', title: __('Create_at'), operate:'RANGE',operate:false, addclass:'datetimerange'},
                        {field: 'update_at', title: __('Update_at'), operate:'RANGE',operate:false, addclass:'datetimerange'},
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