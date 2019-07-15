define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/training/index',
                    add_url: 'user/training/add',
                    edit_url: 'user/training/edit',
                    del_url: 'user/training/del',
                    multi_url: 'user/training/multi',
                    table: 'user_training',
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
                        {field: 'user.username', title: '用户姓名'},
                        {field: 'reTraining.name', title: '培训项目'},
                        {field: 'status', title:'培训状态', searchList: {'1':"已报名", '2': '已参加','3':'已结束'}, style: 'min-width:100px;'},
                        {field: 'create_at', title: __('Create_at'), operate:'RANGE', operate:false, addclass:'datetimerange'},
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