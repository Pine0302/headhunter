define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 're/applymission/index',
                    add_url: 're/applymission/add',
                    edit_url: 're/applymission/edit',
                    del_url: 're/applymission/del',
                    multi_url: 're/applymission/multi',
                    table: 're_apply_mission',
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
                        {field: 'user.nickname', title: __('User_id')},
                        {field: 'hr.nickname', title: __('Hr_id')},
                       /* {field: 'agent_id', title: __('Agent_id')},*/
                        /*{field: 're_apply_id', title: __('Re_apply_id')},*/
                        {field: 'project.name', title: __('Re_project_id')},
                        /*{field: 're_company_id', title: __('Re_company_id')},*/
                        {field: 'status', title: __('Status')},
                        {field: 'start_time', title: __('Start_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'end_time', title: __('End_time'), operate:'RANGE', addclass:'datetimerange'},
                        /*{field: 'imgs', title: __('Imgs')},*/
                        /*{field: 'update_at', title: __('Update_at'), operate:'RANGE', addclass:'datetimerange'},*/
                        {field: 'create_at', title: __('Create_at'), operate:'RANGE', addclass:'datetimerange'},
                        /*{field: 'admin_id', title: __('Admin_id')},*/
                        {field: 'hour_status', title: __('Hour_status'), visible:false, searchList: {"1":__('Hour_status 1')}},
                        {field: 'hour_status_text', title: __('Hour_status'), operate:false},
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