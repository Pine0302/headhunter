define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/user/index',
                    add_url: 'user/user/add',
                    edit_url: 'user/user/edit',
                    detail_url: 'user/user/detail',
                  //  del_url: 'user/user/del',
                    multi_url: 'user/user/multi',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'user.id',
                columns: [
                    [
                        {checkbox: true},
                       /* {field: 'id', title: __('Id'), sortable: true},*/
                      //  {field: 'group.name', title: __('Group')},
                        {field: 'username', title: __('Username'), operate: 'LIKE'},
                        {field: 'nickname', title: __('Nickname'), operate: 'LIKE'},
                      /*  {field: 'email', title: __('Email'), operate: 'LIKE'},*/
                        {field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
                        /*{field: 'district_name', title:"地区", operate: 'LIKE'},*/
                        {field: 'user_type', title:"身份", operate: false},
                        {field: 'avatar', title: __('Avatar'), formatter: Table.api.formatter.image, operate: false},
                       /* {field: 'level', title: __('Level'), operate: 'BETWEEN', sortable: true},*/
                        {field: 'gender', title: '性别', visible: true, searchList: {1: __('Male'), 2: __('Female'),0:'未知'}},
                   /*     {field: 'score', title: __('Score'), operate: 'BETWEEN', sortable: true},*/
                        /*{field: 'successions', title: __('Successions'), visible: false, operate: 'BETWEEN', sortable: true},
                        {field: 'maxsuccessions', title: __('Maxsuccessions'), visible: false, operate: 'BETWEEN', sortable: true},*/
                        {field: 'logintime', title: __('Logintime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                     /*   {field: 'loginip', title: __('Loginip'), formatter: Table.api.formatter.search},*/
                        {field: 'coin_info', title: "金币情况", operate: false},
                      /*  {field: 'jointime', title: __('Jointime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'joinip', title: __('Joinip'), formatter: Table.api.formatter.search},
                        {field: 'status', title: __('Status'), formatter: Table.api.formatter.status, searchList: {normal: __('Normal'), hidden: __('Hidden')}},*/
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: function (value, row, index) {
                            var table = this.table;
                            // 操作配置
                            var options = table ? table.bootstrapTable('getOptions') : {};
                            // 默认按钮组
                            var buttons = $.extend([], this.buttons || []);
                            if (options.extend.edit_url !== '') {
                                buttons.push({
                                    name: 'edit',
                                    icon: 'fa fa-exchange',
                                    title: '修改金币',
                                    extend: 'data-toggle="tooltip"',
                                    classname: 'btn btn-xs btn-primary btn-editone',
                                    // url: options.extend.detail_url
                                });
                            }


                            if (options.extend.detail_url !== '') {
                                buttons.push({
                                    name: 'detail',
                                    icon: 'fa fa-list',
                                    title: '详情',
                                    extend: 'data-toggle="tooltip"',
                                    classname: 'btn btn-xs btn-primary btn-detailone',
                                    url: options.extend.detail_url
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