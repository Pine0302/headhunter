define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'co/tip/index',
                    add_url: 'co/tip/add',
                    edit_url: 'co/tip/edit',
                    del_url: 'co/tip/del',
                    multi_url: 'co/tip/multi',
                    table: 'co_tip',
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
                      /*  {field: 'id', title: __('Id')},*/
                        {field: 'user.username', title: '用户名称'},
                        {field: 'title', title: __('Title')},
                        {field: 'hot',  operate:false,title:'热度'},
                        {field: 'hit', operate:false, title: __('Hit')},
                        {field: 'enable', title: '是否展示',searchList: {'1':"展示", '2': "隐藏"}, style: 'min-width:100px;'},
                        {field: 'coCircle.name', title: "所属圈子"},
                        {field: 'create_at', title:'发布时间', operate:false, addclass:'datetimerange'},
                     /*   {field: 'update_at', title: __('Update_at'), operate:false, addclass:'datetimerange'},*/
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