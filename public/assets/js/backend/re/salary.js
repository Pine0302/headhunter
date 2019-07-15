define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 're/salary/index',
                    add_url: 're/salary/add',
                   // edit_url: 're/salary/edit',
                    del_url: 're/salary/del',
                    multi_url: 're/salary/multi',
                    import_url: 're/salary/import',
                    detail_url: 're/salary/detail',
                    table: 're_salary',
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
                        {field: 'name', title: __('User_id')},
                        {field: 'salary', title: __('Salary'), operate:'BETWEEN'},
                        {field: 'month', title: __('Date'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'id_num', title: '身份证号'},
                        /*{field: 're_company_id', title: __('Re_company_id')},*/
                        {field: 'create_at', title: __('Create_at'), operate:'RANGE', addclass:'datetimerange'},
                       /* {field: 'update_at', title: __('Update_at'), operate:'RANGE', addclass:'datetimerange'},*/
                        /*{field: 're_salarymodel_id', title: __('Re_salarymodel_id')},*/
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,  formatter: function (value, row, index) {
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
                            if(row.offer_status==0){
                                if (options.extend.pass_url !== '') {
                                    buttons.push({
                                        name: 'pass',
                                        icon: 'fa fa-check',
                                        title:'录用',
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-primary btn-passone',
                                    });
                                }

                                if (options.extend.deny_url !== '') {
                                    buttons.push({
                                        name: 'deny',
                                        icon: 'fa fa-times',
                                        title:'拒绝',
                                        extend: 'data-toggle="tooltip"',
                                        classname: 'btn btn-xs btn-primary btn-denyone',
                                    });
                                }

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