define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 're/topic/index',
                    add_url: 're/topic/add',
                    edit_url: 're/topic/edit',
                    del_url: 're/topic/del',
                    multi_url: 're/topic/multi',
                    table: 're_topic',
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
                        {field: 'title', title: __('Title')},
                        {field: 'coin', title: __('Coin')},
                        {field: 'blue_num', title: __('Blue_num')},
                        {field: 'red_num', title: __('Red_num')},
                        {field: 'total_num', title: __('Total_num')},
                        {field: 'total_coin', title: __('Total_coin')},
                        {field: 'end_time', title: __('End_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'result', title: __('Result'), visible:false, searchList: {"1":"蓝方获胜","2":"红方获胜","3":"平局"}},
                        {field: 'result_text', title: __('Result'), operate:false},
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