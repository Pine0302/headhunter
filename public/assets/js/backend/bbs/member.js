define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'bbs/member/index',
                    add_url: 'bbs/member/add',
                  //  edit_url: 'bbs/member/edit',
                   edit_url: 'bbs/member/detail',
                //    del_url: 'bbs/member/del',
                    multi_url: 'bbs/member/multi',
                    table: 'bbs_member',
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
                       /* {field: 'user_id', title: __('User_id')},*/
                      /*  {field: 'open_id', title: __('Open_id')},*/
                        {field: 'nick_name', title: __('Nick_name')},
                        {field: 'avatar_url', title: __('Avatar_url'), formatter: Table.api.formatter.image, operate: false},
                        {field: 'mobile', title: __('Mobile')},
                        {field: 'gender', title: __('Gender'), visible:false, searchList: {"2":__('Gender 2')}},
                        /*{field: 'gender_text', title: __('Gender'), operate:false},*/
                     /*   {field: 'province', title: __('Province')},
                        {field: 'city', title: __('City')},
                        {field: 'area', title: __('Area')},*/
                     /*   {field: 'isvalid', title: __('Isvalid')},*/
                     /*   {field: 'level', title: __('Level'), visible:false, searchList: {"2":__('Level 2')}},
                        {field: 'level_text', title: __('Level'), operate:false},*/
                        {field: 'point', title: __('Point')},
                       /* {field: 'is_banned', title: __('Is_banned'), visible:false, searchList: {"2":__('Is_banned 2')}},
                        {field: 'is_banned_text', title: __('Is_banned'), operate:false},*/
                       /* {field: 'is_back', title: __('Is_back'), visible:false, searchList: {"2":__('Is_back 2')}},
                        {field: 'is_back_text', title: __('Is_back'), operate:false},*/
                      /*  {field: 'bind_mobile', title: __('Bind_mobile'), visible:false, searchList: {"2":__('Bind_mobile 2')}},
                        {field: 'bind_mobile_text', title: __('Bind_mobile'), operate:false},*/
                      /*  {field: 'is_manage', title: __('Is_manage'), visible:false, searchList: {"2":__('Is_manage 2')}},
                        {field: 'is_manage_text', title: __('Is_manage'), operate:false},*/
                       /* {field: 'sign_count', title: __('Sign_count')},*/
                       /* {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'add_time', title: __('Add_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},*/
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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
                                    title: '编辑状态',
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