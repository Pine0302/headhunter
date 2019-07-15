define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'bbs/reply/index',
                    add_url: 'bbs/reply/add',
                    edit_url: 'bbs/reply/edit',
                    del_url: 'bbs/reply/del',
                    multi_url: 'bbs/reply/multi',
                    detail_url: 'bbs/reply/detail',
                    table: 'bbs_reply',
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
                        /*{field: 'bbsMember.nick_name', title: __('Bbs_member_id')},*/
                       /* {field: 'parent_id', title: __('Parent_id')},*/
                        {field: 'bbsMemberFor.nick_name', title:"帖子作者"},
                        /*{field: 'bbs_post_id', title: __('Bbs_post_id')},*/
                        {field: 'bbsPost.title', title: '帖子名称',operate:false},
                        {field: 'content', title: "回复内容"},
                      /*  {field: 'status', title:"是否展示", visiable:false,searchList: {"1":"隐藏",'2':'展示'}},
                        {field: 'ch_status', title:"是否展示",operate:false},*/
                      /*  {field: 'status_text', title: __('Status'), operate:false},*/
                        /*{field: 'isvalid', title: __('Isvalid')},*/
                        /*{field: 'is_read', title: __('Is_read'), visible:false, searchList: {"1":__('Is_read 1')}},
                        {field: 'is_read_text', title: __('Is_read'), operate:false},*/
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange'},
                      /*  {field: 'add_time', title: __('Add_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},*/
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,formatter: function (value, row, index) {
                            var table = this.table;
                            // 操作配置
                            var options = table ? table.bootstrapTable('getOptions') : {};
                            // 默认按钮组
                            var buttons = $.extend([], this.buttons || []);
                              if (options.extend.detail_url !== '') {
                             buttons.push({
                             name: 'detail',
                             icon: 'fa fa-list',
                             title: '详情',
                             extend: 'data-toggle="tooltip"',
                             classname: 'btn btn-xs btn-primary btn-detailone',
                             // url: options.extend.detail_url
                             });
                             }

                            if (options.extend.del_url !== '') {
                                buttons.push({
                                    name: 'del',
                                    icon: 'fa fa-trash',
                                    title: __('Del'),
                                    extend: 'data-toggle="tooltip"',
                                    classname: 'btn btn-xs btn-danger btn-delone'
                                });
                            }
                            return Table.api.buttonlink(this, buttons, value, row, index, 'operate');
                        }}
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