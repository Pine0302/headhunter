define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'bbs/forum/index',
                    add_url: 'bbs/forum/add',
                    edit_url: 'bbs/forum/edit',
                   // del_url: 'bbs/forum/del',
                    multi_url: 'bbs/forum/multi',
                    table: 'bbs_forum',
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
                     /*   {field: 'id', title: __('Id')},*/
                     /*   {field: 'type', title: __('Type'), visible:false, searchList: {"2":__('Type 2')}},
                        {field: 'type_text', title: __('Type'), operate:false},*/
                        {field: 'name', title: __('Name')},
                        {field: 'summary', title: "简介"},
                        {field: 'bbsForumtype.name', title: "所属圈子",operate:false},
                        /*{field: 'bbs_forumtype_id', title: __('Bbs_forumtype_id')},*/
                        /*{field: 'path', title: __('Path')},*/

                        /*{field: 'img', title: __('Img')},*/
                        {field: 'sort_id', title: "排序"},
                        {field: 'isvalid', title: '是否展示',searchList: {'1':"展示", '2': "隐藏"}, style: 'min-width:100px;'},
                        {field: 'create_time', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange'},
                    /*    {field: 'add_time', title: __('Add_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},*/
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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