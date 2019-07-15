define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 're/topic/vote/index',
                    add_url: 're/topic/vote/add',
                    edit_url: 're/topic/vote/edit',
                    del_url: 're/topic/vote/del',
                    multi_url: 're/topic/vote/multi',
                    table: 're_topic_vote',
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
                        {field: 're_topic_id', title: __('Re_topic_id')},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'user_type', title: __('User_type')},
                        {field: 'vote', title: __('Vote')},
                        {field: 'result', title: __('Result')},
                        {field: 'coin', title: __('Coin')},
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