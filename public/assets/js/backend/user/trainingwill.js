define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/trainingwill/index',
                    add_url: 'user/trainingwill/add',
                  //  edit_url: 'user/trainingwill/edit',
                    detail_url: 'user/trainingwill/detail',
                    del_url: 'user/trainingwill/del',
                    multi_url: 'user/trainingwill/multi',
                    table: 'user_trainingwill',
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
                        /*{field: 'user_id', title: __('User_id')},*/
                        {field: 'username', title: __('Username')},
                        {field: 'mobile', title: __('Mobile')},

                        /*{field: 'start_time', title: __('Start_time'), operate:false, addclass:'datetimerange'},*/

                        {field: 'create_at', title: __('Create_at'), operate:false, addclass:'datetimerange'},
                        /*{field: 'update_at', title: __('Update_at'), operate:false, addclass:'datetimerange'},*/
                        {field: 'content', title: '需求说明'},
                      /*  {field: 'areas',operate:false, title: __('Areas')},
                        {field: 'address', operate:false,title: __('Address')},*/
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